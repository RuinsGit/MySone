<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAICodeSnippetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('a_i_code_snippets', function (Blueprint $table) {
            $table->id();
            $table->string('language');
            $table->string('category');
            $table->text('code_content');
            $table->string('code_hash')->index();
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->integer('usage_count')->default(0);
            $table->float('confidence_score')->default(0.5);
            $table->json('tags')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            // İndeksler
            $table->index('language');
            $table->index('category');
            $table->index('last_used_at');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('a_i_code_snippets');
    }
} 