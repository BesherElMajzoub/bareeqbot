<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE plan_prices MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'SYP'");
        DB::statement("ALTER TABLE subscriptions MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'SYP'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE plan_prices MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'SAR'");
        DB::statement("ALTER TABLE subscriptions MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'SAR'");
    }
};
