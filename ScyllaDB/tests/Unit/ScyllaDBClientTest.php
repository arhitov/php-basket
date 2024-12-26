<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use ScyllaDBDriver\ScyllaDBClient;
use Tests\TestCase;

class ScyllaDBClientTest extends TestCase
{
    const PREFIX_KEYSPACE = 'ks_test_';
    private ScyllaDBClient $connect;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connect = (new ScyllaDBClient(['host' => 'scylladb']))->connect();
    }

    /**
     * @after
     * @return void
     */
    public function tearDown(): void
    {
        $this->connect->newQuery()->query('SELECT keyspace_name FROM system_schema.keyspaces;')
            ->filter(fn(array $row) => str_starts_with($row['keyspace_name'], self::PREFIX_KEYSPACE))
            ->map(function(array $row) {
                $this->connect->newQuery()->query(sprintf(
                    'DROP KEYSPACE IF EXISTS %s;',
                    $row['keyspace_name'],
                ));
            });
    }

    private function generateKeyspace(): string
    {
        return self::PREFIX_KEYSPACE . fake()->word();
    }

    public function testConnect(): void
    {
        $this->assertTrue($this->connect->isConnected());
    }

    public function testQuery(): void
    {
        $queryBuilder = $this->connect->newQuery();

        $rows = $queryBuilder->query('SELECT key, build_mode from system.versions');
        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertCount(1, $rows);
        $this->assertEquals(
            [
                'key'        => 'local',
                'build_mode' => 'release',
            ],
            $rows->first(),
        );
    }

    public function testCreateKeySpace(): void
    {
        $keyspace = $this->generateKeyspace();
        $this->connect->createKeySpace($keyspace);

        $rows = $this->connect->newQuery()->query('SELECT keyspace_name FROM system_schema.keyspaces;')
            ->filter(fn(array $row) => str_starts_with($row['keyspace_name'], 'ks_'))
            ->map(fn(array $row) => $row['keyspace_name'])
            ->values()
            ->toArray();
        sort($rows);
        $expected = [
            $keyspace,
        ];
        sort($expected);
        $this->assertEquals(
            $expected,
            $rows
        );
    }

    public function testCreateTable(): void
    {
        $keyspace = $this->generateKeyspace();
        $this->connect
            ->createKeySpace($keyspace)
            ->use($keyspace)
            ->createTable(
                'my_table',
                [
                    'uuid uuid PRIMARY KEY,',
                    'sid text,',
                    'name text,',
                    'value text,',
                    'expires_at timestamp',
                ],
                indexes: [
                    'sid', 'name', 'expires_at',
                ],
            );

        // Проверяем наличие таблица
        $rows = $this->connect->newQuery()->query(sprintf(
            "SELECT table_name FROM system_schema.tables WHERE keyspace_name = '%s';",
            $keyspace,
        ));
        $this->assertEquals(
            [
                ['table_name' => 'my_table'],
            ],
            $rows->toArray(),
        );

        // Проверяем наличие индекесов
        $indexes = $this->connect->newQuery()->query(sprintf(
            "DESCRIBE TABLE %s.my_table;",
            $keyspace,
        ))
            ->filter(fn(array $row) => $row['type'] === 'index')
            ->map(fn(array $row) => ['name' => $row['name']])
            ->values()
            ->toArray();
        $this->assertEquals(
            [
                ['name' => 'idx_my_table_expires_at_index'],
                ['name' => 'idx_my_table_name_index'],
                ['name' => 'idx_my_table_sid_index'],
            ],
            $indexes,
        );
    }

    public function testInsetSelectUpdate(): void
    {
        $keyspace = $this->generateKeyspace();
        $connect = $this->connect
            ->createKeySpace($keyspace)
            ->use($keyspace);
        $connect->createTable(
                'my_table',
                [
                    'uuid uuid PRIMARY KEY,',
                    'sid text,',
                    'name text,',
                    'value text,',
                    'expires_at timestamp',
                ],
                indexes: [
                    'sid', 'name', 'expires_at',
                ],
            );
        $sid = implode('_', fake()->words(5));
        $name = fake()->word();
        $value = '{"string": "asd", "num": 1, "float": 1.2, "nested": ["qwe", 123]}';
        $expires_at = time();
        $connect->newQuery()->table('my_table')->insert([
            'uuid'       => 'now()',
            'sid'        => "'" . $sid . "'",
            'name'       => "'" . $name . "'",
            'value'      => "'" . $value . "'",
            'expires_at' => $expires_at,
        ]);

        $result = $connect->newQuery()->table('my_table')/*->select()*/
            ->get()
            ->map(fn(array $row) => [
                'sid'        => $row['sid'],
                'name'       => $row['name'],
                'value'      => $row['value'],
                'expires_at' => $row['expires_at'],
            ]);
        $this->assertEquals(
            [
                [
                    'sid'        => $sid,
                    'name'       => $name,
                    'value'      => $value,
                    'expires_at' => $expires_at,
                ],
            ],
            $result->toArray(),
        );


        dd(
            '$result',
            $result
        );
    }
}
