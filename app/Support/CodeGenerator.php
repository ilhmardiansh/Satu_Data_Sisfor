<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CodeGenerator
{
    /**
     * Generate a unique readable code with the given prefix.
     */
    public static function next(string $prefix, ?string $table = null, string $column = 'code'): string
    {
        $prefix = strtoupper(trim($prefix));

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = strtoupper(base_convert((string) (int) (microtime(true) * 100), 10, 36));
            $code = $prefix.'-'.substr($suffix, -4).Str::upper(Str::random(3));

            if ($table === null || ! DB::table($table)->where($column, $code)->exists()) {
                return $code;
            }
        }

        return $prefix.'-'.Str::upper(Str::random(8));
    }
}
