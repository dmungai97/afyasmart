<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('drugs', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('generic_name');
        $table->string('category');
        $table->text('uses');
        $table->text('dosage');
        $table->text('side_effects');
        $table->boolean('pregnancy_safe')->default(false);
        $table->boolean('alcohol_safe')->default(false);
        $table->boolean('lactation_safe')->default(false);
        $table->string('prescription_required')->default('Yes');
        $table->timestamps();
    });
}
};
