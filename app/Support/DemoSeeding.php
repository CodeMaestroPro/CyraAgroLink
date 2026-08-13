<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Controls whether request-time demo/catalog seeding may run.
 *
 * Production defaults to OFF. Override with CYRA_ALLOW_DEMO_SEEDING=true|false.
 */
final class DemoSeeding
{
    /**
     * Whether the app may auto-seed demo catalogs, fleets, farms, etc. on GET.
     */
    public static function allowed(): bool
    {
        $override = config('cyra.allow_demo_seeding');

        if ($override !== null && $override !== '') {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return ! app()->environment('production');
    }

    /**
     * Run a seeder callback only when demo seeding is allowed.
     */
    public static function run(callable $callback): void
    {
        if (! self::allowed()) {
            return;
        }

        $callback();
    }
}
