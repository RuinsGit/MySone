<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_title',
        'default_title',
        'title_separator',
        'meta_description',
        'meta_keywords',
        'head_scripts',
        'body_start_scripts',
        'body_end_scripts',
        'google_analytics',
        'google_tag_manager',
        'robots_txt',
        'google_verification',
        'social_meta',
        'noindex',
        'nofollow',
        'canonical_self',
        'favicon',
        'og_image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
        'canonical_self' => 'boolean',
    ];

    /**
     * Get global SEO settings
     *
     * @return SeoSetting
     */
    public static function global()
    {
        return self::firstOrCreate(['id' => 1]);
    }
} 