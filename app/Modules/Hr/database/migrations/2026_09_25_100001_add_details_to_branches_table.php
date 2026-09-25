<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Branch management was a known gap (the HR core module only created the
// table as a staff dependency); the Setup wizard needs it. SLMS360's branches
// carried a timezone (see its commented-out date_default_timezone_set use in
// SalaryController) — kept here for attendance and scheduled jobs later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('timezone')->nullable(); // null = the company's timezone
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['address', 'city', 'phone', 'timezone']);
        });
    }
};
