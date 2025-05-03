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
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_title', 100)->nullable();
            $table->string('default_title', 100)->nullable();
            $table->string('title_separator', 20)->default('|');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('head_scripts')->nullable()->comment('Scripts to include in the <head> section');
            $table->text('body_start_scripts')->nullable()->comment('Scripts to include at the start of <body>');
            $table->text('body_end_scripts')->nullable()->comment('Scripts to include at the end of <body>');
            $table->text('google_analytics')->nullable();
            $table->text('google_tag_manager')->nullable();
            $table->string('robots_txt')->nullable();
            $table->string('google_verification')->nullable();
            $table->text('social_meta')->nullable()->comment('Social media metadata settings');
            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);
            $table->boolean('canonical_self')->default(true);
            $table->string('favicon')->nullable();
            $table->string('og_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
}; 