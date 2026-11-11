<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DatabaseIsolationTest extends TestCase
{
    public function test_both_configurations_override_an_external_database_url(): void
    {
        foreach (['phpunit.xml' => ['sqlite', ':memory:'], 'phpunit.mysql.xml' => ['mysql', 'testing']] as $configuration => [$driver, $database]) {
            $process = new Process([
                PHP_BINARY,
                'vendor/bin/phpunit',
                '--configuration', $configuration,
                '--do-not-cache-result',
                'tests/Fixtures/DatabaseConfigurationProbe.php',
            ], dirname(__DIR__, 2), [
                'DB_URL' => 'mysql://example:example@127.0.0.1/production_example',
                'DB_CONNECTION' => 'mysql',
                'DB_DATABASE' => 'production_example',
                'EXPECTED_TEST_DRIVER' => $driver,
                'EXPECTED_TEST_DATABASE' => $database,
            ]);
            $process->run();

            $this->assertTrue($process->isSuccessful(), $configuration."\n".$process->getOutput().$process->getErrorOutput());
        }
    }
}
