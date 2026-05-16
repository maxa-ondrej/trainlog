<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\WorkoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function is_array;

final class HomeController extends AbstractController {
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(WorkoutRepository $workouts): Response {
        $user = $this->getUser();
        $recentWorkouts = [];

        if ($user instanceof User) {
            $list = $workouts->createUserHistoryQuery($user)
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
            $recentWorkouts = is_array($list) ? $list : [];
        }

        return $this->render('home/index.html.twig', [
            'recentWorkouts' => $recentWorkouts,
        ]);
    }
}
