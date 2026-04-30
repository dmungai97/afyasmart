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
    Schema::create('doctors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('specialization');
        $table->string('hospital');
        $table->string('location');
        $table->string('phone');
        $table->string('email')->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->integer('experience_years');
        $table->decimal('rating', 3, 1)->default(0.0);
        $table->string('availability')->default('Mon-Fri 8am-5pm');
        $table->boolean('available')->default(true);
        $table->timestamps();
    });
}
};
