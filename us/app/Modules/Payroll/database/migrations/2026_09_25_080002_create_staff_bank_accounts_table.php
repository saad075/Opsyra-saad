<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's employee_accounts. Account number, IBAN, routing
// number and tax id are encrypted at rest (Laravel's encrypted cast, hence
// text columns) — SLMS360 stored them in plain text.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_holder_name');
            $table->string('bank_name');
            $table->char('bank_country', 2);
            $table->text('account_number')->nullable(); // encrypted
            $table->text('iban')->nullable(); // encrypted
            $table->text('routing_number')->nullable(); // encrypted
            $table->text('tax_id')->nullable(); // encrypted
            $table->string('bank_code')->nullable();
            $table->string('swift_bic')->nullable();
            $table->char('account_currency', 3)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });

        TenantRls::enable('staff_bank_accounts');
    }

    public function down(): void
    {
        TenantRls::disable('staff_bank_accounts');
        Schema::dropIfExists('staff_bank_accounts');
    }
};
