<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('platform');                        // page | instagram (Meta `object`)
            $table->string('object_type')->nullable();         // the Meta object
            $table->string('object_id')->nullable();           // entry id (asset id)
            $table->boolean('signature_valid')->default(false);
            $table->json('raw_payload');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('received');     // received | processed | failed | skipped
            $table->timestamps();

            $table->index(['status', 'received_at']);
            $table->index(['object_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
