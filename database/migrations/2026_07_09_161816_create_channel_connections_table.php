<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('channel_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('platform');                          // facebook | instagram
            $table->string('provider_account_id');               // page id or IG user id
            $table->string('linked_page_id')->nullable();        // for IG-via-page
            $table->string('name');
            $table->string('username')->nullable();              // IG username
            $table->text('access_token');                        // encrypted at rest via cast
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('webhook_subscribed')->default(false);
            $table->string('status')->default('active');         // active | revoked | error
            $table->json('meta')->nullable();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['platform', 'provider_account_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_connections');
    }
};
