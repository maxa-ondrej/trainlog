<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkoutSetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkoutSetRepository::class)]
#[ORM\Table(name: 'workout_set')]
class WorkoutSet {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Workout::class, inversedBy: 'sets')]
    #[ORM\JoinColumn(nullable: false)]
    private Workout $workout;

    #[ORM\ManyToOne(targetEntity: Exercise::class, inversedBy: 'workoutSets')]
    #[ORM\JoinColumn(nullable: false)]
    private Exercise $exercise;

    #[ORM\Column]
    #[Assert\Positive]
    private int $position = 1;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $reps = 0;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $weightKg = '0.00';

    /** RPE (Rate of Perceived Exertion) 1–10, optional. */
    #[ORM\Column(type: 'decimal', precision: 3, scale: 1, nullable: true)]
    #[Assert\Range(min: 1, max: 10)]
    private ?string $rpe = null;

    public function getId(): ?int {
        return $this->id;
    }

    public function getWorkout(): ?Workout {
        return $this->workout ?? null;
    }

    public function setWorkout(Workout $workout): void {
        $this->workout = $workout;
    }

    public function getExercise(): ?Exercise {
        return $this->exercise ?? null;
    }

    public function setExercise(Exercise $exercise): void {
        $this->exercise = $exercise;
    }

    public function getPosition(): int {
        return $this->position;
    }

    public function setPosition(int $position): void {
        $this->position = $position;
    }

    public function getReps(): int {
        return $this->reps;
    }

    public function setReps(int $reps): void {
        $this->reps = $reps;
    }

    public function getWeightKg(): string {
        return $this->weightKg;
    }

    public function setWeightKg(string $weightKg): void {
        $this->weightKg = $weightKg;
    }

    public function getWeightKgAsFloat(): float {
        return (float) $this->weightKg;
    }

    public function getRpe(): ?string {
        return $this->rpe;
    }

    public function setRpe(?string $rpe): void {
        $this->rpe = $rpe;
    }

    /** Estimated volume: reps × weight (kg). */
    public function getVolume(): float {
        return $this->reps * $this->getWeightKgAsFloat();
    }

    /** Estimated 1RM by the Epley formula: weight × (1 + reps/30). */
    public function getEstimated1Rm(): float {
        return $this->getWeightKgAsFloat() * (1 + $this->reps / 30);
    }
}
