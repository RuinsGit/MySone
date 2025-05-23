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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('metadata');
            $table->text('device_info')->nullable()->after('ip_address');
        });

        // Ziyaretçi adlarını saklamak için yeni bir tablo oluştur
        Schema::create('visitor_names', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id')->unique();
            $table->string('name');
            $table->string('ip_address')->nullable();
            $table->text('device_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('ip_address');
            $table->dropColumn('device_info');
        });

        Schema::dropIfExists('visitor_names');
    }
}; 