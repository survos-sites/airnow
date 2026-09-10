<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DesktopController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('desktop.html.twig');
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): Response
    {
        return new Response('symfony-desktop-ready', headers: ['Content-Type' => 'text/plain']);
    }
}
