<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'secretary', 'technical_head', 'technician', 'staff') NOT NULL DEFAULT 'staff'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'technician')->update(['role' => 'staff']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'secretary', 'technical_head', 'staff') NOT NULL DEFAULT 'staff'");
    }
};