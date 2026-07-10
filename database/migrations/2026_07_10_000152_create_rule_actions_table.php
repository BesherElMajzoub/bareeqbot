<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('action_type');                 // public_reply | private_reply | dm
            $table->text('message_template');
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['rule_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_actions');
    }
};
