<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users', name: 'admin_user_')]
final class UserController extends AbstractController {
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $users): Response {
        return $this->render('admin/users/index.html.twig', [
            'users' => $users->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/toggle-role', name: 'toggle_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleRole(User $user, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_user_toggle_role_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Nemůžete měnit vlastní roli.');

            return $this->redirectToRoute('admin_user_index');
        }

        $user->setRole($user->getRole() === Role::Admin ? Role::User : Role::Admin);
        $em->flush();

        $this->addFlash('success', sprintf('Role uživatele %s byla změněna na %s.', $user->getEmail(), $user->getRole()->value));

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_user_delete_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Nemůžete smazat sami sebe.');

            return $this->redirectToRoute('admin_user_index');
        }

        $email = $user->getEmail();
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', sprintf('Uživatel %s byl smazán.', $email));

        return $this->redirectToRoute('admin_user_index');
    }
}
