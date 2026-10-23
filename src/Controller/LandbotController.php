<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandbotController extends AbstractController
{
    public function __construct(
        private readonly string $landbotConfigUrl,
    ) {
    }

    #[Route('/', name: 'landbot_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('landbot/index.html.twig', [
            'landbot_config_url' => $this->landbotConfigUrl,
        ]);
    }
}
