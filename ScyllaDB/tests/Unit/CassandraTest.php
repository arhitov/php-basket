<?php

namespace Tests\Unit;

use Cassandra;
use Tests\TestCase;

class CassandraTest extends TestCase
{
    /**
     * @return void
     * @throws Cassandra\Exception
     */
    public function testCassandraWork(): void
    {
        $cluster = Cassandra::cluster()
            ->withContactPoints('scylladb')
            ->withPort(9042)
//            ->withCredentials('cassandra', 'cassandra')
            ->withPersistentSessions(true)
            ->withTokenAwareRouting(true)
            ->withConnectTimeout(15.0)
            ->build();

        $session = $cluster->connect();
        $statement = new Cassandra\SimpleStatement(
            'SELECT key, build_mode from system.versions'
        );
        $result = $session->execute($statement);
        $this->assertEquals(
            [
                'key'        => 'local',
                'build_mode' => 'release',
            ],
            $result->first(),
        );
    }
}
