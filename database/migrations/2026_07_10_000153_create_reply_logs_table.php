<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reply_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('channel_connection_id')->constrained('channel_connections')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('automation_rules')->nullOnDelete();
            $table->string('platform');
            $table->string('surface');                     // post_comment | story_reply | story_mention
            $table->string('source_object_id');            // comment/message id
            $table->string('actor_id')->nullable();        // commenter/sender id
            $table->string('action_type');                 // public_reply | private_reply | dm
            $table->string('status')->default('sent');     // sent | failed | skipped | deduped
            $table->text('error')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Primary idempotency guard: never reply twice to the same object with the same action.
            $table->unique(['platform', 'source_object_id', 'action_type']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reply_logs');
    }
};
