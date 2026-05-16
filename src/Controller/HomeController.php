<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\WorkoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController {
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(WorkoutRepository $workouts): Response {
        $user = $this->getUser();
        $recentWorkouts = $user instanceof User
            ? $workouts->findUserHistoryWithSets($user, limit: 5)
            : [];

        return $this->render('home/index.html.twig', [
            'recentWorkouts' => $recentWorkouts,
        ]);
    }
}
