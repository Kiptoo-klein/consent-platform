<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $pivotRole =
            $columnNames['role_pivot_key'] ?? 'role_id';

        $pivotPermission =
            $columnNames['permission_pivot_key'] ?? 'permission_id';

        $teamForeignKey =
            $columnNames['team_foreign_key'] ?? 'organization_id';

        throw_if(
            empty($tableNames),
            'Error: config/permission.php not loaded. Run php artisan config:clear and try again.'
        );

        throw_if(
            ! $teams,
            'Error: Spatie teams must be enabled for the multi-tenant eConsent platform.'
        );

        throw_if(
            empty($teamForeignKey),
            'Error: The permission team foreign key is not configured.'
        );

        Schema::create(
            $tableNames['permissions'],
            static function (Blueprint $table): void {
                $table->id();

                $table->string('name');
                $table->string('guard_name');

                $table->timestamps();

                $table->unique([
                    'name',
                    'guard_name',
                ]);
            }
        );

        Schema::create(
            $tableNames['roles'],
            static function (Blueprint $table) use (
                $teamForeignKey
            ): void {
                $table->id();

                /*
                 * Null organization_id allows us to create global roles
                 * later if the platform requires them.
                 *
                 * Organization roles will always have an organization_id.
                 */
                $table
                    ->foreignId($teamForeignKey)
                    ->nullable()
                    ->constrained('organizations')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->string('name');
                $table->string('guard_name');

                $table->timestamps();

                $table->index(
                    $teamForeignKey,
                    'roles_organization_id_index'
                );

                $table->unique(
                    [
                        $teamForeignKey,
                        'name',
                        'guard_name',
                    ],
                    'roles_organization_name_guard_unique'
                );
            }
        );

        Schema::create(
            $tableNames['model_has_permissions'],
            static function (Blueprint $table) use (
                $tableNames,
                $columnNames,
                $pivotPermission,
                $teamForeignKey
            ): void {
                $table->unsignedBigInteger($pivotPermission);

                $table->string('model_type');

                $table->unsignedBigInteger(
                    $columnNames['model_morph_key']
                );

                $table->unsignedBigInteger($teamForeignKey);

                $table->index(
                    [
                        $columnNames['model_morph_key'],
                        'model_type',
                    ],
                    'model_has_permissions_model_index'
                );

                $table->index(
                    $teamForeignKey,
                    'model_has_permissions_organization_index'
                );

                $table
                    ->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->cascadeOnDelete();

                $table
                    ->foreign($teamForeignKey)
                    ->references('id')
                    ->on('organizations')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->primary(
                    [
                        $teamForeignKey,
                        $pivotPermission,
                        $columnNames['model_morph_key'],
                        'model_type',
                    ],
                    'model_has_permissions_primary'
                );
            }
        );

        Schema::create(
            $tableNames['model_has_roles'],
            static function (Blueprint $table) use (
                $tableNames,
                $columnNames,
                $pivotRole,
                $teamForeignKey
            ): void {
                $table->unsignedBigInteger($pivotRole);

                $table->string('model_type');

                $table->unsignedBigInteger(
                    $columnNames['model_morph_key']
                );

                $table->unsignedBigInteger($teamForeignKey);

                $table->index(
                    [
                        $columnNames['model_morph_key'],
                        'model_type',
                    ],
                    'model_has_roles_model_index'
                );

                $table->index(
                    $teamForeignKey,
                    'model_has_roles_organization_index'
                );

                $table
                    ->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->cascadeOnDelete();

                $table
                    ->foreign($teamForeignKey)
                    ->references('id')
                    ->on('organizations')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->primary(
                    [
                        $teamForeignKey,
                        $pivotRole,
                        $columnNames['model_morph_key'],
                        'model_type',
                    ],
                    'model_has_roles_primary'
                );
            }
        );

        Schema::create(
            $tableNames['role_has_permissions'],
            static function (Blueprint $table) use (
                $tableNames,
                $pivotRole,
                $pivotPermission
            ): void {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);

                $table
                    ->foreign($pivotPermission)
                    ->references('id')
                    ->on($tableNames['permissions'])
                    ->cascadeOnDelete();

                $table
                    ->foreign($pivotRole)
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->cascadeOnDelete();

                $table->primary(
                    [
                        $pivotPermission,
                        $pivotRole,
                    ],
                    'role_has_permissions_primary'
                );
            }
        );

        app('cache')
            ->store(
                config('permission.cache.store') !== 'default'
                    ? config('permission.cache.store')
                    : null
            )
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(
            empty($tableNames),
            'Error: config/permission.php not found.'
        );

        Schema::dropIfExists(
            $tableNames['role_has_permissions']
        );

        Schema::dropIfExists(
            $tableNames['model_has_roles']
        );

        Schema::dropIfExists(
            $tableNames['model_has_permissions']
        );

        Schema::dropIfExists(
            $tableNames['roles']
        );

        Schema::dropIfExists(
            $tableNames['permissions']
        );
    }
};
