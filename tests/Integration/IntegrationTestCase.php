<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use Tests\TestCase;

/**
 * Runs against the real MySQL database from .env. Every test runs inside a
 * transaction that is rolled back afterwards, so nothing is kept, and no test
 * may run DDL (CREATE/ALTER/DROP), which would commit it.
 *
 * Opt-in:  INTEGRATION_DB=1 vendor/bin/phpunit --testsuite Integration
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Database $db;

    protected function setUp(): void
    {
        if (getenv('INTEGRATION_DB') !== '1') {
            $this->markTestSkipped('Set INTEGRATION_DB=1 to run tests against MySQL.');
        }

        parent::setUp();

        $this->db = $this->app->container()->make(Database::class);
        $this->db->pdo()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->db) && $this->db->pdo()->inTransaction()) {
            $this->db->pdo()->rollBack();
        }

        parent::tearDown();
    }

    protected function usesFakes(): bool
    {
        return false;
    }

    /** Creates a player (inside the test transaction) and returns its id. */
    protected function createUser(string $username, array $attributes = []): int
    {
        $id = $this->db->insert('users', [
            'username' => $username,
            'email' => "{$username}@example.test",
            'password_hash' => password_hash('secret-pass', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            ...$attributes,
        ]);
        $this->db->insert('user_stats', ['user_id' => $id]);

        return $id;
    }

    protected function loginAs(string $username): void
    {
        $_SESSION = [];
        $response = $this->post('/login', ['login' => $username, 'password' => 'secret-pass']);
        $this->assertSame('/dashboard', $response->getHeader('Location'), "login as {$username}");
    }

    protected function row(string $sql, array $params = []): ?array
    {
        return $this->db->fetch($sql, $params);
    }
}
