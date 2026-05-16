<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Exercise;
use App\Entity\Role;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function in_array;

/** @extends Voter<string, Exercise> */
final class ExerciseVoter extends Voter {
    public const VIEW = 'EXERCISE_VIEW';
    public const EDIT = 'EXERCISE_EDIT';
    public const DELETE = 'EXERCISE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Exercise;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isAdmin = $user->getRole() === Role::Admin;
        $isOwner = $user === $subject->getOwner();

        return match ($attribute) {
            self::VIEW => $isOwner || $subject->isPublic() || $isAdmin,
            self::EDIT, self::DELETE => $isOwner || $isAdmin,
            default => false,
        };
    }
}
