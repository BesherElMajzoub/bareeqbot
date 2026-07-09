<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Marks Bariq staff (super_admin / support) vs tenant customers.
            $table->boolean('is_platform_staff')->default(false)->after('email');
            // The tenant the user is currently acting within (multi-tenant members).
            $table->foreignId('current_tenant_id')->nullable()->after('is_platform_staff');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_platform_staff', 'current_tenant_id']);
        });
    }
};
