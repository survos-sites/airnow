import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'results', 'button', 'status'];
    static values = { enabled: Boolean };

    connect() {
        if (!this.enabledValue) return;
        this.refresh(false);
        this.timer = setInterval(() => this.refresh(false), 300_000);
    }

    disconnect() {
        clearInterval(this.timer);
        this.abort?.abort();
    }

    manual(event) {
        event.preventDefault();
        this.refresh(true);
    }

    async refresh(force) {
        if (this.busy) return;
        this.busy = true;
        this.buttonTarget.disabled = true;
        this.buttonTarget.textContent = 'Checking…';
        this.abort = new AbortController();
        try {
            const data = new FormData(this.formTarget);
            data.set('force', force ? '1' : '0');
            const response = await fetch(this.formTarget.action, {
                method: 'POST', body: data, signal: this.abort.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Refresh failed');
            this.resultsTarget.innerHTML = await response.text();
            this.statusTarget.textContent = 'Checks every five minutes. API responses are cached for one hour.';
        } catch (error) {
            if (error.name !== 'AbortError') this.statusTarget.textContent = 'Unable to refresh. Saved observations are still shown. Try again.';
        } finally {
            this.busy = false;
            this.buttonTarget.disabled = false;
            this.buttonTarget.textContent = 'Refresh now';
        }
    }
}
