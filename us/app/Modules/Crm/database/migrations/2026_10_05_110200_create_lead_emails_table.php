<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "Mail" quick action — a log of emails sent to a lead's contact
 * through LeadMailController, which actually sends via Laravel's Mail
 * facade (App\Modules\Crm\Mail\LeadEmail), not just a fake record. In this
 * dev environment MAIL_MAILER=log, so sends land in the log file rather
 * than a real inbox — same as every other outbound email in Opsyra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->text('body');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id']);
        });

        TenantRls::enable('lead_emails');
    }

    public function down(): void
    {
        TenantRls::disable('lead_emails');
        Schema::dropIfExists('lead_emails');
    }
};
