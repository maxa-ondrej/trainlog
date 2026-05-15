<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'user_email_unique', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(enumType: Role::class)]
    private Role $role = Role::User;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, Workout> */
    #[ORM\OneToMany(targetEntity: Workout::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $workouts;

    /** @var Collection<int, Exercise> */
    #[ORM\OneToMany(targetEntity: Exercise::class, mappedBy: 'owner')]
    private Collection $exercises;

    public function __construct() {
        $this->createdAt = new DateTimeImmutable();
        $this->workouts = new ArrayCollection();
        $this->exercises = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getUserIdentifier(): string {
        return $this->email;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): void {
        $this->password = $hashedPassword;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getRole(): Role {
        return $this->role;
    }

    public function setRole(Role $role): void {
        $this->role = $role;
    }

    /** @return list<string> */
    public function getRoles(): array {
        return $this->role === Role::Admin
            ? [Role::Admin->value, Role::User->value]
            : [Role::User->value];
    }

    public function getCreatedAt(): DateTimeImmutable {
        return $this->createdAt;
    }

    /** @return Collection<int, Workout> */
    public function getWorkouts(): Collection {
        return $this->workouts;
    }

    /** @return Collection<int, Exercise> */
    public function getExercises(): Collection {
        return $this->exercises;
    }

    public function eraseCredentials(): void {
        // no-op: we don't store plaintext credentials anywhere
    }
}
