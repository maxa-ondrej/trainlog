<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\WorkoutSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function is_array;
use function is_scalar;

/** @extends ServiceEntityRepository<WorkoutSet> */
class WorkoutSetRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, WorkoutSet::class);
    }

    /**
     * Returns one row per workout date for the given exercise + user:
     *   ['date' => 'YYYY-MM-DD', 'maxWeight' => float, 'volume' => float].
     *
     * Skips template workouts. Ordered by date ascending so charts read left-to-right.
     *
     * @return list<array{date: string, maxWeight: float, volume: float}>
     */
    public function findProgressFor(User $user, Exercise $exercise): array {
        $rows = $this->createQueryBuilder('s')
            ->select('SUBSTRING(w.performedAt, 1, 10) AS d, MAX(s.weightKg) AS maxWeight, SUM(s.reps * s.weightKg) AS volume')
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
            $out[] = [
                'date' => is_scalar($date) ? (string) $date : '',
                'maxWeight' => is_numeric($maxWeight) ? (float) $maxWeight : 0.0,
                'volume' => is_numeric($volume) ? (float) $volume : 0.0,
            ];
        }

        return $out;
    }

    /**
     * @return list<WorkoutSet>
     */
    public function findAllForExerciseByUser(User $user, Exercise $exercise): array {
        $result = $this->createQueryBuilder('s')
            ->innerJoin('s.workout', 'w')->addSelect('w')
            ->andWhere('s.exercise = :exercise')
            ->andWhere('w.user = :user')
            ->andWhere('w.isTemplate = false')
            ->setParameter('exercise', $exercise)
            ->setParameter('user', $user)
            ->orderBy('w.performedAt', 'ASC')
            ->addOrderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();

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
