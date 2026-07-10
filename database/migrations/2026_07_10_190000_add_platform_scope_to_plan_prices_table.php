<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->string('platform_scope')->default('facebook')->after('duration_months');
        });

        // Add the new unique index before dropping the old one: the old
        // index is what currently satisfies MySQL's requirement that the
        // plan_id foreign key have a supporting index (plan_id is its
        // leftmost column), so dropping it first would fail with error 1553.
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->unique(['plan_id', 'duration_months', 'currency', 'platform_scope'], 'plan_prices_scope_unique');
        });

        Schema::table('plan_prices', function (Blueprint $table) {
            $table->dropUnique(['plan_id', 'duration_months', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->unique(['plan_id', 'duration_months', 'currency']);
        });

        Schema::table('plan_prices', function (Blueprint $table) {
            $table->dropUnique('plan_prices_scope_unique');
        });

        Schema::table('plan_prices', function (Blueprint $table) {
            $table->dropColumn('platform_scope');
        });
    }
};
