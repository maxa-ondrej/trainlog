<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExerciseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExerciseRepository::class)]
#[ORM\Table(name: 'exercise')]
class Exercise {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\Column]
    private bool $isPublic = false;

    /** @var Collection<int, MuscleGroup> */
    #[ORM\ManyToMany(targetEntity: MuscleGroup::class, inversedBy: 'exercises')]
    #[ORM\JoinTable(name: 'exercise_muscle_group')]
    private Collection $muscleGroups;

    /** @var Collection<int, WorkoutSet> */
    #[ORM\OneToMany(targetEntity: WorkoutSet::class, mappedBy: 'exercise')]
    private Collection $workoutSets;

    public function __construct() {
        $this->muscleGroups = new ArrayCollection();
        $this->workoutSets = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function getOwner(): ?User {
        return $this->owner ?? null;
    }

    public function setOwner(User $owner): void {
        $this->owner = $owner;
    }

    public function isPublic(): bool {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void {
        $this->isPublic = $isPublic;
    }

    /** @return Collection<int, MuscleGroup> */
    public function getMuscleGroups(): Collection {
        return $this->muscleGroups;
    }

    public function addMuscleGroup(MuscleGroup $muscleGroup): void {
        if (!$this->muscleGroups->contains($muscleGroup)) {
            $this->muscleGroups->add($muscleGroup);
        }
    }

    public function removeMuscleGroup(MuscleGroup $muscleGroup): void {
        $this->muscleGroups->removeElement($muscleGroup);
    }

    /** @return Collection<int, WorkoutSet> */
    public function getWorkoutSets(): Collection {
        return $this->workoutSets;
    }
}
