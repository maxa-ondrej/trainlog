<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Workout;
use App\Repository\WorkoutRepository;
use App\Security\Voter\WorkoutVoter;
use App\Service\PdfRenderer;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function is_array;
use function sprintf;

#[IsGranted('ROLE_USER')]
final class WorkoutExportController extends AbstractController {
    public function __construct(
        private readonly PdfRenderer $pdf,
    ) {}

    #[Route('/workouts/{id}/export.pdf', name: 'workout_export_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function workout(Workout $workout): Response {
        if (!$this->isGranted(WorkoutVoter::VIEW, $workout)) {
            throw $this->createAccessDeniedException();
        }

        $byExercise = [];
        foreach ($workout->getSets() as $set) {
            $ex = $set->getExercise();
            if ($ex === null || $ex->getId() === null) {
                continue;
            }
            $byExercise[$ex->getId()] ??= ['exercise' => $ex, 'sets' => []];
            $byExercise[$ex->getId()]['sets'][] = $set;
        }

        $pdf = $this->pdf->render('pdf/workout.html.twig', [
            'workout' => $workout,
            'byExercise' => $byExercise,
        ]);

        return $this->binaryPdfResponse($pdf, sprintf('workout-%d.pdf', $workout->getId() ?? 0));
    }

    #[Route('/workouts/export/{year}-{month}.pdf', name: 'workout_export_month_pdf', methods: ['GET'], requirements: ['year' => '\d{4}', 'month' => '\d{1,2}'])]
    public function month(int $year, int $month, WorkoutRepository $workouts): Response {
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException('Invalid month.');
        }

        /** @var User $user */
        $user = $this->getUser();

        $from = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month))->setTime(0, 0);
        $to = $from->modify('first day of next month')->modify('-1 second');

        $list = $workouts->createUserHistoryQuery($user, $from, $to)
            ->getQuery()
            ->getResult();

        $pdf = $this->pdf->render('pdf/monthly.html.twig', [
            'workouts' => is_array($list) ? $list : [],
            'year' => $year,
            'month' => $month,
            'user' => $user,
        ]);

        return $this->binaryPdfResponse($pdf, sprintf('workouts-%04d-%02d.pdf', $year, $month));
    }

    private function binaryPdfResponse(string $pdf, string $filename): Response {
        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}
