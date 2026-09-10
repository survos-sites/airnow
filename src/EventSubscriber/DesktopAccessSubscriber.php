<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/** Optional desktop transport guard; ordinary web/console usage needs no native bridge. */
final class DesktopAccessSubscriber
{
    #[AsEventListener(priority: 128)]
    public function onRequest(RequestEvent $event): void
    {
        $token = $_SERVER['DESKTOP_TOKEN'] ?? $_ENV['DESKTOP_TOKEN'] ?? '';
        $request = $event->getRequest();
        if ($token === '' || !$event->isMainRequest() || $request->getPathInfo() === '/health') {
            return;
        }
        if ($request->getPathInfo() === '/' && hash_equals($token, $request->query->getString('desktop_token'))) {
            $response = new RedirectResponse('/');
            $response->headers->setCookie(Cookie::create('desktop_access', $token)->withHttpOnly(true)->withSameSite('strict'));
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $event->setResponse($response);
        } elseif (!hash_equals($token, $request->cookies->getString('desktop_access'))) {
            $event->setResponse(new Response('Desktop session required.', 403));
        }
    }
}
