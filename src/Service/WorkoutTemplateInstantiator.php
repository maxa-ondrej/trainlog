<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutSet;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class WorkoutTemplateInstantiator {
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function instantiate(Workout $template, User $user): Workout {
        $workout = new Workout();
        $workout->setUser($user);
        $workout->setName($template->getName());
        $workout->setNote($template->getNote());
        $workout->setPerformedAt(new DateTimeImmutable());
        $workout->setIsTemplate(false);

        $position = 1;
        foreach ($template->getSets() as $sourceSet) {
            $exercise = $sourceSet->getExercise();
            if ($exercise === null) {
                continue;
            }
            $clone = new WorkoutSet();
            $clone->setExercise($exercise);
            $clone->setReps($sourceSet->getReps());
            $clone->setWeightKg($sourceSet->getWeightKg());
            $clone->setRpe($sourceSet->getRpe());
            $clone->setPosition($position++);
            $workout->addSet($clone);
        }

        $this->em->persist($workout);
        $this->em->flush();

        return $workout;
    }
}
