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

use function is_array;

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

    /**
     * Loads user workouts with their sets + exercises eager-fetched in a single
     * round-trip per call. Uses a two-step query so `$limit` operates on parent
     * rows rather than the joined product.
     *
     * @return list<Workout>
     */
    public function findUserHistoryWithSets(
        User $user,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
        ?Exercise $exercise = null,
        ?int $limit = null,
    ): array {
        $idsQb = $this->createUserHistoryQuery($user, $from, $to, $exercise)->select('w.id');
        if ($limit !== null) {
            $idsQb->setMaxResults($limit);
        }

        /** @var list<array{id: int}> $rows */
        $rows = $idsQb->getQuery()->getArrayResult();
        $ids = array_column($rows, 'id');
        if ($ids === []) {
            return [];
        }

        return $this->loadWithSetsByIds($ids);
    }

    /** @return list<Workout> */
    public function findUserTemplates(User $user): array {
        $idsQb = $this->createQueryBuilder('w')
            ->select('w.id')
            ->andWhere('w.user = :user')
            ->andWhere('w.isTemplate = true')
            ->setParameter('user', $user)
            ->orderBy('w.name', 'ASC');

        /** @var list<array{id: int}> $rows */
        $rows = $idsQb->getQuery()->getArrayResult();
        $ids = array_column($rows, 'id');
        if ($ids === []) {
            return [];
        }

        return $this->loadWithSetsByIds($ids, orderByName: true);
    }

    /**
     * @param list<int> $ids
     *
     * @return list<Workout>
     */
    private function loadWithSetsByIds(array $ids, bool $orderByName = false): array {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.sets', 's')->addSelect('s')
            ->leftJoin('s.exercise', 'ex')->addSelect('ex')
            ->andWhere('w.id IN (:ids)')
            ->setParameter('ids', $ids);

        if ($orderByName) {
            $qb->orderBy('w.name', 'ASC');
        } else {
            $qb->orderBy('w.performedAt', 'DESC')->addOrderBy('w.id', 'DESC');
        }
        $qb->addOrderBy('s.position', 'ASC');

        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        $out = [];
        foreach ($result as $row) {
            if ($row instanceof Workout) {
                $out[] = $row;
            }
        }

        return $out;
    }
}
