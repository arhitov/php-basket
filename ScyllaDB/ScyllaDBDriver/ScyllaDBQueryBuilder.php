<?php

namespace ScyllaDBDriver;

use Illuminate\Support\Collection;

class ScyllaDBQueryBuilder
{
    private string $table;
    private array|null $select = null;

    public function __construct(
        private \Cassandra\Session $session
    ) {
    }

    public function query(string $rawQuery)
    {
        $result = $this->session->execute(
            new \Cassandra\SimpleStatement($rawQuery),
        );

        if ($result instanceof \Cassandra\Rows) {
            $collection = new Collection;
            foreach ($result as $row) {
                $collection->add(array_map(
                    fn($value) => match (true) {
                        $value instanceof \Cassandra\Uuid      => $value->uuid(),
                        $value instanceof \Cassandra\Timestamp => (int)(string)$value,
                        default                                => (string)$value,
                    },
                    $row,
                ));
            }
            return $collection;
        }

        dd(
            'query $result',
            $result
        );
    }

    public function table(string $tableName): self
    {
        $this->table = $tableName;
        return $this;
    }

    public function insert(array $data): void
    {
        dump(
            'insert',
            $data,
        );
        $this->query(sprintf(
            'INSERT INTO %s (%s) VALUES (%s);',
            $this->table,
            implode(', ', array_keys($data)),
            implode(', ', array_values($data)),
        ));
    }

    public function get(): Collection
    {
        return $this->query(sprintf(
            'SELECT %s FROM %s;',
            null === $this->select ? '*' : implode(', ', $this->select),
            $this->table,
        ));
    }
}
