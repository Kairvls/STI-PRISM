<?php

namespace App\Support;

use Illuminate\Database\MySqlConnection;

class CachedMySqlConnection extends MySqlConnection
{
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new CachedMySqlBuilder($this);
    }
}
