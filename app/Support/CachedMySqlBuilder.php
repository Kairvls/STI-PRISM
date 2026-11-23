<?php

namespace App\Support;

use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Caches MySQL table/column existence lookups in memory + local file cache
 * so repeated Schema::hasTable() / Schema::hasColumn() calls do not hit
 * information_schema dozens of times per HTTP request.
 */
class CachedMySqlBuilder extends MySqlBuilder
{
    private const CACHE_TTL_SECONDS = 300;

    /** @var array<string, array<string, array<string, string>>> */
    private static array $schemaMaps = [];

    public static function flush(?string $database = null): void
    {
        if ($database !== null) {
            $key = self::cacheKey($database);
            unset(self::$schemaMaps[$key]);
            try {
                Cache::store('file')->forget($key);
            } catch (Throwable) {
                // Ignore file cache errors.
            }

            return;
        }

        foreach (array_keys(self::$schemaMaps) as $key) {
            try {
                Cache::store('file')->forget($key);
            } catch (Throwable) {
                // Ignore file cache errors.
            }
        }
        self::$schemaMaps = [];
    }

    public function hasTable($table)
    {
        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $dbName = (string) $this->connection->getDatabaseName();

        if ($dbName === '' || ($schema !== null && strtolower($schema) !== strtolower($dbName))) {
            return parent::hasTable($table);
        }

        $map = $this->schemaMap($dbName);
        if ($map === null) {
            return parent::hasTable($table);
        }

        $prefixed = strtolower($this->connection->getTablePrefix().$tableName);

        return array_key_exists($prefixed, $map);
    }

    public function getColumnListing($table)
    {
        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $dbName = (string) $this->connection->getDatabaseName();

        if ($dbName === '' || ($schema !== null && strtolower($schema) !== strtolower($dbName))) {
            return parent::getColumnListing($table);
        }

        $map = $this->schemaMap($dbName);
        if ($map === null) {
            return parent::getColumnListing($table);
        }

        $prefixed = strtolower($this->connection->getTablePrefix().$tableName);

        return isset($map[$prefixed]) ? array_values($map[$prefixed]) : [];
    }

    public function hasColumn($table, $column)
    {
        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $dbName = (string) $this->connection->getDatabaseName();

        if ($dbName === '' || ($schema !== null && strtolower($schema) !== strtolower($dbName))) {
            return parent::hasColumn($table, $column);
        }

        $map = $this->schemaMap($dbName);
        if ($map === null) {
            return parent::hasColumn($table, $column);
        }

        $prefixed = strtolower($this->connection->getTablePrefix().$tableName);

        return isset($map[$prefixed][strtolower((string) $column)]);
    }

    public function hasColumns($table, array $columns)
    {
        [$schema, $tableName] = $this->parseSchemaAndTable($table);
        $dbName = (string) $this->connection->getDatabaseName();

        if ($dbName === '' || ($schema !== null && strtolower($schema) !== strtolower($dbName))) {
            return parent::hasColumns($table, $columns);
        }

        $map = $this->schemaMap($dbName);
        if ($map === null) {
            return parent::hasColumns($table, $columns);
        }

        $prefixed = strtolower($this->connection->getTablePrefix().$tableName);
        if (! isset($map[$prefixed])) {
            return false;
        }

        foreach ($columns as $column) {
            if (! isset($map[$prefixed][strtolower((string) $column)])) {
                return false;
            }
        }

        return true;
    }

    public function create($table, \Closure $callback)
    {
        try {
            parent::create($table, $callback);
        } finally {
            self::flush((string) $this->connection->getDatabaseName());
        }
    }

    public function table($table, \Closure $callback)
    {
        try {
            parent::table($table, $callback);
        } finally {
            self::flush((string) $this->connection->getDatabaseName());
        }
    }

    public function drop($table)
    {
        try {
            parent::drop($table);
        } finally {
            self::flush((string) $this->connection->getDatabaseName());
        }
    }

    public function dropIfExists($table)
    {
        try {
            parent::dropIfExists($table);
        } finally {
            self::flush((string) $this->connection->getDatabaseName());
        }
    }

    public function rename($from, $to)
    {
        try {
            parent::rename($from, $to);
        } finally {
            self::flush((string) $this->connection->getDatabaseName());
        }
    }

    public function dropAllTables()
    {
        try {
            parent::dropAllTables();
        } finally {
            self::flush((string) $this->connection->getDatabaseName());
        }
    }

    /**
     * @return array<string, array<string, string>>|null
     */
    private function schemaMap(string $database): ?array
    {
        $key = self::cacheKey($database);
        if (isset(self::$schemaMaps[$key])) {
            return self::$schemaMaps[$key];
        }

        try {
            $map = Cache::store('file')->remember($key, self::CACHE_TTL_SECONDS, function () use ($database) {
                $rows = $this->connection->select(
                    'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
                    [$database]
                );

                $tables = [];
                foreach ($rows as $row) {
                    $t = strtolower((string) ($row->TABLE_NAME ?? $row->table_name ?? ''));
                    $c = (string) ($row->COLUMN_NAME ?? $row->column_name ?? '');
                    if ($t === '' || $c === '') {
                        continue;
                    }
                    $tables[$t][strtolower($c)] = $c;
                }

                return $tables;
            });

            if (! is_array($map)) {
                return null;
            }

            return self::$schemaMaps[$key] = $map;
        } catch (Throwable) {
            return null;
        }
    }

    private static function cacheKey(string $database): string
    {
        return 'prism_mysql_schema_map_v1_'.md5(strtolower($database));
    }
}
