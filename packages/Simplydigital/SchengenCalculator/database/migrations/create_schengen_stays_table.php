<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schengen_stays', function (Blueprint $table) {
            $table->id();

            $table->morphs('traveller');

            $table->date('entry_date');
            $table->date('exit_date')->nullable();

            // Country where the traveller was physically present.
            $table->char('country_code', 2)->nullable();

            // short_stay, residence_permit or long_stay_visa
            $table->string('type', 30)->default('short_stay');

            // Country that issued the residence permit or long-stay visa.
            $table->char('permit_country_code', 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['traveller_type', 'traveller_id', 'entry_date'],
                'schengen_stays_traveller_entry_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schengen_stays');
    }
};