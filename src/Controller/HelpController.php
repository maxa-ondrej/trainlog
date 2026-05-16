<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HelpController extends AbstractController {
    #[Route('/navod', name: 'help_index', methods: ['GET'])]
    public function index(): Response {
        return $this->render('help/index.html.twig');
    }
}
