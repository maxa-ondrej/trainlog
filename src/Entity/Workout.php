<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkoutRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use function count;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
#[ORM\Table(name: 'workout')]
class Workout {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workouts')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    private DateTimeImmutable $performedAt;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    /** Duration in minutes. */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $durationMinutes = null;

    #[ORM\Column]
    private bool $isTemplate = false;

    /** @var Collection<int, WorkoutSet> */
    #[ORM\OneToMany(targetEntity: WorkoutSet::class, mappedBy: 'workout', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => Criteria::ASC])]
    private Collection $sets;

    public function __construct() {
        $this->performedAt = new DateTimeImmutable();
        $this->sets = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUser(): ?User {
        return $this->user ?? null;
    }

    public function setUser(User $user): void {
        $this->user = $user;
    }

    public function getPerformedAt(): DateTimeImmutable {
        return $this->performedAt;
    }

    public function setPerformedAt(DateTimeImmutable $performedAt): void {
        $this->performedAt = $performedAt;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getNote(): ?string {
        return $this->note;
    }

    public function setNote(?string $note): void {
        $this->note = $note;
    }

    public function getDurationMinutes(): ?int {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(?int $durationMinutes): void {
        $this->durationMinutes = $durationMinutes;
    }

    public function isTemplate(): bool {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): void {
        $this->isTemplate = $isTemplate;
    }

    /** @return Collection<int, WorkoutSet> */
    public function getSets(): Collection {
        return $this->sets;
    }

    public function addSet(WorkoutSet $set): void {
        if (!$this->sets->contains($set)) {
            $set->setWorkout($this);
            $this->sets->add($set);
        }
    }

    /** Required by Symfony Forms `by_reference: false` collection mapping. */
    public function removeSet(WorkoutSet $set): void {
        $this->sets->removeElement($set);
    }

    /**
     * Re-attach every set to this workout and assign sequential positions (1..n)
     * in iteration order. Call before flushing a form-submitted Workout.
     */
    public function renumberSets(): void {
        $position = 1;
        foreach ($this->sets as $set) {
            $set->setWorkout($this);
            $set->setPosition($position++);
        }
    }

    /**
     * Groups sets by their exercise, preserving the first-seen order. Each entry
     * holds the Exercise plus the list of its sets in original order.
     *
     * @return array<int, array{exercise: Exercise, sets: list<WorkoutSet>}>
     */
    public function getSetsByExercise(): array {
        $byExercise = [];
        foreach ($this->sets as $set) {
            $exercise = $set->getExercise();
            if ($exercise === null) {
                continue;
            }
            $id = $exercise->getId();
            if ($id === null) {
                continue;
            }
            if (!isset($byExercise[$id])) {
                $byExercise[$id] = ['exercise' => $exercise, 'sets' => []];
            }
            $byExercise[$id]['sets'][] = $set;
        }

        return $byExercise;
    }

    public function getTotalVolume(): float {
        $total = 0.0;
        foreach ($this->sets as $set) {
            $total += $set->getVolume();
        }

        return $total;
    }

    public function getDistinctExerciseCount(): int {
        $seen = [];
        foreach ($this->sets as $set) {
            $exercise = $set->getExercise();
            if ($exercise === null) {
                continue;
            }
            $id = $exercise->getId();
            if ($id !== null) {
                $seen[$id] = true;
            }
        }

        return count($seen);
    }
}
