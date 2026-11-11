<?php

namespace Tests\Fixtures;

use Tests\TestCase;

class DatabaseConfigurationProbe extends TestCase
{
    public function test_database_configuration_is_isolated(): void
    {
        // Resolving configuration does not open a database connection.
        $connection = $this->app['db']->connection();

        $this->assertSame(getenv('EXPECTED_TEST_DRIVER'), $connection->getConfig('driver'));
        $this->assertSame(getenv('EXPECTED_TEST_DATABASE'), $connection->getConfig('database'));
        $this->assertEmpty($connection->getConfig('url'));
    }
}
