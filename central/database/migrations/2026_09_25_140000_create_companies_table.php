<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central's register of every company, whichever country it lives in.
 * It is the global directory (client code → country), the source of truth
 * for subscriptions (docs/research/subscription-architecture.md), and what
 * the super-admin dashboard reports on. Deliberately no employee data:
 * only the company, its plan, its admin contact, and an aggregate staff
 * count reported by the country deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->unique();
            $table->string('name');
            $table->char('country', 2);
            // provisioning → trial → active; past_due, suspended, cancelled;
            // provisioning_failed if the country deployment couldn't create it.
            $table->string('status')->default('provisioning');
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('admin_name');
            $table->string('admin_email');
            $table->string('phone')->nullable();
            $table->unsignedInteger('expected_employees')->nullable(); // what they told us at sign-up
            $table->unsignedInteger('active_employees')->nullable(); // what the country deployment reports
            $table->timestamp('usage_reported_at')->nullable();
            $table->text('provision_error')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            $table->index(['country', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
