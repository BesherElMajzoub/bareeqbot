<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedTinyInteger('duration_months'); // 1 | 3 | 6 | 12
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'duration_months', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
