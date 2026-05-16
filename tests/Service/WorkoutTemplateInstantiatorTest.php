<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutSet;
use App\Service\WorkoutTemplateInstantiator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WorkoutTemplateInstantiatorTest extends TestCase {
    public function testInstantiateClonesTemplateWithFreshDateAndPositions(): void {
        $owner = new User();
        $exercise = new Exercise();

        $template = new Workout();
        $template->setUser($owner);
        $template->setName('Push template');
        $template->setNote('Easy day');
        $template->setIsTemplate(true);
        $template->setPerformedAt(new DateTimeImmutable('2020-01-01'));

        foreach ([[5, '60.00'], [5, '70.00'], [3, '80.00']] as $i => [$reps, $weight]) {
            $set = new WorkoutSet();
            $set->setExercise($exercise);
            $set->setReps($reps);
            $set->setWeightKg($weight);
            $set->setRpe('8.0');
            $set->setPosition($i + 1);
            $template->addSet($set);
        }

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Workout::class));
        $em->expects(self::once())->method('flush');

        $instantiator = new WorkoutTemplateInstantiator($em);

        $newUser = new User();
        $today = new DateTimeImmutable('today');

        $cloned = $instantiator->instantiate($template, $newUser);

        self::assertNotSame($template, $cloned);
        self::assertFalse($cloned->isTemplate());
        self::assertSame('Push template', $cloned->getName());
        self::assertSame('Easy day', $cloned->getNote());
        self::assertSame($newUser, $cloned->getUser());
        self::assertSame($today->format('Y-m-d'), $cloned->getPerformedAt()->format('Y-m-d'));

        self::assertCount(3, $cloned->getSets());
        $positions = [];
        $weights = [];
        foreach ($cloned->getSets() as $set) {
            $positions[] = $set->getPosition();
            $weights[] = $set->getWeightKg();
            // Each cloned set must point at the new workout, not the template
            self::assertSame($cloned, $set->getWorkout());
            // Each cloned set is a distinct object
            self::assertNotContains($set, iterator_to_array($template->getSets()));
        }

        self::assertSame([1, 2, 3], $positions);
        self::assertSame(['60.00', '70.00', '80.00'], $weights);
    }
}
