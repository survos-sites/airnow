use std::{fs, io::{Read, Write}, net::{TcpListener, TcpStream}, path::PathBuf, process::{Child, Command, Stdio}, sync::Mutex, thread, time::{Duration, Instant}};
use tauri::{Manager, WebviewUrl, WebviewWindowBuilder, WindowEvent, RunEvent, menu::{Menu, MenuItem}, tray::{TrayIconBuilder, TrayIconEvent, MouseButton, MouseButtonState}};

struct Server(Mutex<Option<Child>>);
impl Server {
    fn stop(&self) {
        if let Some(mut child) = self.0.lock().unwrap().take() {
            if matches!(child.try_wait(), Ok(Some(_))) { return; }
            // SIGTERM lets Caddy drain requests and release the listener. Always reap the child.
            unsafe { libc::kill(child.id() as i32, libc::SIGTERM); }
            let deadline = Instant::now() + Duration::from_secs(5);
            while Instant::now() < deadline {
                if matches!(child.try_wait(), Ok(Some(_))) { return; }
                thread::sleep(Duration::from_millis(50));
            }
            let _ = child.kill();
            let _ = child.wait();
        }
    }
}
impl Drop for Server { fn drop(&mut self) { self.stop(); } }

fn show(app: &tauri::AppHandle) {
    // A macOS accessory app can be hidden independently of its window.
    let _ = app.show();
    if let Some(window) = app.get_webview_window("main") {
        let _ = window.unminimize();
        let _ = window.show();
        let _ = window.set_focus();
    }
}

fn start(app: &tauri::AppHandle) -> Result<(Server, String), Box<dyn std::error::Error>> {
    let source = PathBuf::from(env!("CARGO_MANIFEST_DIR")).parent().unwrap().to_path_buf();
    let (root, binary, config) = if cfg!(debug_assertions) {
        let arch = if cfg!(target_arch = "aarch64") { "aarch64" } else { "x86_64" };
        (source.clone(), source.join(format!("src-tauri/binaries/frankenphp-{arch}-apple-darwin")), source.join("desktop/Caddyfile"))
    } else {
        let resources = app.path().resource_dir()?;
        (resources.join("app"), std::env::current_exe()?.parent().unwrap().join("frankenphp"), resources.join("Caddyfile"))
    };
    let data = if cfg!(debug_assertions) { root.join("var/desktop") } else { app.path().app_data_dir()? };
    fs::create_dir_all(&data)?;
    let log = fs::OpenOptions::new().create(true).append(true).open(data.join("runtime.log"))?;
    let listener = TcpListener::bind("127.0.0.1:0")?;
    let port = listener.local_addr()?.port();
    drop(listener);
    let secret = uuid::Uuid::new_v4().to_string();
    let command = || -> Result<Command, std::io::Error> {
        let mut cmd = Command::new(&binary);
        cmd.current_dir(&root)
            .env("DESKTOP_PORT", port.to_string()).env("APP_PUBLIC_DIR", root.join("public"))
            .env("APP_ENV", if cfg!(debug_assertions) { "dev" } else { "prod" })
            .env("APP_DEBUG", if cfg!(debug_assertions) { "1" } else { "0" }).env("APP_DATA_DIR", &data)
            .env("APP_BUILD_ID", fs::read_to_string(root.join(".build-id")).unwrap_or_else(|_| "dev".into()))
            .env("DEFAULT_URI", format!("http://127.0.0.1:{port}"))
            .env("APP_SECRET", &secret).env("DESKTOP_TOKEN", &secret)
            .stdin(Stdio::null()).stdout(log.try_clone()?).stderr(log.try_clone()?);
        Ok(cmd)
    };
    // Run ordinary Symfony migrations using bundled PHP before accepting web requests.
    let migration = command()?.args(["php-cli", "bin/console", "doctrine:migrations:migrate", "--no-interaction"]).spawn()?;
    let migration = Server(Mutex::new(Some(migration)));
    let deadline = Instant::now() + Duration::from_secs(30);
    loop {
        if let Some(status) = migration.0.lock().unwrap().as_mut().unwrap().try_wait()? {
            if !status.success() { return Err(format!("Database migration failed. See {}", data.join("runtime.log").display()).into()); }
            break;
        }
        if Instant::now() >= deadline { return Err("Database migration timed out; see runtime.log".into()); }
        thread::sleep(Duration::from_millis(100));
    }
    let child = command()?.args(["run", "--config"]).arg(config).spawn()?;
    let server = Server(Mutex::new(Some(child)));
    let deadline = Instant::now() + Duration::from_secs(30);
    while Instant::now() < deadline {
        let status = server.0.lock().unwrap().as_mut().unwrap().try_wait()?;
        if let Some(status) = status {
            return Err(format!("FrankenPHP exited ({status}). See {}", data.join("runtime.log").display()).into());
        }
        if let Ok(mut stream) = TcpStream::connect_timeout(&format!("127.0.0.1:{port}").parse()?, Duration::from_millis(200)) {
            stream.set_read_timeout(Some(Duration::from_millis(500)))?;
            let _ = write!(stream, "GET /health HTTP/1.1\r\nHost: 127.0.0.1:{port}\r\nConnection: close\r\n\r\n");
            let mut body = String::new();
            let _ = stream.read_to_string(&mut body);
            if body.contains("symfony-desktop-ready") {
                return Ok((server, format!("http://127.0.0.1:{port}/?desktop_token={secret}")));
            }
        }
        thread::sleep(Duration::from_millis(100));
    }
    Err(format!("Symfony startup timed out. See {}", data.join("runtime.log").display()).into())
}

fn main() {
    let app = tauri::Builder::default()
        .setup(|app| {
            app.set_activation_policy(tauri::ActivationPolicy::Accessory);
            let (server, url) = start(app.handle())?;
            app.manage(server);
            let origin = url.split("/?").next().unwrap().to_owned();
            WebviewWindowBuilder::new(app, "main", WebviewUrl::External(url.parse()?))
                .title("Air Quality").inner_size(400.0, 510.0).resizable(false).visible(true)
                .on_navigation(move |url| url.as_str().starts_with(&format!("{origin}/")))
                .build()?;
            let open = MenuItem::with_id(app, "open", "Open Air Quality", true, None::<&str>)?;
            let quit = MenuItem::with_id(app, "quit", "Quit Air Quality", true, Some("CmdOrCtrl+Q"))?;
            // Small monochrome three-bar glyph, suitable for a macOS template icon.
            let mut rgba = vec![0_u8; 18 * 18 * 4];
            for (y, width) in [(4, 14), (8, 10), (12, 14)] {
                for row in y..y+2 { for x in 2..width { rgba[(row * 18 + x) * 4 + 3] = 255; } }
            }
            TrayIconBuilder::new().icon(tauri::image::Image::new_owned(rgba, 18, 18))
                .icon_as_template(true).tooltip("Air Quality").menu(&Menu::with_items(app, &[&open, &quit])?)
                .show_menu_on_left_click(false)
                .on_menu_event(|app, event| match event.id.as_ref() {
                    "open" => show(app), "quit" => app.exit(0), _ => {}
                })
                .on_tray_icon_event(|tray, event| {
                    if matches!(event, TrayIconEvent::Click { button: MouseButton::Left, button_state: MouseButtonState::Down, .. }) { show(tray.app_handle()); }
                }).build(app)?;
            Ok(())
        })
        .on_window_event(|window, event| {
            if let WindowEvent::CloseRequested { api, .. } = event {
                api.prevent_close();
                let _ = window.hide();
            }
        })
        .build(tauri::generate_context!()).expect("Unable to start AirNow; check runtime.log");
    app.run(|app, event| {
        if matches!(event, RunEvent::Reopen { .. }) { show(app); }
        if matches!(event, RunEvent::Exit) {
            if let Some(server) = app.try_state::<Server>() { server.stop(); }
        }
    });
}
