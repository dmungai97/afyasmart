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
    Schema::table('doctors', function (Blueprint $table) {
        $table->index('region');     // for WHERE region = ?
        $table->index('latitude');   // for Haversine RADIANS()
        $table->index('longitude');  // for Haversine RADIANS()
    });
}

public function down(): void
{
    Schema::table('doctors', function (Blueprint $table) {
        $table->dropIndex('doctors_region_index');
        $table->dropIndex('doctors_latitude_index');
        $table->dropIndex('doctors_longitude_index');
    });
}
};
