<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A throwaway table used only to exercise the BelongsToTenant trait in tests.
 * It is created solely in the testing environment and never in dev/production.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        Schema::create('tenancy_test_fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_test_fixtures');
    }
};
