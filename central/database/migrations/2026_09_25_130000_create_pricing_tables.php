<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pricing is tiered plans × per-country prices, each price being three
 * numbers the super admin sets (user, 2026-09-25):
 *
 *   monthly = base_price + max(0, employees − included_employees) × extra_employee_price
 *
 * e.g. "from $15/month for up to 10 employees, $1 per additional employee".
 * A country's prices are only shown publicly once published.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Central's users are BrainStormers staff (super admins), never
            // tenant staff — those live in the country deployments.
            $table->boolean('is_active')->default(true);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // stable key, e.g. base, plus, premium
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_code'); // a key of config('opsyra.features')
            $table->timestamps();

            $table->unique(['plan_id', 'feature_code']);
        });

        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->char('country', 2);
            $table->char('currency', 3);
            // decimal(12,3): three places for OMR's baisa, as in the country apps.
            $table->decimal('base_price', 12, 3);
            $table->unsignedInteger('included_employees');
            $table->decimal('extra_employee_price', 12, 3);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plan_id', 'country']);
        });

        Schema::create('country_pricing', function (Blueprint $table) {
            $table->char('country', 2)->primary();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_pricing');
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
