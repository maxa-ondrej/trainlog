<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController {
    #[Route('', name: 'home', methods: ['GET'])]
    public function index(): Response {
        return $this->render('admin/index.html.twig');
    }
}
