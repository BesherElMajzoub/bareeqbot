<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->nullable()->constrained('channel_connections')->nullOnDelete();
            $table->date('date');
            $table->unsignedInteger('events_received')->default(0);
            $table->unsignedInteger('replies_sent')->default(0);
            $table->unsignedInteger('dms_sent')->default(0);
            $table->unsignedInteger('failures')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'channel_connection_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stats');
    }
};
