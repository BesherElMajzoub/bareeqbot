<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->constrained('channel_connections')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_surface');                 // post_comment | story_reply | story_mention
            $table->string('target_scope')->default('all');    // all | specific (comments only)
            $table->string('target_ref')->nullable();          // post/media id when scope=specific
            $table->string('match_type')->default('any');      // any | exact | contains | regex
            $table->string('keyword')->nullable();
            $table->boolean('case_sensitive')->default(false);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['channel_connection_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
