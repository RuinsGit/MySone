@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="alert alert-info">
    <div id="js-test-result">JavaScript çalışıyor mu? Bu mesaj değişirse evet.</div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4>Kod Bilinç Sistemi - Gerçek Zamanlı İşleme</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Bilinç Sistemi Durumu</h5>
                                </div>
                                <div class="card-body">
                                    <div id="consciousness-status">
                                        <div class="alert alert-info">Bilinç sistemi durumu yükleniyor...</div>
                                    </div>
                                    <div class="d-flex mt-3">
                                        <button id="activate-btn" class="btn btn-success me-2">Bilinci Aktifleştir</button>
                                        <button id="deactivate-btn" class="btn btn-danger me-2">Bilinci Devre Dışı Bırak</button>
                                        <button id="think-btn" class="btn btn-primary">Düşünmeyi Tetikle</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>İşleme Kontrolü</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex mb-3">
                                        <button id="process-single-btn" class="btn btn-primary me-2">Tek Kod İşle</button>
                                        <button id="process-batch-btn" class="btn btn-info me-2">Toplu İşle</button>
                                        <button id="reset-processed-btn" class="btn btn-warning">Sıfırla</button>
                                    </div>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">Toplu İşleme Sayısı</span>
                                        <input type="number" id="batch-count" class="form-control" value="5" min="1" max="50">
                                        <button id="start-batch-btn" class="btn btn-success">Başlat</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>İşleme Durumu</h5>
                                </div>
                                <div class="card-body">
                                    <div id="processing-status">
                                        <div class="alert alert-info">İşleme durumu yükleniyor...</div>
                                    </div>
                                    <div class="progress mb-3">
                                        <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5>Gerçek Zamanlı İşlenen Kodlar</h5>
                                    <button id="refresh-processed-btn" class="btn btn-sm btn-outline-primary">Yenile</button>
                                </div>
                                <div class="card-body">
                                    <div id="processed-codes-list" style="max-height: 400px; overflow-y: auto;">
                                        <div class="alert alert-info">İşlenen kodlar yükleniyor...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Son İşlenen Kod Detayı</h5>
                                </div>
                                <div class="card-body">
                                    <div id="current-code-detail">
                                        <div class="alert alert-info">Henüz bir kod işlenmedi veya seçilmedi.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// JavaScript yüklendi mi kontrol et
document.getElementById('js-test-result').textContent = 'JavaScript çalışıyor!';

// jQuery yüklendi mi kontrol et
if (typeof $ === 'undefined') {
    document.getElementById('js-test-result').textContent = 'JavaScript çalışıyor, fakat jQuery yüklenmemiş!';
    // jQuery olmadan çalışmaya devam etme
    console.error('jQuery yüklenmemiş!');
} else {
    $('#js-test-result').text('JavaScript ve jQuery çalışıyor!');
    console.log('jQuery sürümü:', $.fn.jquery);
}

$(document).ready(function() {
    console.log('Sayfa hazır. DOM yüklendi.');
    
    // Butonları listele
    console.log('Butonlar:');
    console.log('- #activate-btn mevcut:', $('#activate-btn').length > 0);
    console.log('- #deactivate-btn mevcut:', $('#deactivate-btn').length > 0);
    console.log('- #think-btn mevcut:', $('#think-btn').length > 0);
    
    // Buton olaylarını ekle
    $('#activate-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Aktivasyon butonuna tıklandı');
        activateConsciousness();
    });
    
    $('#deactivate-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Deaktivasyon butonuna tıklandı');
        deactivateConsciousness();
    });
    
    $('#think-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Düşünme butonuna tıklandı');
        triggerThinking();
    });
    
    $('#process-single-btn').on('click', function(e) {
        e.preventDefault();
        processSingleCode();
    });
    
    $('#process-batch-btn').on('click', function(e) {
        e.preventDefault();
        processBatchCodes();
    });
    
    $('#reset-processed-btn').on('click', function(e) {
        e.preventDefault();
        resetProcessedCodes();
    });
    
    // Bilinç durumunu yükle
    function loadConsciousnessStatus() {
        console.log('Bilinç durumu yükleme isteği gönderiliyor...');
        $.ajax({
            url: '/api/ai/code-consciousness/status',
            method: 'GET',
            success: function(response) {
                console.log('Bilinç durumu başarıyla alındı:', response);
                if (response.success) {
                    let status = response.consciousness_status;
                    let isActive = status.is_active;
                    let level = status.consciousness_level;
                    
                    let html = `
                        <div class="alert ${isActive ? 'alert-success' : 'alert-warning'}">
                            <h6>Durum: ${isActive ? 'Aktif' : 'Devre Dışı'}</h6>
                            <p>Bilinç Seviyesi: ${level}/10</p>
                            <p>İşlenen Kod Sayısı: ${status.processed_codes_count} / ${status.total_codes}</p>
                            <p>Son Düşünme Zamanı: ${status.last_thinking_time}</p>
                        </div>
                    `;
                    
                    $('#consciousness-status').html(html);
                    
                    // Butonları güncelle
                    if (isActive) {
                        $('#activate-btn').prop('disabled', true);
                        $('#deactivate-btn').prop('disabled', false);
                        $('#think-btn').prop('disabled', false);
                        $('#process-single-btn').prop('disabled', false);
                        $('#process-batch-btn').prop('disabled', false);
                        $('#start-batch-btn').prop('disabled', false);
                    } else {
                        $('#activate-btn').prop('disabled', false);
                        $('#deactivate-btn').prop('disabled', true);
                        $('#think-btn').prop('disabled', true);
                        $('#process-single-btn').prop('disabled', true);
                        $('#process-batch-btn').prop('disabled', true);
                        $('#start-batch-btn').prop('disabled', true);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Bilinç durumu alınamadı:', {xhr: xhr, status: status, error: error, responseText: xhr.responseText});
                $('#consciousness-status').html('<div class="alert alert-danger">Bilinç durumu alınamadı. Hata: ' + error + '</div>');
            }
        });
    }
    
    // İşleme durumunu yükle
    function loadProcessingStatus() {
        console.log('İşleme durumu yükleme isteği gönderiliyor...');
        $.ajax({
            url: '/api/ai/code-consciousness/processing-status',
            method: 'GET',
            success: function(response) {
                console.log('İşleme durumu başarıyla alındı:', response);
                if (response.success) {
                    let status = response.processing_status;
                    let consciousness = response.consciousness_status;
                    
                    let totalCodes = status.total_codes;
                    let processedCodes = status.processed_codes;
                    let currentlyProcessing = status.currently_processing;
                    
                    // İlerleme çubuğunu güncelle
                    let progressPercent = totalCodes > 0 ? Math.round((processedCodes / totalCodes) * 100) : 0;
                    $('#progress-bar').css('width', progressPercent + '%');
                    $('#progress-bar').attr('aria-valuenow', progressPercent);
                    $('#progress-bar').text(progressPercent + '%');
                    
                    let html = `
                        <div class="alert alert-info">
                            <h6>Toplam Kod: ${totalCodes}</h6>
                            <p>İşlenen Kod: ${processedCodes}</p>
                            <p>Şu Anda İşlenen: ${currentlyProcessing ? 'Kod #' + currentlyProcessing : 'Yok'}</p>
                            <p>Kalan: ${totalCodes - processedCodes}</p>
                        </div>
                    `;
                    
                    $('#processing-status').html(html);
                }
            },
            error: function(xhr, status, error) {
                console.error('İşleme durumu alınamadı:', {xhr: xhr, status: status, error: error, responseText: xhr.responseText});
                $('#processing-status').html('<div class="alert alert-danger">İşleme durumu alınamadı. Hata: ' + error + '</div>');
            }
        });
    }
    
    // Son işlenen kodları yükle
    function loadRecentProcessedCodes() {
        console.log('Son işlenen kodlar yükleme isteği gönderiliyor...');
        $.ajax({
            url: '/api/ai/code-consciousness/recent-processed',
            method: 'GET',
            success: function(response) {
                console.log('Son işlenen kodlar başarıyla alındı:', response);
                if (response.success) {
                    let recentCodes = response.recently_processed;
                    let html = '';
                    
                    if (recentCodes.length === 0) {
                        html = '<div class="alert alert-info">Henüz işlenmiş kod yok.</div>';
                    } else {
                        html = '<div class="list-group">';
                        recentCodes.forEach(function(item) {
                            let code = item.code;
                            let status = item.processing_status;
                            
                            let statusBadge = '';
                            if (status.status === 'completed') {
                                statusBadge = '<span class="badge bg-success">Tamamlandı</span>';
                            } else if (status.status === 'processing') {
                                statusBadge = '<span class="badge bg-info">İşleniyor</span>';
                            } else if (status.status === 'error') {
                                statusBadge = '<span class="badge bg-danger">Hata</span>';
                            }
                            
                            html += `
                                <a href="#" class="list-group-item list-group-item-action code-item" data-code-id="${code.id}">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Kod #${code.id}: ${code.language}</h6>
                                        ${statusBadge}
                                    </div>
                                    <p class="mb-1">${code.category || 'Kategorisiz'}</p>
                                    <small>${status.timestamp}</small>
                                </a>
                            `;
                        });
                        html += '</div>';
                    }
                    
                    $('#processed-codes-list').html(html);
                }
            },
            error: function(xhr, status, error) {
                console.error('İşlenen kodlar alınamadı:', {xhr: xhr, status: status, error: error, responseText: xhr.responseText});
                $('#processed-codes-list').html('<div class="alert alert-danger">İşlenen kodlar alınamadı. Hata: ' + error + '</div>');
            }
        });
    }
    
    // Kod detayını göster
    function showCodeDetail(codeId) {
        $.ajax({
            url: '/api/ai/code-learning/code-example',
            method: 'GET',
            data: { id: codeId },
            success: function(response) {
                if (response.success) {
                    let code = response.code;
                    
                    let html = `
                        <div class="card">
                            <div class="card-header">
                                <h6>Kod #${code.id} - ${code.language}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <pre><code class="language-${code.language}">${escapeHtml(code.code_content)}</code></pre>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Kategori:</strong> ${code.category || 'Belirtilmemiş'}</p>
                                        <p><strong>Etiketler:</strong> ${code.tags ? code.tags.join(', ') : 'Yok'}</p>
                                        <p><strong>Güven Skoru:</strong> ${code.confidence_score}</p>
                                        <p><strong>Kullanım Sayısı:</strong> ${code.usage_count}</p>
                                        <p><strong>Son Kullanım:</strong> ${code.last_used_at || 'Hiç kullanılmadı'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('#current-code-detail').html(html);
                    
                    // Kod vurgulama için (eğer highlight.js kullanıyorsanız)
                    if (typeof hljs !== 'undefined') {
                        document.querySelectorAll('pre code').forEach((block) => {
                            hljs.highlightBlock(block);
                        });
                    }
                }
            },
            error: function() {
                $('#current-code-detail').html('<div class="alert alert-danger">Kod detayı alınamadı.</div>');
            }
        });
    }
    
    // HTML karakterlerini escape et
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Bilinç sistemini aktifleştir
    function activateConsciousness() {
        console.log('activateConsciousness() çağrıldı');
        $('#consciousness-status').html('<div class="alert alert-info">Bilinç sistemi aktifleştiriliyor...</div>');
        
        $.ajax({
            url: '/api/ai/code-consciousness/activate',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Aktivasyon yanıtı:', response);
                if (response.success) {
                    $('#consciousness-status').html('<div class="alert alert-success">Bilinç sistemi aktifleştirildi!</div>');
                    loadConsciousnessStatus();
                    loadProcessingStatus();
                } else {
                    $('#consciousness-status').html('<div class="alert alert-danger">Hata: ' + (response.error || 'Bilinmeyen bir hata oluştu') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Aktivasyon hatası:', {xhr: xhr, status: status, error: error});
                console.error('Yanıt:', xhr.responseText);
                $('#consciousness-status').html('<div class="alert alert-danger">Hata: ' + error + '</div>');
                alert('Bilinç sistemi aktifleştirilemedi! Detaylar için konsola bakın.');
            }
        });
    }
    
    // Bilinç sistemini devre dışı bırak
    function deactivateConsciousness() {
        console.log('deactivateConsciousness() çağrıldı');
        $('#consciousness-status').html('<div class="alert alert-info">Bilinç sistemi devre dışı bırakılıyor...</div>');
        
        $.ajax({
            url: '/api/ai/code-consciousness/deactivate',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Deaktivasyon yanıtı:', response);
                if (response.success) {
                    $('#consciousness-status').html('<div class="alert alert-warning">Bilinç sistemi devre dışı bırakıldı!</div>');
                    loadConsciousnessStatus();
                } else {
                    $('#consciousness-status').html('<div class="alert alert-danger">Hata: ' + (response.error || 'Bilinmeyen bir hata oluştu') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Deaktivasyon hatası:', {xhr: xhr, status: status, error: error});
                console.error('Yanıt:', xhr.responseText);
                $('#consciousness-status').html('<div class="alert alert-danger">Hata: ' + error + '</div>');
            }
        });
    }
    
    // Düşünmeyi tetikle
    function triggerThinking() {
        console.log('triggerThinking() çağrıldı');
        $('#consciousness-status').html('<div class="alert alert-info">Düşünme tetikleniyor...</div>');
        
        $.ajax({
            url: '/api/ai/code-consciousness/think',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Düşünme yanıtı:', response);
                if (response.success) {
                    $('#consciousness-status').html('<div class="alert alert-success">Düşünme tetiklendi!</div>');
                    loadConsciousnessStatus();
                } else {
                    $('#consciousness-status').html('<div class="alert alert-danger">Hata: ' + (response.error || 'Bilinmeyen bir hata oluştu') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Düşünme hatası:', {xhr: xhr, status: status, error: error});
                console.error('Yanıt:', xhr.responseText);
                $('#consciousness-status').html('<div class="alert alert-danger">Hata: ' + error + '</div>');
            }
        });
    }
    
    // Tek kod işle
    function processSingleCode() {
        console.log('processSingleCode() çağrıldı');
        $('#process-single-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> İşleniyor...');
        
        $.ajax({
            url: '/api/ai/code-consciousness/process-single',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Tek kod işleme yanıtı:', response);
                $('#process-single-btn').prop('disabled', false).text('Tek Kod İşle');
                
                if (response.success) {
                    alert('Kod başarıyla işlendi!');
                    
                    // Durumları güncelle
                    loadConsciousnessStatus();
                    loadProcessingStatus();
                    loadRecentProcessedCodes();
                } else {
                    alert('Kod işleme başarısız oldu: ' + (response.message || 'Bilinmeyen hata'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Tek kod işleme hatası:', {xhr: xhr, status: status, error: error});
                console.error('Yanıt:', xhr.responseText);
                $('#process-single-btn').prop('disabled', false).text('Tek Kod İşle');
                alert('Kod işleme sırasında bir hata oluştu: ' + error);
            }
        });
    }
    
    // Toplu kod işle
    function processBatchCodes() {
        console.log('processBatchCodes() çağrıldı');
        let count = $('#batch-count').val();
        $('#process-batch-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> İşleniyor...');
        
        $.ajax({
            url: '/api/ai/code-consciousness/process-batch',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { count: count },
            success: function(response) {
                console.log('Toplu işleme yanıtı:', response);
                $('#process-batch-btn').prop('disabled', false).text('Toplu İşle');
                
                if (response.success) {
                    alert('Toplu işleme başarılı: ' + response.message);
                    
                    // Durumları güncelle
                    loadConsciousnessStatus();
                    loadProcessingStatus();
                    loadRecentProcessedCodes();
                } else {
                    alert('Toplu işleme başarısız oldu: ' + (response.message || 'Bilinmeyen hata'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Toplu işleme hatası:', {xhr: xhr, status: status, error: error});
                console.error('Yanıt:', xhr.responseText);
                $('#process-batch-btn').prop('disabled', false).text('Toplu İşle');
                alert('Toplu işleme sırasında bir hata oluştu: ' + error);
            }
        });
    }
    
    // İşlenen kodları sıfırla
    function resetProcessedCodes() {
        console.log('resetProcessedCodes() çağrıldı');
        if (confirm('Tüm işlenmiş kodları sıfırlamak istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
            $.ajax({
                url: '/api/ai/code-consciousness/reset-processed',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('Sıfırlama yanıtı:', response);
                    if (response.success) {
                        alert('İşlenmiş kodlar başarıyla sıfırlandı!');
                        
                        // Durumları güncelle
                        loadConsciousnessStatus();
                        loadProcessingStatus();
                        loadRecentProcessedCodes();
                    } else {
                        alert('Sıfırlama başarısız oldu: ' + (response.message || 'Bilinmeyen hata'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Sıfırlama hatası:', {xhr: xhr, status: status, error: error});
                    console.error('Yanıt:', xhr.responseText);
                    alert('Sıfırlama işlemi sırasında bir hata oluştu: ' + error);
                }
            });
        }
    }
    
    // İlk yükleme işlemleri
    loadConsciousnessStatus();
    loadProcessingStatus();
    loadRecentProcessedCodes();
});
</script>
@endsection
