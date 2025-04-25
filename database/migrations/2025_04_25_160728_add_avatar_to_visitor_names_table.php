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
            // Eğer zaten eklenmediyse, avatar ve user_id sütunlarını ekle
            if (!Schema::hasColumn('visitor_names', 'avatar')) {
                $table->string('avatar')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('visitor_names', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('avatar');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_names', function (Blueprint $table) {
            // Foreign key kısıtlamasını kaldır
            if (Schema::hasColumn('visitor_names', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            
            if (Schema::hasColumn('visitor_names', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });
    }
};
