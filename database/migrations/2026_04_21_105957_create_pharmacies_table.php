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
    Schema::create('pharmacies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('location');
        $table->string('address');
        $table->string('phone');
        $table->string('email')->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->string('opening_hours')->default('Mon-Sat 8am-8pm');
        $table->boolean('open_24hrs')->default(false);
        $table->boolean('open')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
