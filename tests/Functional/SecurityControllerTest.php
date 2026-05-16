<?php

declare(strict_types=1);

/*
 * Functional tests for SecurityController. Boots the kernel in `test` env and uses
 * fixtures loaded by FixturesWebTestCase (group `demo`) — the seeded users
 * `demo@trainlog.local` / `demo` and `hosta@trainlog.local` / `demo` are assumed to exist.
 * Tests rely on the DOM crawler to submit forms so stateless CSRF tokens flow correctly.
 */

namespace App\Tests\Functional;

/**
 * @internal
 */
final class SecurityControllerTest extends FixturesWebTestCase {
    public function testLoginPageRendersForm(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form input[name="_username"]');
        self::assertSelectorExists('form input[name="_password"]');
        self::assertSelectorExists('form input[name="_csrf_token"]');
    }

    public function testLoginWithBadCredentialsShowsError(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Přihlásit se')->form([
            '_username' => 'demo@trainlog.local',
            '_password' => 'wrong-password',
        ]);
        $client->submit($form);

        // After failure Symfony redirects back to the login form
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert.alert-danger');
    }

    public function testLoginWithDemoCredentialsRedirectsToHome(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Přihlásit se')->form([
            '_username' => 'demo@trainlog.local',
            '_password' => 'demo',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/');
    }

    public function testHomePageShowsUserNameInNavbarWhenLoggedIn(): void {
        $client = self::createClient();
        $user = $this->getUser($client, 'demo@trainlog.local');
        $client->loginUser($user);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('nav', 'Demo uživatel');
    }
}
