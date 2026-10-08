<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The global directory: client_code -> which country deployment to talk to.
 * Deliberately tiny and free of personal data (no names, emails, tenant
 * details) — see docs/decisions.md. In a real deployment this table (and the
 * /api/resolve endpoint reading it) could live in its own small service
 * outside the four country apps; for now it's a self-contained module here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_directory', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->unique();
            $table->string('country', 2);
            $table->string('endpoint_url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_directory');
    }
};
