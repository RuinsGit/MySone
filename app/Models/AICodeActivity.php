<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AICodeActivity extends Model
{
    // Laravel 8+ ile tablo adlarını çoğul yaptığı için doğru tablo adını belirtelim
    protected $table = 'a_i_code_activities';

    protected $fillable = [
        'activity_type',
        'description',
        'timestamp',
        'code_id',
        'usage_context',
        'related_languages',
        'effectiveness_score'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'related_languages' => 'array',
        'effectiveness_score' => 'float'
    ];

    /**
     * İlgili kod kaydı ile ilişki
     */
    public function codeSnippet()
    {
        return $this->belongsTo(AICodeSnippet::class, 'code_id');
    }

    /**
     * Sistem aktivitelerini filtrele
     */
    public function scopeSystemActivities($query)
    {
        return $query->where('activity_type', 'System');
    }

    /**
     * Öğrenme aktivitelerini filtrele
     */
    public function scopeLearningActivities($query)
    {
        return $query->where('activity_type', 'Learning');
    }

    /**
     * Manuel ekleme aktivitelerini filtrele
     */
    public function scopeManualActivities($query)
    {
        return $query->where('activity_type', 'ManualAdd');
    }
    
    /**
     * Kullanım aktivitelerini filtrele
     */
    public function scopeUsageActivities($query)
    {
        return $query->where('activity_type', 'Usage');
    }

    /**
     * Son aktiviteleri getir
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('timestamp', 'desc')->limit($limit);
    }

    /**
     * Belirli bir tarih aralığındaki aktiviteleri getir
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Bugünkü aktiviteleri getir
     */
    public function scopeToday($query)
    {
        return $query->whereDate('timestamp', now()->toDateString());
    }
    
    /**
     * Kodun kullanımını kaydet
     * 
     * @param int $codeId
     * @param string $context
     * @param array $relatedLanguages
     * @param float $effectivenessScore
     * @return AICodeActivity
     */
    public static function logUsage($codeId, $context = null, $relatedLanguages = [], $effectivenessScore = 0.5)
    {
        return self::create([
            'activity_type' => 'Usage',
            'description' => 'Kod kullanıldı',
            'timestamp' => now(),
            'code_id' => $codeId,
            'usage_context' => $context,
            'related_languages' => $relatedLanguages,
            'effectiveness_score' => $effectivenessScore
        ]);
    }
    
    /**
     * Kodun etkinlik skorunu güncelle
     * 
     * @param int $codeId
     * @param float $score
     * @return bool
     */
    public static function updateEffectivenessScore($codeId, $score)
    {
        return AICodeSnippet::where('id', $codeId)->update([
            'confidence_score' => $score
        ]);
    }
    
    /**
     * Kodun kullanım istatistiklerini getir
     * 
     * @param int $codeId
     * @return array
     */
    public static function getUsageStats($codeId)
    {
        $activities = self::where('code_id', $codeId)
            ->where('activity_type', 'Usage')
            ->get();
            
        $totalUsage = $activities->count();
        $avgEffectiveness = $activities->avg('effectiveness_score') ?? 0;
        $lastUsed = $activities->max('timestamp') ?? null;
        
        if ($lastUsed) {
            $lastUsed = Carbon::parse($lastUsed);
        }
        
        $relatedLanguages = [];
        foreach ($activities as $activity) {
            if (isset($activity->related_languages) && is_array($activity->related_languages)) {
                foreach ($activity->related_languages as $lang) {
                    if (!isset($relatedLanguages[$lang])) {
                        $relatedLanguages[$lang] = 0;
                    }
                    $relatedLanguages[$lang]++;
                }
            }
        }
        
        return [
            'total_usage' => $totalUsage,
            'avg_effectiveness' => $avgEffectiveness,
            'last_used' => $lastUsed ? $lastUsed->format('Y-m-d H:i:s') : null,
            'related_languages' => $relatedLanguages
        ];
    }
    
    /**
     * Belirli bir dönemdeki en popüler kodları getir
     * 
     * @param string $period day|week|month|year
     * @param int $limit
     * @return array
     */
    public static function getMostPopularCodes($period = 'month', $limit = 10)
    {
        $startDate = null;
        
        switch ($period) {
            case 'day':
                $startDate = now()->subDay();
                break;
            case 'week':
                $startDate = now()->subWeek();
                break;
            case 'month':
                $startDate = now()->subMonth();
                break;
            case 'year':
                $startDate = now()->subYear();
                break;
            default:
                $startDate = now()->subMonth();
        }
        
        return self::where('activity_type', 'Usage')
            ->where('timestamp', '>=', $startDate)
            ->groupBy('code_id')
            ->selectRaw('code_id, count(*) as usage_count, avg(effectiveness_score) as avg_score')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                $snippet = AICodeSnippet::find($item->code_id);
                return [
                    'code_id' => $item->code_id,
                    'language' => $snippet ? $snippet->language : 'unknown',
                    'category' => $snippet ? $snippet->category : 'unknown',
                    'description' => $snippet ? $snippet->description : 'Kod bulunamadı',
                    'usage_count' => $item->usage_count,
                    'effectiveness' => $item->avg_score
                ];
            });
    }
} 