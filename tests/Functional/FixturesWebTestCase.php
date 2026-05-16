<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function assert;
use function sprintf;

/**
 * Base class for functional WebTestCases.
 *
 * Loads the `demo` fixture group once per test (purging the test DB inside a transaction-style
 * `--purge-with-truncate` so seeded IDs reset between tests). This keeps tests independent.
 *
 * @internal
 */
abstract class FixturesWebTestCase extends WebTestCase {
    private static bool $fixturesLoaded = false;

    protected function setUp(): void {
        parent::setUp();
        $this->loadFixturesOnce();
    }

    /**
     * Returns the seeded user with the given email by looking it up in the test DB.
     * Requires that the kernel has been booted (e.g. via createClient()).
     */
    protected function getUser(KernelBrowser $client, string $email): User {
        $em = $client->getContainer()->get('doctrine')->getManager();
        assert($em instanceof EntityManagerInterface);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            throw new RuntimeException(sprintf('Seeded user "%s" not found — did fixtures load?', $email));
        }

        return $user;
    }

    /**
     * Marks fixtures as dirty so the next test reloads them.
     */
    protected function markFixturesDirty(): void {
        self::$fixturesLoaded = false;
    }

    /**
     * Loads `doctrine:fixtures:load --group=demo` once for the whole test process.
     * Subsequent tests reuse the seeded DB; tests must not mutate seeded data they rely on,
     * or should reset by calling self::$fixturesLoaded = false in tearDown.
     */
    private function loadFixturesOnce(): void {
        if (self::$fixturesLoaded) {
            return;
        }

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:fixtures:load',
            '--group' => ['demo'],
            '--no-interaction' => true,
            '--quiet' => true,
            '--env' => 'test',
        ]);
        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        if ($exitCode !== 0) {
            throw new RuntimeException('Failed to load fixtures: '.$output->fetch());
        }

        self::ensureKernelShutdown();
        self::$fixturesLoaded = true;
    }
}
