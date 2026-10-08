<?php

namespace App\Modules\Platform\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Run before every deployment (the cPanel deploy script calls it, and
 * stops if it fails). Checks the things that silently break Opsyra's
 * promises rather than crashing:
 *
 * - The database user must not be a superuser or have BYPASSRLS — either
 *   switches row-level security off, and every company would see every
 *   other company's data (docs/decisions.md, Phase 2).
 * - The database session must be UTC, or clock times shift by hours.
 * - Every company table must have row-level security on and forced
 *   (--tables, after migrations).
 * - Production must not run with debug output or without an app key.
 *
 * Warnings (don't stop a deploy) cover mail, the queue and HTTPS.
 */
class PreflightCommand extends Command
{
    /**
     * Tables with a tenant_id that are deliberately outside row-level
     * security. Anything else unprotected fails the check.
     */
    public const PLATFORM_TABLES = [
        // Looked up by token before the company is known (Auth's prepare_authentication migration).
        'personal_access_tokens',
        // The platform's own billing record per company, not company data (Phase 2).
        'subscriptions',
    ];

    protected $signature = 'opsyra:preflight {--tables : Also check every company table has row-level security (run after migrating)}';

    protected $description = 'Check this deployment is safe to run Opsyra (database role, timezone, row-level security, settings)';

    private int $failures = 0;

    public function handle(): int
    {
        $this->info('Opsyra preflight — '.config('app.env').' — '.config('app.url'));

        $this->check('App key is set', filled(config('app.key')));
        $this->check('Debug output is off in production', ! (app()->isProduction() && config('app.debug')), 'Set APP_DEBUG=false — debug pages expose settings and data.');
        $this->check('Database is PostgreSQL', DB::connection()->getDriverName() === 'pgsql', 'Opsyra keeps companies apart with PostgreSQL row-level security; other databases are not supported.');

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return $this->finish();
        }

        $version = (int) DB::selectOne('show server_version_num')->server_version_num;
        $this->check('PostgreSQL 12 or newer (found '.intdiv($version, 10000).')', $version >= 120000);

        $role = DB::selectOne('select current_user as name, rolsuper, rolbypassrls from pg_roles where rolname = current_user');
        $this->check("Database user '{$role->name}' is not a superuser", ! $role->rolsuper, 'A superuser ignores row-level security: every company would see every other company\'s data. Use an ordinary database user (cPanel\'s PostgreSQL Databases creates these).');
        $this->check("Database user '{$role->name}' cannot bypass row-level security", ! $role->rolbypassrls, 'Remove BYPASSRLS from this role.');

        $timezone = DB::selectOne('show timezone')->TimeZone ?? DB::selectOne('show timezone')->timezone ?? null;
        $this->check("Database session timezone is UTC (found {$timezone})", in_array($timezone, ['UTC', 'Etc/UTC'], true), 'config/database.php sets it; check nothing overrides DB timezone.');

        if ($this->option('tables')) {
            $unprotected = DB::select("
                select c.relname
                from pg_class c
                join pg_namespace n on n.oid = c.relnamespace and n.nspname = current_schema()
                join pg_attribute a on a.attrelid = c.oid and a.attname = 'tenant_id' and not a.attisdropped
                where c.relkind = 'r' and (not c.relrowsecurity or not c.relforcerowsecurity)
                order by c.relname
            ");
            $names = array_values(array_diff(array_map(fn ($r) => $r->relname, $unprotected), self::PLATFORM_TABLES));
            $this->check('Every company table has row-level security on and forced', $names === [], 'Not protected: '.implode(', ', $names));
        }

        $this->warnUnless(app()->isProduction() ? ! in_array(config('mail.default'), ['log', 'array'], true) : true, 'Mail is only being logged (MAIL_MAILER) — welcome and password-reset emails won\'t reach anyone.');
        $this->warnUnless(config('queue.default') !== 'sync', 'QUEUE_CONNECTION=sync runs pay runs inside the web request; use database (with the queue cron) or redis.');
        $this->warnUnless(! app()->isProduction() || str_starts_with((string) config('app.url'), 'https://'), 'APP_URL is not https.');
        $this->warnUnless(filled(config('services.central.secret')), 'OPSYRA_CENTRAL_SECRET is empty — sign-ups from the website can\'t create companies here.');

        return $this->finish();
    }

    private function check(string $label, bool $ok, string $fix = ''): void
    {
        if ($ok) {
            $this->line("  <fg=green>✓</> {$label}");

            return;
        }

        $this->failures++;
        $this->line("  <fg=red>✗ {$label}</>".($fix ? "\n      {$fix}" : ''));
    }

    private function warnUnless(bool $ok, string $message): void
    {
        if (! $ok) {
            $this->line("  <fg=yellow>!</> {$message}");
        }
    }

    private function finish(): int
    {
        if ($this->failures) {
            $this->error("Preflight failed: {$this->failures} problem(s). Not safe to run — fix them before deploying.");

            return self::FAILURE;
        }

        $this->info('Preflight passed.');

        return self::SUCCESS;
    }
}
