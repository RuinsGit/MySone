<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AICodeSnippet extends Model
{
    // Laravel 8+ ile tablo adlarını çoğul yaptığı için doğru tablo adını belirtelim
    protected $table = 'a_i_code_snippets';

    protected $fillable = [
        'language',
        'category',
        'code_content',
        'code_hash',
        'description',
        'metadata',
        'usage_count',
        'confidence_score',
        'tags',
        'last_used_at',
        'is_featured'
    ];

    protected $casts = [
        'metadata' => 'array',
        'usage_count' => 'integer',
        'confidence_score' => 'float',
        'tags' => 'array',
        'last_used_at' => 'datetime',
        'is_featured' => 'boolean'
    ];

    /**
     * Model oluşturulmadan önce yapılacak işlemler
     */
    protected static function boot()
    {
        parent::boot();
        
        // Kaydetmeden önce eksik değerleri doldur
        static::creating(function ($model) {
            if (empty($model->category)) {
                $model->category = 'snippet';
            }
            
            if (empty($model->usage_count)) {
                $model->usage_count = 0;
            }
            
            if (empty($model->confidence_score)) {
                $model->confidence_score = 0.5;
            }
            
            if (empty($model->tags)) {
                $model->tags = [];
            }
        });
    }

    /**
     * Belirli bir dile ait kod parçalarını getir
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Belirli bir kategoriye ait kod parçalarını getir
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Sık kullanılan kod parçalarını getir
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Yüksek güven skoruna sahip kod parçalarını getir
     */
    public function scopeConfident($query, $minScore = 0.7)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }

    /**
     * İşlev kategorisindeki kodları getir
     */
    public function scopeFunctions($query)
    {
        return $query->where('category', 'function');
    }

    /**
     * Sınıf kategorisindeki kodları getir
     */
    public function scopeClasses($query)
    {
        return $query->where('category', 'class');
    }
    
    /**
     * HTML kodlarını getir
     */
    public function scopeHtml($query)
    {
        return $query->where('language', 'html');
    }
    
    /**
     * CSS kodlarını getir
     */
    public function scopeCss($query)
    {
        return $query->where('language', 'css');
    }
    
    /**
     * Öne çıkarılan kodları getir
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Son kullanılan kodları getir
     */
    public function scopeRecentlyUsed($query, $limit = 10)
    {
        return $query->whereNotNull('last_used_at')
            ->orderBy('last_used_at', 'desc')
            ->limit($limit);
    }

    /**
     * Belirli bir metni içeren kodları arama
     */
    public function scopeSearch($query, $searchText)
    {
        return $query->where(function ($q) use ($searchText) {
            $q->where('code_content', 'like', "%$searchText%")
              ->orWhere('description', 'like', "%$searchText%");
        });
    }
    
    /**
     * Etiketlere göre kod parçalarını filtrele
     */
    public function scopeWithTags($query, $tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Kod aktiviteleri ile ilişki
     */
    public function activities()
    {
        return $this->hasMany(AICodeActivity::class, 'code_id');
    }
    
    /**
     * Kodu kullan ve kullanım istatistiklerini güncelle
     * 
     * @param string $context Kodun kullanıldığı bağlam
     * @param array $relatedLanguages İlişkili diller
     * @param float $effectivenessScore Etkinlik skoru
     * @return bool
     */
    public function useCode($context = null, $relatedLanguages = [], $effectivenessScore = 0.5)
    {
        // Kullanım sayısını artır
        $this->increment('usage_count');
        
        // Son kullanım zamanını güncelle
        $this->update(['last_used_at' => now()]);
        
        // Aktivite kaydı oluştur
        AICodeActivity::logUsage($this->id, $context, $relatedLanguages, $effectivenessScore);
        
        return true;
    }
    
    /**
     * Koda etiket ekle
     * 
     * @param string|array $tags Eklenecek etiketler
     * @return bool
     */
    public function addTags($tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        
        $currentTags = $this->tags ?? [];
        $newTags = array_unique(array_merge($currentTags, $tags));
        
        return $this->update(['tags' => $newTags]);
    }
    
    /**
     * Kodun güven skorunu güncelle
     * 
     * @param float $score Yeni güven skoru
     * @return bool
     */
    public function updateConfidence($score)
    {
        return $this->update(['confidence_score' => $score]);
    }
    
    /**
     * Kodu öne çıkar/çıkarma
     * 
     * @param bool $featured Öne çıkarma durumu
     * @return bool
     */
    public function setFeatured($featured = true)
    {
        return $this->update(['is_featured' => $featured]);
    }
    
    /**
     * Belirli bir kategorideki kodları sayılarıyla birlikte getir
     * 
     * @return array
     */
    public static function getLanguageStats()
    {
        return self::selectRaw('language, count(*) as count')
            ->groupBy('language')
            ->orderBy('count', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->language => $item->count];
            })
            ->toArray();
    }
    
    /**
     * Belirli bir dildeki kod kategorilerini sayılarıyla birlikte getir
     * 
     * @param string $language
     * @return array
     */
    public static function getCategoryStats($language = null)
    {
        $query = self::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc');
            
        if ($language) {
            $query->where('language', $language);
        }
        
        return $query->get()
            ->mapWithKeys(function ($item) {
                return [$item->category => $item->count];
            })
            ->toArray();
    }
    
    /**
     * Benzer kodları bul
     * 
     * @param string $code Aranan kod
     * @param string $language Kod dili
     * @param int $limit Sonuç limiti
     * @return Collection
     */
    public static function findSimilarCodes($code, $language = null, $limit = 5)
    {
        $query = self::query();
        
        if ($language) {
            $query->where('language', $language);
        }
        
        // Basit benzerlikleri bul (tam eşleşme olmadan)
        // Gerçek uygulamada daha gelişmiş algoritmalar kullanılabilir
        $words = preg_split('/\s+/', $code);
        $words = array_filter($words, function($word) {
            return strlen($word) > 3; // Kısa kelimeleri filtrele
        });
        
        if (count($words) > 0) {
            $query->where(function($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('code_content', 'like', "%$word%");
                }
            });
        }
        
        return $query->orderBy('confidence_score', 'desc')
            ->limit($limit)
            ->get();
    }
} 