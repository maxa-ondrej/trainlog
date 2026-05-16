<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\WorkoutSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use function is_array;
use function is_scalar;

/** @extends ServiceEntityRepository<WorkoutSet> */
class WorkoutSetRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, WorkoutSet::class);
    }

    /**
     * One aggregated row per workout date for the given exercise + user, holding
     * `maxWeight`, total `volume`, and the Epley `estimated1rm` for that day.
     * Skips template workouts. Ordered ascending so charts read left-to-right.
     *
     * @return list<array{date: string, maxWeight: float, volume: float, estimated1rm: float}>
     */
    public function findProgressFor(User $user, Exercise $exercise): array {
        $rows = $this->createQueryBuilder('s')
            ->select(
                'SUBSTRING(w.performedAt, 1, 10) AS d',
                'MAX(s.weightKg) AS maxWeight',
                'SUM(s.reps * s.weightKg) AS volume',
                'MAX(s.weightKg * (1 + s.reps / 30.0)) AS estimated1rm',
            )
            ->innerJoin('s.workout', 'w')
            ->andWhere('s.exercise = :exercise')
            ->andWhere('w.user = :user')
            ->andWhere('w.isTemplate = false')
            ->setParameter('exercise', $exercise)
            ->setParameter('user', $user)
            ->groupBy('d')
            ->orderBy('d', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['d'])) {
                continue;
            }
            $date = $row['d'];
            $maxWeight = $row['maxWeight'] ?? 0;
            $volume = $row['volume'] ?? 0;
            $estimated1rm = $row['estimated1rm'] ?? 0;
            $out[] = [
                'date' => is_scalar($date) ? (string) $date : '',
                'maxWeight' => is_numeric($maxWeight) ? (float) $maxWeight : 0.0,
                'volume' => is_numeric($volume) ? (float) $volume : 0.0,
                'estimated1rm' => is_numeric($estimated1rm) ? round((float) $estimated1rm, 2) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * @return list<WorkoutSet>
     */
    public function findAllForExerciseByUser(User $user, Exercise $exercise): array {
        return $this->collectSets($this->baseHistoryQuery($user)
            ->andWhere('s.exercise = :exercise')
            ->setParameter('exercise', $exercise));
    }

    /**
     * Loads every WorkoutSet for the given exercise ids in one round trip,
     * indexed by exercise id and ordered to match `findAllForExerciseByUser`
     * (workout date ascending, then position).
     *
     * @param list<int> $exerciseIds
     *
     * @return array<int, list<WorkoutSet>>
     */
    public function findAllForExercisesByUserGrouped(User $user, array $exerciseIds): array {
        if ($exerciseIds === []) {
            return [];
        }

        $sets = $this->collectSets($this->baseHistoryQuery($user)
            ->andWhere('s.exercise IN (:exerciseIds)')
            ->setParameter('exerciseIds', $exerciseIds));

        $out = [];
        foreach ($sets as $set) {
            $exercise = $set->getExercise();
            if ($exercise === null) {
                continue;
            }
            $id = $exercise->getId();
            if ($id === null) {
                continue;
            }
            $out[$id] ??= [];
            $out[$id][] = $set;
        }

        return $out;
    }

    private function baseHistoryQuery(User $user): QueryBuilder {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.workout', 'w')->addSelect('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.isTemplate = false')
            ->setParameter('user', $user)
            ->orderBy('w.performedAt', 'ASC')
            ->addOrderBy('s.position', 'ASC');
    }

    /**
     * @return list<WorkoutSet>
     */
    private function collectSets(QueryBuilder $qb): array {
        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        $out = [];
        foreach ($result as $row) {
            if ($row instanceof WorkoutSet) {
                $out[] = $row;
            }
        }

        return $out;
    }
}
