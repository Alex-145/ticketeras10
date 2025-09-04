<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DbDialect
{
    public static function like(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /** Expresión SQL de solo-dígitos para phone, compatible con sqlite/mysql/pgsql */
    public static function phoneDigitsExpr(string $column = 'phone'): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "regexp_replace(COALESCE($column,''), '\\D', '', 'g')";
        }

        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($column,''),' ',''),'-',''),'+',''),'(',''),')','')";
    }
}
