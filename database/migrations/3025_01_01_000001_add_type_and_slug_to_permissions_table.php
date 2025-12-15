<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) {
                $table->string('type')->default('custom')->after('name');
                $table->string('slug')->unique()->after('type');
            });
        }

        if (Schema::hasTable($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (Schema::hasTable($tableNames['permissions'])) {
            Schema::table($tableNames['permissions'], function (Blueprint $table) {
                $table->dropColumn('type');
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasTable($tableNames['roles'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
