@extends('back.layouts.app')

@section('title', 'SEO Ayarları')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">SEO Ayarları</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">SEO Ayarları</li>
    </ol>
    
    @if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
    </div>
    @endif
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-cogs me-1"></i>
                Genel SEO Ayarları
            </div>
            <div>
                <a href="{{ route('admin.seo.preview') }}" class="btn btn-sm btn-info" target="_blank">
                    <i class="fas fa-eye me-1"></i> Önizleme
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.seo.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">
                            <i class="fas fa-sliders-h me-1"></i> Temel Ayarlar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="scripts-tab" data-bs-toggle="tab" data-bs-target="#scripts" type="button" role="tab" aria-controls="scripts" aria-selected="false">
                            <i class="fas fa-code me-1"></i> Scriptler
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab" aria-controls="analytics" aria-selected="false">
                            <i class="fas fa-chart-line me-1"></i> Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab" aria-controls="media" aria-selected="false">
                            <i class="fas fa-images me-1"></i> Medya
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false">
                            <i class="fas fa-cog me-1"></i> Gelişmiş
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content p-3 border border-top-0 rounded-bottom">
                    <!-- Temel Ayarlar -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_title" class="form-label">Site Başlığı</label>
                                <input type="text" class="form-control" id="site_title" name="site_title" value="{{ $seoSettings->site_title }}">
                                <div class="form-text">Sitenin genel başlığı.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="default_title" class="form-label">Varsayılan Sayfa Başlığı</label>
                                <input type="text" class="form-control" id="default_title" name="default_title" value="{{ $seoSettings->default_title }}">
                                <div class="form-text">Özel başlık belirtilmediğinde kullanılacak başlık.</div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="title_separator" class="form-label">Başlık Ayırıcı</label>
                                <input type="text" class="form-control" id="title_separator" name="title_separator" value="{{ $seoSettings->title_separator ?? '|' }}" maxlength="10">
                                <div class="form-text">Sayfa başlığı ile site başlığı arasında kullanılacak ayırıcı (ör: |, -, :)</div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="meta_description" class="form-label">Meta Açıklama</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3">{{ $seoSettings->meta_description }}</textarea>
                                <div class="form-text">Sitenin genel açıklaması. Google arama sonuçlarında görünür. (Önerilen: 150-160 karakter)</div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="meta_keywords" class="form-label">Meta Anahtar Kelimeler</label>
                                <textarea class="form-control" id="meta_keywords" name="meta_keywords" rows="3">{{ $seoSettings->meta_keywords }}</textarea>
                                <div class="form-text">Sitenin anahtar kelimeleri. Virgül ile ayırın.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Scriptler -->
                    <div class="tab-pane fade" id="scripts" role="tabpanel" aria-labelledby="scripts-tab">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Dikkat:</strong> Bu bölüme ekleyeceğiniz kodlar doğrudan site üzerinde çalıştırılacaktır. Lütfen güvenli kaynakları kullanın.
                        </div>
                        
                        <div class="mb-3">
                            <label for="head_scripts" class="form-label">Head Scriptleri</label>
                            <textarea class="form-control code-editor" id="head_scripts" name="head_scripts" rows="5">{{ $seoSettings->head_scripts }}</textarea>
                            <div class="form-text">Bu kodlar &lt;head&gt; bölümünün sonuna eklenecektir.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_start_scripts" class="form-label">Body Başlangıç Scriptleri</label>
                            <textarea class="form-control code-editor" id="body_start_scripts" name="body_start_scripts" rows="5">{{ $seoSettings->body_start_scripts }}</textarea>
                            <div class="form-text">Bu kodlar &lt;body&gt; etiketinden hemen sonra eklenecektir.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_end_scripts" class="form-label">Body Bitiş Scriptleri</label>
                            <textarea class="form-control code-editor" id="body_end_scripts" name="body_end_scripts" rows="5">{{ $seoSettings->body_end_scripts }}</textarea>
                            <div class="form-text">Bu kodlar &lt;/body&gt; etiketinden hemen önce eklenecektir.</div>
                        </div>
                    </div>
                    
                    <!-- Analytics -->
                    <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
                        <div class="mb-3">
                            <label for="google_analytics" class="form-label">Google Analytics Kodu</label>
                            <textarea class="form-control code-editor" id="google_analytics" name="google_analytics" rows="5" placeholder="<!-- Global site tag (gtag.js) - Google Analytics --> ...">{{ $seoSettings->google_analytics }}</textarea>
                            <div class="form-text">Google Analytics kodunuzu buraya yapıştırın.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="google_tag_manager" class="form-label">Google Tag Manager Kodu</label>
                            <textarea class="form-control code-editor" id="google_tag_manager" name="google_tag_manager" rows="5" placeholder="<!-- Google Tag Manager --> ...">{{ $seoSettings->google_tag_manager }}</textarea>
                            <div class="form-text">Google Tag Manager kodunuzu buraya yapıştırın.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="google_verification" class="form-label">Google Site Doğrulama Kodu</label>
                            <input type="text" class="form-control" id="google_verification" name="google_verification" value="{{ $seoSettings->google_verification }}" placeholder="google1234...">
                            <div class="form-text">Google Search Console doğrulama meta etiketi kodunu yazın.</div>
                        </div>
                    </div>
                    
                    <!-- Medya -->
                    <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="favicon" class="form-label">Favicon</label>
                                <input type="file" class="form-control" id="favicon" name="favicon">
                                <div class="form-text">Tarayıcı sekmelerinde gösterilecek ikon. (ICO, PNG, SVG formatları - max 5MB)</div>
                                
                                @if($seoSettings->favicon)
                                <div class="mt-2">
                                    <span class="d-block mb-1">Mevcut Favicon:</span>
                                    <img src="{{ asset($seoSettings->favicon) }}" alt="Favicon" class="img-thumbnail" style="max-width: 64px;">
                                </div>
                                @endif
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="og_image" class="form-label">Sosyal Medya Paylaşım Görseli</label>
                                <input type="file" class="form-control" id="og_image" name="og_image">
                                <div class="form-text">Sosyal medyada paylaşıldığında görünecek varsayılan görsel. (PNG, JPG formatları - önerilen: 1200x630px - max 5MB)</div>
                                
                                @if($seoSettings->og_image)
                                <div class="mt-2">
                                    <span class="d-block mb-1">Mevcut Görsel:</span>
                                    <img src="{{ asset($seoSettings->og_image) }}" alt="OG Image" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gelişmiş -->
                    <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="noindex" name="noindex" value="1" {{ $seoSettings->noindex ? 'checked' : '' }}>
                                <label class="form-check-label" for="noindex">Arama motorlarının indekslemesini engelle (noindex)</label>
                            </div>
                            <div class="form-text">Bu seçenek işaretlendiğinde, arama motorları sitenizi indekslemeyecektir.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="nofollow" name="nofollow" value="1" {{ $seoSettings->nofollow ? 'checked' : '' }}>
                                <label class="form-check-label" for="nofollow">Linkleri takip etmeyi engelle (nofollow)</label>
                            </div>
                            <div class="form-text">Bu seçenek işaretlendiğinde, arama motorları sitenizdeki linkleri takip etmeyecektir.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="canonical_self" name="canonical_self" value="1" {{ $seoSettings->canonical_self ? 'checked' : '' }}>
                                <label class="form-check-label" for="canonical_self">Otomatik canonical URL oluştur</label>
                            </div>
                            <div class="form-text">Her sayfa için otomatik olarak canonical URL ekler.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="robots_txt" class="form-label">Robots.txt İçeriği</label>
                            <textarea class="form-control code-editor" id="robots_txt" name="robots_txt" rows="5">{{ $seoSettings->robots_txt }}</textarea>
                            <div class="form-text">Robots.txt dosyasının içeriği. Bu dosya, arama motorlarının sitenizde hangi sayfaları tarayıp taramayacağını belirler.</div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i>
            SEO Önerileri
        </div>
        <div class="card-body">
            <div class="accordion" id="seoTips">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            Meta Etiketleri Hakkında
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#seoTips">
                        <div class="accordion-body">
                            <p><strong>Meta Açıklama:</strong> 150-160 karakter arasında olmalıdır. Sayfanızın içeriğini özetleyen, kullanıcıları sayfanızı ziyaret etmeye teşvik eden bilgiler içermelidir.</p>
                            <p><strong>Meta Anahtar Kelimeler:</strong> Günümüzde arama motoru sıralamasında eskisi kadar etkili olmasa da, içeriğinizle ilgili anahtar kelimeleri tanımlamanız faydalı olabilir.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Sosyal Medya Optimizasyonu
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#seoTips">
                        <div class="accordion-body">
                            <p>Sosyal medya paylaşım görseli (OG Image), siteniz sosyal medyada paylaşıldığında görünen resimdir. İdeal boyutlar:</p>
                            <ul>
                                <li>Facebook: 1200 x 630 piksel</li>
                                <li>Twitter: 1200 x 600 piksel</li>
                                <li>LinkedIn: 1200 x 627 piksel</li>
                            </ul>
                            <p>Görseliniz net, kaliteli ve içeriğinizle ilgili olmalıdır.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Google Analytics ve Tag Manager
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#seoTips">
                        <div class="accordion-body">
                            <p><strong>Google Analytics:</strong> Ziyaretçi davranışlarını takip etmek için kullanılır. Kurulumu yapmak için:</p>
                            <ol>
                                <li>Google Analytics hesabı oluşturun</li>
                                <li>Yeni bir veri akışı (data stream) ekleyin</li>
                                <li>Size verilen izleme kodunu ilgili alana yapıştırın</li>
                            </ol>
                            <p><strong>Google Tag Manager:</strong> Çeşitli etiketleri (Analytics, Facebook Piksel, vb.) yönetmek için kullanılır. Kurulum için Google Tag Manager hesabı oluşturup size verilen kodu ilgili alana yapıştırın.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            Robots.txt Dosyası
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#seoTips">
                        <div class="accordion-body">
                            <p>Robots.txt dosyası, arama motoru botlarının sitenizde hangi sayfaları tarayıp hangilerini taramayacağını belirler. Örnek bir robots.txt dosyası:</p>
                            <pre><code>User-agent: *
Allow: /
Disallow: /admin/
Disallow: /private/

Sitemap: https://www.siteadi.com/sitemap.xml</code></pre>
                            <p>Bu örnekte, tüm botların site genelinde tarama yapmasına izin verilirken, /admin/ ve /private/ dizinlerinin taranması engelleniyor.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>

<script>
    // Code Editor'lar için CodeMirror'ı başlat
    document.addEventListener('DOMContentLoaded', function() {
        const codeEditors = document.querySelectorAll('.code-editor');
        
        codeEditors.forEach(editor => {
            const cm = CodeMirror.fromTextArea(editor, {
                lineNumbers: true,
                mode: "htmlmixed",
                theme: "default",
                lineWrapping: true,
                indentUnit: 4,
                indentWithTabs: false,
                extraKeys: {"Ctrl-Space": "autocomplete"}
            });
            
            // Editör yüksekliğini ayarla
            cm.setSize(null, 150);
            
            // Değişiklik olduğunda textarea'ya yansıt
            cm.on('change', function() {
                cm.save();
            });
        });
    });
</script>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<style>
    .CodeMirror {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        height: auto;
    }
    
    .tab-content {
        background-color: #fff;
    }
    
    .code-preview {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
        font-family: monospace;
    }
    
    /* Form elemanları için bootstrap stilleri */
    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    /* Accordion stilleri */
    .accordion-button:not(.collapsed) {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    
    pre {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
    }
</style>
@endsection 