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
        Schema::table('visitor_names', function (Blueprint $table) {
            if (!Schema::hasColumn('visitor_names', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            
            if (!Schema::hasColumn('visitor_names', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            
            if (!Schema::hasColumn('visitor_names', 'location_info')) {
                $table->json('location_info')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_names', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_info']);
        });
    }
}; 