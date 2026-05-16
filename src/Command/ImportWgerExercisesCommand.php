<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Exercise;
use App\Entity\MuscleGroup;
use App\Entity\Role;
use App\Repository\ExerciseRepository;
use App\Repository\MuscleGroupRepository;
use App\Repository\UserRepository;
use App\Service\Wger\WgerClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: 'app:exercise:import-wger',
    description: 'Import a curated subset of exercises from wger.de as public exercises owned by an admin user.',
)]
final class ImportWgerExercisesCommand {
    /**
     * Best-effort English-name → Czech muscle group mapping. Anything not in this
     * list is skipped silently — the operator can refine afterwards in /admin.
     *
     * @var array<string, string>
     */
    private const MUSCLE_MAP = [
        'Pectoralis major' => 'prsa',
        'Biceps brachii' => 'biceps',
        'Triceps brachii' => 'triceps',
        'Latissimus dorsi' => 'záda',
        'Trapezius' => 'záda',
        'Anterior deltoid' => 'ramena',
        'Deltoid' => 'ramena',
        'Quadriceps femoris' => 'kvadricepsy',
        'Biceps femoris' => 'hamstringy',
        'Gluteus maximus' => 'hýždě',
        'Gastrocnemius' => 'lýtka',
        'Soleus' => 'lýtka',
        'Rectus abdominis' => 'břicho',
        'Brachialis' => 'biceps',
        'Brachioradialis' => 'předloktí',
    ];

    public function __construct(
        private readonly WgerClient $wger,
        private readonly ExerciseRepository $exercises,
        private readonly MuscleGroupRepository $muscleGroups,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Maximum number of exercises to import')]
        int $limit = 50,
        #[Option(description: 'Language code (cs, en, de)')]
        string $language = 'en',
        #[Argument(description: 'Email of the admin user that will own the imported exercises')]
        string $ownerEmail = 'admin@trainlog.local',
    ): int {
        $owner = $this->users->findOneBy(['email' => $ownerEmail]);
        if ($owner === null || $owner->getRole() !== Role::Admin) {
            $io->error(sprintf('Admin user "%s" not found (or is not ROLE_ADMIN).', $ownerEmail));

            return Command::FAILURE;
        }

        $io->info(sprintf('Fetching up to %d exercises from wger.de (%s)…', $limit, $language));
        $entries = $this->wger->fetchExercises($limit, $language);

        $created = 0;
        $skipped = 0;
        foreach ($entries as $entry) {
            $existing = $this->exercises->findOneBy(['name' => $entry['name']]);
            if ($existing !== null) {
                ++$skipped;

                continue;
            }

            $exercise = new Exercise();
            $exercise->setName($entry['name']);
            $exercise->setDescription($entry['description'] !== '' ? $entry['description'] : null);
            $exercise->setOwner($owner);
            $exercise->setIsPublic(true);

            foreach ($entry['muscles'] as $muscleName) {
                $czName = self::MUSCLE_MAP[$muscleName] ?? null;
                if ($czName === null) {
                    continue;
                }
                $mg = $this->muscleGroups->findOneBy(['name' => $czName]);
                if ($mg instanceof MuscleGroup) {
                    $exercise->addMuscleGroup($mg);
                }
            }

            $this->em->persist($exercise);
            ++$created;
        }

        $this->em->flush();

        $io->success(sprintf('Imported %d new exercises (skipped %d duplicates).', $created, $skipped));

        return Command::SUCCESS;
    }
}
