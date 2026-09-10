<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\RefreshState;
use App\Form\SettingsType;
use App\Repository\ObservationRepository;
use App\Service\AqiMonitor;
use App\Service\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Survos\AirNowBundle\Exception\AirNowException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesktopController extends AbstractController
{
    public function __construct(private readonly Settings $settings, private readonly ObservationRepository $observations, private readonly EntityManagerInterface $em) {}

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('desktop.html.twig', $this->dashboard());
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request, AqiMonitor $monitor): Response
    {
        if (!$this->isCsrfTokenValid('refresh', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid refresh token.');
        }
        if ($this->settings->hasApiKey()) {
            try { $monitor->refresh(force: $request->request->getBoolean('force')); }
            catch (AirNowException) { /* Persisted status is rendered with the last successful data. */ }
        }
        if ($request->isXmlHttpRequest()) {
            return $this->render('_observations.html.twig', $this->dashboard());
        }
        return $this->redirectToRoute('home');
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(): Response
    {
        return $this->render('history.html.twig', [
            'zip' => $this->settings->zipCode(),
            'observations' => $this->observations->history($this->settings->zipCode()),
        ]);
    }

    #[Route('/settings', name: 'settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class, ['zipCode' => $this->settings->zipCode()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->settings->save($data['zipCode'], $data['apiKey']);
            $this->addFlash('notice', 'Settings saved.');
            return $this->redirectToRoute('home');
        }
        return $this->render('settings.html.twig', ['form' => $form, 'hasKey' => $this->settings->hasApiKey()]);
    }

    #[Route('/hello', name: 'hello', methods: ['GET'])]
    public function hello(): Response
    {
        return new Response('Hello from Symfony Desktop');
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): Response
    {
        return new Response('symfony-desktop-ready', headers: ['Content-Type' => 'text/plain']);
    }

    private function dashboard(): array
    {
        $zip = $this->settings->zipCode();
        return ['zip' => $zip, 'hasKey' => $this->settings->hasApiKey(),
            'observations' => $this->observations->latest($zip),
            'state' => $this->em->find(RefreshState::class, $zip)];
    }
}
