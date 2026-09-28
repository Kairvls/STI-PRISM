<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed reference data after RefreshDatabase rebuilds the schema.
     */
    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        // RefreshDatabase wipes the connection, so never let tests touch a live database.
        if ($connection !== 'sqlite' && !str_ends_with($database, '_testing')) {
            throw new RuntimeException("Refusing to run tests against database [{$database}]; use a *_testing database (see phpunit.xml).");
        }

        self::ensureMysqlClientOnPath();

        return $app;
    }

    /**
     * Loading database/schema/mysql-schema.sql shells out to the `mysql` client.
     */
    private static function ensureMysqlClientOnPath(): void
    {
        $xamppBin = 'C:\\xampp\\mysql\\bin';
        $path = (string) getenv('PATH');

        if (PHP_OS_FAMILY !== 'Windows' || !is_file($xamppBin.'\\mysql.exe') || stripos($path, $xamppBin) !== false) {
            return;
        }

        $path = $xamppBin.';'.$path;
        putenv('PATH='.$path);
        $_SERVER['PATH'] = $_ENV['PATH'] = $path;
    }
}
