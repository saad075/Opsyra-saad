<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The country deployment's local enforcement copy of a company's
 * subscription, pushed from Opsyra Central — which is the source of truth
 * (docs/research/subscription-architecture.md). Kept on the tenant row
 * itself: `status` already exists; this adds the trial end, the plan, the
 * features it unlocks, and whether this is the public demo company.
 *
 * The scaffold's separate `plans`/`subscriptions` tables predate that
 * decision and stay unused for now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('plan_code')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_demo')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'plan_code', 'features', 'is_demo']);
        });
    }
};
