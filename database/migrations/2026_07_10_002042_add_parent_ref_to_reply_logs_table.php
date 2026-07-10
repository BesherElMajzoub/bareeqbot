<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reply_logs', function (Blueprint $table) {
            // Post/media id (comments) or story reference (story reply/mention) for analytics.
            $table->string('parent_ref')->nullable()->after('actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('reply_logs', function (Blueprint $table) {
            $table->dropColumn('parent_ref');
        });
    }
};
