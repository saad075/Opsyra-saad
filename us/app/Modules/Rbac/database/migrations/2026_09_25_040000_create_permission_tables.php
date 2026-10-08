<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Published from spatie/laravel-permission's own migration stub (v6.25, with
 * config/permission.php's 'teams' enabled and team_foreign_key set to
 * 'tenant_id' — see docs/decisions.md and RbacServiceProvider). Opsyra
 * changes from the stock migration:
 *   - roles.tenant_id (and the two pivot tables' tenant_id) are NOT NULL with
 *     a real foreign key to tenants, not a nullable unconstrained column —
 *     every role belongs to exactly one tenant, no orphans.
 *   - RLS enabled on roles, model_has_roles, model_has_permissions (the
 *     tenant-scoped tables). permissions and role_has_permissions are left
 *     alone: permissions are the app's fixed capability list (global, like a
 *     Platform table — see docs/decisions.md), and role_has_permissions only
 *     links two things that are already each individually tenant-checked.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamKey = $columnNames['team_foreign_key']; // 'tenant_id'
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if(empty($teamKey), Exception::class, 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teamKey) {
            $table->bigIncrements('id');
            $table->foreignId($teamKey)->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique([$teamKey, 'name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teamKey) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreignId($teamKey)->constrained('tenants')->cascadeOnDelete();

            $table->primary(
                [$teamKey, $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teamKey) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->foreignId($teamKey)->constrained('tenants')->cascadeOnDelete();

            $table->primary(
                [$teamKey, $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        TenantRls::enable($tableNames['roles']);
        TenantRls::enable($tableNames['model_has_roles']);
        TenantRls::enable($tableNames['model_has_permissions']);

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');

        TenantRls::disable($tableNames['model_has_permissions']);
        TenantRls::disable($tableNames['model_has_roles']);
        TenantRls::disable($tableNames['roles']);

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
