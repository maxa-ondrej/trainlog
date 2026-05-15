<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MuscleGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MuscleGroupRepository::class)]
#[ORM\Table(name: 'muscle_group')]
#[ORM\UniqueConstraint(name: 'muscle_group_name_unique', columns: ['name'])]
class MuscleGroup {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $name = '';

    /** @var Collection<int, Exercise> */
    #[ORM\ManyToMany(targetEntity: Exercise::class, mappedBy: 'muscleGroups')]
    private Collection $exercises;

    public function __construct(string $name = '') {
        $this->name = $name;
        $this->exercises = new ArrayCollection();
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

    /** @return Collection<int, Exercise> */
    public function getExercises(): Collection {
        return $this->exercises;
    }

    public function __toString(): string {
        return $this->name;
    }
}
