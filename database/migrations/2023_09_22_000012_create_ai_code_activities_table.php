<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAICodeActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('a_i_code_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type');
            $table->text('description');
            $table->timestamp('timestamp');
            $table->unsignedBigInteger('code_id')->nullable();
            $table->text('usage_context')->nullable();
            $table->json('related_languages')->nullable();
            $table->float('effectiveness_score')->default(0.0);
            $table->timestamps();
            
            // Yabancı anahtar
            $table->foreign('code_id')
                  ->references('id')
                  ->on('a_i_code_snippets')
                  ->onDelete('set null');
            
            // İndeksler
            $table->index('activity_type');
            $table->index('timestamp');
            $table->index('effectiveness_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('a_i_code_activities');
    }
} 