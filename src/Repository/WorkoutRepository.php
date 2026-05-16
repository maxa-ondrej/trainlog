<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutSet;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Workout> */
final class WorkoutRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Workout::class);
    }

    public function createUserHistoryQuery(
        User $user,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
        ?Exercise $exercise = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.isTemplate = false')
            ->setParameter('user', $user)
            ->orderBy('w.performedAt', 'DESC')
            ->addOrderBy('w.id', 'DESC');

        if ($from !== null) {
            $qb->andWhere('w.performedAt >= :from')->setParameter('from', $from);
        }
        if ($to !== null) {
            $qb->andWhere('w.performedAt <= :to')->setParameter('to', $to);
        }
        if ($exercise !== null) {
            $qb->andWhere('EXISTS (SELECT 1 FROM '.WorkoutSet::class.' s WHERE s.workout = w AND s.exercise = :exercise)')
                ->setParameter('exercise', $exercise);
        }

        return $qb;
    }

    /** @return list<Workout> */
    public function findUserTemplates(User $user): array {
        return $this->findBy(
            ['user' => $user, 'isTemplate' => true],
            ['name' => 'ASC'],
        );
    }
}
