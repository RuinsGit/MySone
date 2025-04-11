<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_coding', function (Blueprint $table) {
            $table->id();
            $table->text('code_content');           // Kod içeriği
            $table->string('language');             // Programlama dili
            $table->string('category');             // Kod kategorisi (örn: fonksiyon, class, algoritma)
            $table->text('description')->nullable(); // Kod açıklaması
            $table->text('usage_example')->nullable(); // Kullanım örneği
            $table->json('parameters')->nullable();    // Parametreler
            $table->json('dependencies')->nullable();  // Bağımlılıklar
            $table->float('complexity')->default(0);   // Kod karmaşıklığı
            $table->integer('usage_count')->default(0); // Kullanım sayısı
            $table->float('success_rate')->default(0);  // Başarı oranı
            $table->json('tags')->nullable();           // Etiketler
            $table->boolean('is_tested')->default(false); // Test edildi mi?
            $table->json('test_results')->nullable();     // Test sonuçları
            $table->timestamps();
            
            // İndeksler
            $table->index('language');
            $table->index('category');
        });

        Schema::create('ai_coding_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('pattern_name');         // Pattern adı
            $table->text('pattern_description');    // Pattern açıklaması
            $table->text('code_template');          // Kod şablonu
            $table->string('language');             // Programlama dili
            $table->json('variables')->nullable();  // Değişkenler
            $table->json('use_cases')->nullable(); // Kullanım senaryoları
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('pattern_name');
            $table->index('language');
        });

        Schema::create('ai_coding_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->text('context');                // Oturum bağlamı
            $table->json('conversation_history');   // Konuşma geçmişi
            $table->json('code_snippets');          // Kod parçacıkları
            $table->string('active_language');      // Aktif programlama dili
            $table->json('variables')->nullable();  // Oturum değişkenleri
            $table->timestamps();
            
            $table->index('session_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_coding_sessions');
        Schema::dropIfExists('ai_coding_patterns');
        Schema::dropIfExists('ai_coding');
    }
}; 