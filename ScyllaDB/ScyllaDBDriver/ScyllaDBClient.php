<?php

namespace ScyllaDBDriver;

use Cassandra;

class ScyllaDBClient
{
    private \Cassandra\Session|null $session = null;
    private readonly string|null $keyspace;

    public function __construct(
        private readonly array $config,
    ) {
        $this->keyspace = $this->config['keyspace'] ?? null;
    }

    public function __destruct()
    {
        $this->session?->close(1);
    }


    public function connect(): self
    {
        if ($this->isConnected()) {
            return $this;
        }

        $connect = Cassandra::cluster()
            ->withContactPoints($this->config['host'] ?? 'localhost')
            ->withPort($this->config['port'] ?? 9042)
//            ->withCredentials('cassandra', 'cassandra')
//            ->withPersistentSessions(true)
//            ->withTokenAwareRouting(true)
            ->withConnectTimeout($this->config['connect-timeout'] ?? 0.5)
            ->build();
        $this->session = (null === $this->keyspace)
                ? $connect->connect()
                : $connect->connect($this->keyspace);
        return $this;
    }

    public function newQuery(): ScyllaDBQueryBuilder
    {
        $this->connect();
        return new ScyllaDBQueryBuilder($this->session);
    }

    public function isConnected(): bool
    {
        return null !== $this->session;
    }

    public function use(string $keyspace): self
    {
        return new self(array_merge($this->config, ['keyspace' => $keyspace]));
    }

    public function createKeySpace(string $keyspace): self
    {
        $this->connect()->newQuery()->query(sprintf(
            "CREATE KEYSPACE IF NOT EXISTS %s WITH REPLICATION = {'class': 'SimpleStrategy', 'replication_factor': 1};",
            $keyspace,
        ));
        return $this;
    }

    public function createTable(string $tableName, array $columnDefinition, string $keyspace = null, array $indexes = []): self
    {
        $keyspace ??= $this->keyspace;
        $this->connect()->newQuery()->query(sprintf(
            'CREATE TABLE IF NOT EXISTS %s.%s (%s);',
            $keyspace,
            $tableName,
            implode(', ', $columnDefinition),
        ));
        foreach ($indexes as $column) {
            $this->connect()->newQuery()->query(str_replace(
                [
                    '%keyspace%',
                    '%table_name%',
                    '%column%',
                ],
                [
                    $keyspace,
                    $tableName,
                    $column,
                ],
                'CREATE INDEX IF NOT EXISTS idx_%table_name%_%column% ON %keyspace%.%table_name% (%column%);',
            ));
        }
        return $this;
    }
}
