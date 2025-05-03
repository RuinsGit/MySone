@extends('back.layouts.app')

@section('title', isset($visitor) ? $visitor->name . ' Kullanıcı Mesajları' : 'Kullanıcı Mesajları')

@section('css')
<style>
    /* Harita için stil kuralları */
    #location-map {
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .map-controls button {
        transition: all 0.2s ease;
    }
    
    .map-controls button:hover {
        transform: translateY(-2px);
    }
    
    /* Nokta animasyonu */
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.9;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    /* Kart animasyonları */
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    /* Harita info penceresi */
    .gm-style .gm-style-iw-c {
        padding: 12px !important;
        border-radius: 8px !important;
    }

    .progress-stacked {
        display: flex;
        height: 20px;
        border-radius: 0.375rem;
        overflow: hidden;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-user-circle text-primary me-2"></i>
        {{ $visitor->name ?? 'İsimsiz Ziyaretçi' }} <small class="text-muted fs-5">Mesaj Geçmişi</small>
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.message-history.index') }}">Mesaj Geçmişi</a></li>
        <li class="breadcrumb-item active">Kullanıcı Mesajları</li>
    </ol>
    
    @if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
    </div>
    @endif
    
    @if($stats['total_messages'] == 0)
    <div class="alert alert-warning">
        <i class="fas fa-info-circle me-2"></i> Bu ziyaretçi için henüz mesaj bulunmamaktadır.
        <p><small>Ziyaretçi ID: {{ $visitorId }}</small></p>
    </div>
    @endif
    
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Toplam Mesaj</h5>
                        </div>
                        <div class="fs-2 fw-bold">{{ $stats['total_messages'] }}</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <div>Kullanıcının tüm mesajları</div>
                    <div class="text-white"><i class="fas fa-comment"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">AI Mesajları</h5>
                        </div>
                        <div class="fs-2 fw-bold">{{ $stats['ai_messages'] }}</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <div>AI'dan alınan yanıtlar</div>
                    <div class="text-white"><i class="fas fa-robot"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Kullanıcı Mesajları</h5>
                        </div>
                        <div class="fs-2 fw-bold">{{ $stats['user_messages'] }}</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <div>Kullanıcı gönderimi</div>
                    <div class="text-white"><i class="fas fa-user"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card bg-secondary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Sohbet Sayısı</h5>
                        </div>
                        <div class="fs-2 fw-bold">{{ $stats['chats_count'] }}</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <div>Toplam konuşma</div>
                    <div class="text-white"><i class="fas fa-comments"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-info-circle me-1"></i>
                        Kullanıcı Bilgileri
                    </div>
                    <div>
                        <span class="badge {{ $visitor ? 'bg-success' : 'bg-warning' }}">
                            {{ $visitor ? 'Kayıtlı Kullanıcı' : 'Anonim Kullanıcı' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>Ziyaretçi ID:</th>
                                    <td><code class="bg-light p-1 rounded">{{ $visitorId }}</code></td>
                                </tr>
                                <tr>
                                    <th>Kullanıcı Adı:</th>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if(!empty($visitor->avatar))
                                                <img src="{{ $visitor->avatar }}" alt="{{ $visitor->name }}" class="me-2 rounded-circle" width="32" height="32">
                                            @else
                                                <i class="fas fa-user-circle me-1 text-primary"></i>
                                            @endif
                                            {{ $visitor->name ?? 'İsimsiz Ziyaretçi' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>IP Adresi:</th>
                                    <td>
                                        <i class="fas fa-network-wired me-1"></i>
                                        {{ $visitor->ip_address ?? 'Bilinmiyor' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kayıt Tarihi:</th>
                                    <td>
                                        <i class="far fa-calendar-alt me-1"></i>
                                        {{ $visitor->created_at ? \Carbon\Carbon::parse($visitor->created_at)->format('d.m.Y H:i:s') : 'Bilinmiyor' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if(isset($visitor) && (isset($visitor->latitude) || isset($visitor->longitude)))
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-map-marker-alt me-1"></i> Konum Bilgisi
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="location-map" style="height: 200px; width: 100%; border-radius: 0 0 4px 4px;"></div>
                                    </div>
                                </div>
                                <div class="mt-2 text-center small">
                                    <span class="badge bg-primary me-1">Enlem: {{ $visitor->latitude }}</span>
                                    <span class="badge bg-primary">Boylam: {{ $visitor->longitude }}</span>
                                </div>
                            @else
                                <div class="alert alert-info h-100 d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                                        <p class="mb-0">Bu kullanıcı için konum bilgisi bulunmuyor.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Kullanım İstatistikleri
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-4 fw-bold text-end">İlk İletişim:</div>
                        <div class="col-md-8">
                            @if($stats['first_message'])
                                <span data-bs-toggle="tooltip" title="{{ 
                                    isset($stats['first_message']->created_at) && is_object($stats['first_message']->created_at) 
                                    ? $stats['first_message']->created_at->format('d.m.Y H:i:s') 
                                    : (is_string($stats['first_message']->created_at) 
                                        ? \Carbon\Carbon::parse($stats['first_message']->created_at)->format('d.m.Y H:i:s') 
                                        : 'Bilinmiyor') 
                                }}">
                                    <i class="fas fa-calendar-check text-success me-1"></i>
                                    {{ 
                                        isset($stats['first_message']->created_at) && is_object($stats['first_message']->created_at) 
                                        ? $stats['first_message']->created_at->diffForHumans() 
                                        : (is_string($stats['first_message']->created_at) 
                                            ? \Carbon\Carbon::parse($stats['first_message']->created_at)->diffForHumans() 
                                            : 'Bilinmiyor') 
                                    }}
                                </span>
                            @else
                                <span class="text-muted"><i class="fas fa-question-circle me-1"></i> Bilinmiyor</span>
                            @endif
                        </div>
                    </div>
                    <div class="row align-items-center mb-3">
                        <div class="col-md-4 fw-bold text-end">Son İletişim:</div>
                        <div class="col-md-8">
                            @if($stats['last_message'])
                                <span data-bs-toggle="tooltip" title="{{ 
                                    isset($stats['last_message']->created_at) && is_object($stats['last_message']->created_at) 
                                    ? $stats['last_message']->created_at->format('d.m.Y H:i:s') 
                                    : (is_string($stats['last_message']->created_at) 
                                        ? \Carbon\Carbon::parse($stats['last_message']->created_at)->format('d.m.Y H:i:s') 
                                        : 'Bilinmiyor') 
                                }}">
                                    <i class="fas fa-history text-primary me-1"></i>
                                    {{ 
                                        isset($stats['last_message']->created_at) && is_object($stats['last_message']->created_at) 
                                        ? $stats['last_message']->created_at->diffForHumans() 
                                        : (is_string($stats['last_message']->created_at) 
                                            ? \Carbon\Carbon::parse($stats['last_message']->created_at)->diffForHumans() 
                                            : 'Bilinmiyor') 
                                    }}
                                </span>
                            @else
                                <span class="text-muted"><i class="fas fa-question-circle me-1"></i> Bilinmiyor</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="progress-stacked">
                            @php
                                $userPercentage = $stats['total_messages'] > 0 ? round(($stats['user_messages'] / $stats['total_messages']) * 100) : 0;
                                $aiPercentage = 100 - $userPercentage;
                            @endphp
                            <div class="progress" role="progressbar" 
                                 aria-label="Kullanıcı Mesajları" 
                                 style="width: {{ $userPercentage }}%" 
                                 aria-valuenow="{{ $userPercentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <div class="progress-bar bg-primary">{{ $userPercentage }}%</div>
                            </div>
                            <div class="progress" role="progressbar" 
                                 aria-label="AI Mesajları" 
                                 style="width: {{ $aiPercentage }}%" 
                                 aria-valuenow="{{ $aiPercentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <div class="progress-bar bg-success">{{ $aiPercentage }}%</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small><span class="text-primary">■</span> Kullanıcı ({{ $stats['user_messages'] }})</small>
                            <small><span class="text-success">■</span> AI ({{ $stats['ai_messages'] }})</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Konum Bilgileri -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-map-marker-alt me-1"></i> Kullanıcı Konum Bilgisi
            </div>
            <span class="badge bg-light text-dark">
                {{ isset($visitor->latitude) && isset($visitor->longitude) ? 'Gerçek Konum' : 'Yaklaşık Konum' }}
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>Enlem (Latitude)</th>
                                    <td>{{ $visitor->latitude ?? 40.5125215 }}</td>
                                </tr>
                                <tr>
                                    <th>Boylam (Longitude)</th>
                                    <td>{{ $visitor->longitude ?? 50.1388566 }}</td>
                                </tr>
                                <tr>
                                    <th>Doğruluk (Accuracy)</th>
                                    <td>{{ isset($visitor->location_info) ? (json_decode($visitor->location_info, true)['accuracy'] ?? '14.8450') : '14.8450' }}</td>
                                </tr>
                                <tr>
                                    <th>Zaman Damgası</th>
                                    <td>{{ isset($visitor->location_info) ? (json_decode($visitor->location_info, true)['timestamp'] ?? '1746273535437') : '1746273535437' }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <a href="https://www.google.com/maps?q={{ $visitor->latitude ?? 40.5125215 }},{{ $visitor->longitude ?? 50.1388566 }}" target="_blank" class="btn btn-sm btn-success w-100">
                                            <i class="fas fa-external-link-alt me-1"></i> Google Maps'te Görüntüle
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Harita Kontrol Butonları -->
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <i class="fas fa-sliders-h me-1"></i> Harita Seçenekleri
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="https://www.google.com/maps?q={{ $visitor->latitude ?? 40.5125215 }},{{ $visitor->longitude ?? 50.1388566 }}&key=AIzaSyA7gcywhHhm-36Ezpy_Y8gt2cHE-r_NUS0" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> Google Maps'te Aç
                                </a>
                                <a href="https://www.google.com/maps?q={{ $visitor->latitude ?? 40.5125215 }},{{ $visitor->longitude ?? 50.1388566 }}&layer=t&key=AIzaSyA7gcywhHhm-36Ezpy_Y8gt2cHE-r_NUS0" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-satellite me-1"></i> Uydu Görünümünde Aç
                                </a>
                                <a href="https://www.google.com/maps/dir/Current+Location/{{ $visitor->latitude ?? 40.5125215 }},{{ $visitor->longitude ?? 50.1388566 }}?key=AIzaSyA7gcywhHhm-36Ezpy_Y8gt2cHE-r_NUS0" target="_blank" class="btn btn-outline-secondary">
                                    <i class="fas fa-route me-1"></i> Yol Tarifi Al
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <!-- Harita - iframe olarak -->
                    <iframe 
                        width="100%" 
                        height="450" 
                        style="border:0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);" 
                        loading="lazy" 
                        allowfullscreen
                        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyA7gcywhHhm-36Ezpy_Y8gt2cHE-r_NUS0
                            &q={{ $visitor->latitude ?? 40.5125215 }},{{ $visitor->longitude ?? 50.1388566 }}
                            &zoom=15
                            &language=tr">
                    </iframe>
                    <div class="mt-2 small text-muted text-center">
                        <i class="fas fa-info-circle me-1"></i> Haritada gezmek için fare veya dokunmatik ekranı kullanın. Haritayı büyütmek için çift tıklayın.
                    </div>
                </div>
            </div>
            
            @if(isset($visitor->location_info) && !empty($visitor->location_info))
                <div class="mt-4">
                    <h6 class="border-bottom pb-2 mb-3">Detaylı Konum Bilgileri</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <tbody>
                                @php
                                    $locationInfo = json_decode($visitor->location_info, true);
                                @endphp
                                
                                @if(is_array($locationInfo))
                                    @foreach($locationInfo as $key => $value)
                                        @if(!in_array($key, ['latitude', 'longitude']) && !is_array($value) && !is_object($value))
                                            <tr>
                                                <th style="width: 30%">{{ ucfirst($key) }}</th>
                                                <td>{{ $value }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Kullanıcının sohbetleri -->
    @if($stats['chats_count'] > 0)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-comments me-1"></i>
                Kullanıcının Sohbetleri
                <span class="badge bg-primary ms-2">{{ $stats['chats_count'] }}</span>
            </div>
            <a href="{{ route('admin.message-history.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Tüm Mesajlar
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 60px;">ID</th>
                            <th>Sohbet Başlığı</th>
                            <th class="text-center" style="width: 100px;">Mesaj Sayısı</th>
                            <th style="width: 180px;">Oluşturma Tarihi</th>
                            <th class="text-center" style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($chats) > 0)
                            @foreach($chats as $chat)
                            <tr>
                                <td class="text-center">{{ $chat->id }}</td>
                                <td>
                                    <a href="{{ route('admin.message-history.chat', ['chatId' => $chat->id]) }}" class="text-decoration-none">
                                        <i class="fas fa-comment-dots me-1 text-primary"></i>
                                        {{ $chat->title ?: 'İsimsiz Sohbet' }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $chat->message_count ?? $chat->messages->count() }}</span>
                                </td>
                                <td data-bs-toggle="tooltip" title="{{ 
                                    is_object($chat->created_at) 
                                    ? $chat->created_at->format('d.m.Y H:i:s') 
                                    : (\Carbon\Carbon::parse($chat->created_at)->format('d.m.Y H:i:s')) 
                                }}">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    {{ 
                                    is_object($chat->created_at) 
                                    ? $chat->created_at->diffForHumans() 
                                    : (\Carbon\Carbon::parse($chat->created_at)->diffForHumans()) 
                                }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.message-history.chat', ['chatId' => $chat->id]) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Görüntüle
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle me-1"></i> Henüz sohbet bulunmuyor
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Kullanıcının mesajları -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-list me-1"></i>
                Kullanıcı Mesaj Geçmişi
                <span class="badge bg-primary ms-2">{{ $messages->total() }}</span>
            </div>
            <div class="d-flex">
                <a href="{{ route('admin.message-history.index', ['visitor_id' => $visitorId]) }}" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fas fa-filter me-1"></i> Yalnızca Bu Kullanıcı
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="messagesTable" class="table table-striped table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 60px;">ID</th>
                            <th class="text-center" style="width: 80px;">Sohbet ID</th>
                            <th class="text-center" style="width: 100px;">Gönderen</th>
                            <th>İçerik</th>
                            <th style="width: 150px;">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($messages) > 0)
                            @foreach($messages as $message)
                            <tr>
                                <td class="text-center">{{ $message->id }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.message-history.chat', ['chatId' => $message->chat_id]) }}" class="badge bg-secondary text-decoration-none">
                                        {{ $message->chat_id }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $message->sender == 'user' ? 'primary' : 'success' }}">
                                        {{ $message->sender == 'user' ? ($visitor->name ?? 'Kullanıcı') : 'AI' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.message-history.chat', ['chatId' => $message->chat_id]) }}" class="text-decoration-none text-dark">
                                        {{ \Illuminate\Support\Str::limit($message->content, 100) }}
                                    </a>
                                </td>
                                <td data-bs-toggle="tooltip" title="{{ 
                                    isset($message->created_at) && $message->created_at instanceof \DateTime 
                                    ? $message->created_at->format('d.m.Y H:i:s') 
                                    : (\Carbon\Carbon::parse($message->created_at)->format('d.m.Y H:i:s')) 
                                }}">
                                    <i class="far fa-clock me-1"></i>
                                    {{ 
                                    isset($message->created_at) && $message->created_at instanceof \DateTime 
                                    ? $message->created_at->diffForHumans() 
                                    : (\Carbon\Carbon::parse($message->created_at)->diffForHumans()) 
                                }}
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle me-1"></i> Henüz mesaj bulunmuyor
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            @if(count($messages) > 0)
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Toplam {{ $messages->total() }} mesaj | Sayfa {{ $messages->currentPage() }}/{{ $messages->lastPage() }}</span>
                </div>
                <div>
                    {{ $messages->links('pagination::bootstrap-5') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Tooltip'leri etkinleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Console'a konum bilgisi durumunu yaz
        console.log('Konum bilgisi durumu:', {
            'ziyaretçi_var': {{ isset($visitor) ? 'true' : 'false' }},
            'latitude_değeri': '{{ $visitor->latitude ?? "yok" }}',
            'longitude_değeri': '{{ $visitor->longitude ?? "yok" }}',
            'iframe_harita': 'aktif'
        });
    });
</script>
@endsection 