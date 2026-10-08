<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "File" quick action — attachments on a lead. Stored on the `local`
 * (private) disk under a random, non-guessable filename, not the public
 * disk leads/branches avatars use — these can be real business documents
 * (contracts, quotes a tenant uploads), so they're served only through
 * LeadFileController::download() after the same tenant/permission checks
 * every other CRM write goes through, never a public, permanently-guessable
 * URL. `original_name` is for display only; it's never used as the stored
 * path (classic traversal/overwrite footgun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id']);
        });

        TenantRls::enable('lead_files');
    }

    public function down(): void
    {
        TenantRls::disable('lead_files');
        Schema::dropIfExists('lead_files');
    }
};
