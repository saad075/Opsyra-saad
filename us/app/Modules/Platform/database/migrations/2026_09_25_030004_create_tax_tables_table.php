<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform table — not tenant-scoped. Shared per country deployment; the US
 * federal payroll module (US deployment only, behind a feature flag) is the
 * first consumer, replacing SLMS360's cached tax-table tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_tables', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2);
            $table->unsignedSmallInteger('tax_year');
            $table->string('jurisdiction'); // e.g. 'federal', or a state/province code
            $table->json('payload');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['country', 'tax_year', 'jurisdiction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_tables');
    }
};
