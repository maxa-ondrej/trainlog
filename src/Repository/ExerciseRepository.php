<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Exercise;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function is_array;

/** @extends ServiceEntityRepository<Exercise> */
final class ExerciseRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Exercise::class);
    }

    /**
     * Builds the base query for exercises visible to the user (owned + public),
     * optionally filtered by muscle-group ids. Does NOT apply LIMIT/OFFSET —
     * the controller is in charge of pagination.
     *
     * @param list<int> $muscleGroupIds
     */
    public function createVisibleQuery(User $user, array $muscleGroupIds): QueryBuilder {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.muscleGroups', 'mg')->addSelect('mg')
            ->leftJoin('e.owner', 'o')->addSelect('o')
            ->andWhere('e.owner = :user OR e.isPublic = true')
            ->setParameter('user', $user)
            ->orderBy('e.name', 'ASC');

        if ($muscleGroupIds !== []) {
            $sub = $this->createQueryBuilder('e2')
                ->select('e2.id')
                ->innerJoin('e2.muscleGroups', 'mg2')
                ->andWhere('mg2.id IN (:mgIds)');
            $qb->andWhere($qb->expr()->in('e.id', $sub->getDQL()))
                ->setParameter('mgIds', $muscleGroupIds);
        }

        return $qb;
    }

    /**
     * @param list<int> $muscleGroupIds
     */
    public function countVisible(User $user, array $muscleGroupIds): int {
        $qb = $this->createVisibleQuery($user, $muscleGroupIds);
        $qb->resetDQLPart('orderBy')
            ->resetDQLPart('select')
            ->select('COUNT(DISTINCT e.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param list<int> $muscleGroupIds
     *
     * @return list<Exercise>
     */
    public function findVisiblePage(User $user, array $muscleGroupIds, int $page, int $perPage): array {
        $qb = $this->createVisibleQuery($user, $muscleGroupIds)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        $out = [];
        foreach ($result as $row) {
            if ($row instanceof Exercise) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Loads every exercise with `owner` and `muscleGroups` eager-fetched, ordered
     * by name. Intended for the admin list page; do not use for user-facing lists
     * (no visibility filtering).
     *
     * @return list<Exercise>
     */
    public function findAllWithOwnerAndMuscleGroups(): array {
        $result = $this->createQueryBuilder('e')
            ->leftJoin('e.owner', 'o')->addSelect('o')
            ->leftJoin('e.muscleGroups', 'mg')->addSelect('mg')
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
        if (!is_array($result)) {
            return [];
        }

        $out = [];
        foreach ($result as $row) {
            if ($row instanceof Exercise) {
                $out[] = $row;
            }
        }

        return $out;
    }
}
