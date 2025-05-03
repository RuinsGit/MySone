@extends('back.layouts.app')

@section('title', 'SEO Önizleme')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">SEO Önizleme</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.seo.index') }}">SEO Ayarları</a></li>
        <li class="breadcrumb-item active">Önizleme</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-eye me-1"></i>
                Google Arama Sonucu Önizleme
            </div>
            <div>
                <a href="{{ route('admin.seo.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> SEO Ayarlarına Dön
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="google-preview p-3 border rounded mb-4">
                <div class="preview-title text-primary fw-bold mb-1">
                    {{ $seoSettings->site_title }} {{ $seoSettings->title_separator }} {{ $seoSettings->default_title }}
                </div>
                <div class="preview-url text-success small mb-1">
                    {{ url('/') }}
                </div>
                <div class="preview-description text-muted small">
                    {{ \Illuminate\Support\Str::limit($seoSettings->meta_description, 160) }}
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Bu önizleme, Google arama sonuçlarında nasıl görüneceğinize dair yaklaşık bir gösterimdir. Gerçek sonuçlar farklılık gösterebilir.
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-share-alt me-1"></i>
            Sosyal Medya Önizleme
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fab fa-facebook me-1"></i> Facebook
                        </div>
                        <div class="card-body p-0">
                            <div class="facebook-preview border">
                                @if($seoSettings->og_image)
                                    <div class="fb-image bg-light text-center py-2">
                                        <img src="{{ asset($seoSettings->og_image) }}" alt="OG Image" class="img-fluid" style="max-height: 300px;">
                                    </div>
                                @else
                                    <div class="fb-image bg-light text-center py-5">
                                        <i class="fas fa-image fa-4x text-muted"></i>
                                        <p class="mt-2 text-muted">Görsel Tanımlanmamış</p>
                                    </div>
                                @endif
                                <div class="fb-content p-3">
                                    <div class="fb-url text-muted small mb-1">{{ url('/') }}</div>
                                    <div class="fb-title fw-bold mb-1">{{ $seoSettings->site_title }}</div>
                                    <div class="fb-description small text-muted">
                                        {{ \Illuminate\Support\Str::limit($seoSettings->meta_description, 100) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <i class="fab fa-twitter me-1"></i> Twitter
                        </div>
                        <div class="card-body p-0">
                            <div class="twitter-preview border">
                                @if($seoSettings->og_image)
                                    <div class="tw-image bg-light text-center py-2">
                                        <img src="{{ asset($seoSettings->og_image) }}" alt="OG Image" class="img-fluid" style="max-height: 250px; border-radius: 15px;">
                                    </div>
                                @else
                                    <div class="tw-image bg-light text-center py-5">
                                        <i class="fas fa-image fa-4x text-muted"></i>
                                        <p class="mt-2 text-muted">Görsel Tanımlanmamış</p>
                                    </div>
                                @endif
                                <div class="tw-content p-3">
                                    <div class="tw-title fw-bold mb-1">{{ $seoSettings->site_title }}</div>
                                    <div class="tw-description small text-muted mb-1">
                                        {{ \Illuminate\Support\Str::limit($seoSettings->meta_description, 80) }}
                                    </div>
                                    <div class="tw-url text-muted small">{{ url('/') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-code me-1"></i>
            HTML Meta Etiketleri Önizleme
        </div>
        <div class="card-body">
            <pre class="code-preview"><code class="language-html">&lt;!-- Temel Meta Etiketleri --&gt;
&lt;title&gt;{{ $seoSettings->site_title }} {{ $seoSettings->title_separator }} {{ $seoSettings->default_title }}&lt;/title&gt;
&lt;meta name="description" content="{{ $seoSettings->meta_description }}"&gt;
@if($seoSettings->meta_keywords)&lt;meta name="keywords" content="{{ $seoSettings->meta_keywords }}"&gt;@endif

@if($seoSettings->canonical_self)&lt;link rel="canonical" href="{{ url()->current() }}"&gt;@endif

@if($seoSettings->noindex || $seoSettings->nofollow)&lt;meta name="robots" content="{{ $seoSettings->noindex ? 'noindex' : 'index' }},{{ $seoSettings->nofollow ? 'nofollow' : 'follow' }}"&gt;@endif

@if($seoSettings->google_verification)&lt;meta name="google-site-verification" content="{{ $seoSettings->google_verification }}"&gt;@endif

&lt;!-- Favicon --&gt;
@if($seoSettings->favicon)&lt;link rel="icon" href="{{ asset($seoSettings->favicon) }}" type="image/x-icon"&gt;@endif

&lt;!-- Open Graph / Facebook Meta Etiketleri --&gt;
&lt;meta property="og:type" content="website"&gt;
&lt;meta property="og:url" content="{{ url()->current() }}"&gt;
&lt;meta property="og:title" content="{{ $seoSettings->site_title }}"&gt;
&lt;meta property="og:description" content="{{ $seoSettings->meta_description }}"&gt;
@if($seoSettings->og_image)&lt;meta property="og:image" content="{{ asset($seoSettings->og_image) }}"&gt;@endif

&lt;!-- Twitter Meta Etiketleri --&gt;
&lt;meta name="twitter:card" content="summary_large_image"&gt;
&lt;meta name="twitter:url" content="{{ url()->current() }}"&gt;
&lt;meta name="twitter:title" content="{{ $seoSettings->site_title }}"&gt;
&lt;meta name="twitter:description" content="{{ $seoSettings->meta_description }}"&gt;
@if($seoSettings->og_image)&lt;meta name="twitter:image" content="{{ asset($seoSettings->og_image) }}"&gt;@endif</code></pre>
        </div>
    </div>
    
    @if($seoSettings->robots_txt)
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-robot me-1"></i>
            Robots.txt Önizleme
        </div>
        <div class="card-body">
            <pre class="code-preview"><code class="language-txt">{{ $seoSettings->robots_txt }}</code></pre>
        </div>
    </div>
    @endif
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.2.0/styles/github.min.css">
<style>
    .google-preview {
        font-family: Arial, sans-serif;
        max-width: 600px;
    }
    
    .preview-title {
        font-size: 18px;
    }
    
    .preview-url {
        font-size: 14px;
    }
    
    .preview-description {
        font-size: 14px;
        line-height: 1.4;
    }
    
    .facebook-preview, .twitter-preview {
        font-family: Arial, sans-serif;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .fb-image, .tw-image {
        position: relative;
        overflow: hidden;
    }
    
    .code-preview {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
        font-family: monospace;
        overflow: auto;
        white-space: pre-wrap;
    }
</style>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.2.0/highlight.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kod bloklarını vurgula
        document.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightBlock(block);
        });
    });
</script>
@endsection 