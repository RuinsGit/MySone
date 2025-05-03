@extends('back.layouts.app')

@section('title', isset($visitor) ? $visitor->name . ' Ziyaretçi Detayları' : 'Ziyaretçi Detayları')

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
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">
        @if(isset($isGoogleUser) && $isGoogleUser)
            <i class="fab fa-google text-danger me-2"></i> Google Kullanıcıları
        @else
            <i class="fas fa-user me-2"></i> Ziyaretçi Detayları
        @endif
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.user-stats.index') }}">Kullanıcı İstatistikleri</a></li>
        <li class="breadcrumb-item active">
            @if(isset($isGoogleUser) && $isGoogleUser)
                Google Kullanıcıları
            @else
                {{ $visitor->name ?? 'Ziyaretçi' }} Detayları
            @endif
        </li>
    </ol>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        @if(isset($isGoogleUser) && $isGoogleUser)
                            <i class="fab fa-google text-danger me-1"></i> Google Kullanıcıları
                        @else
                            <i class="fas fa-user me-1"></i> Ziyaretçi Bilgileri
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('admin.user-stats.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Geri Dön
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Google Kullanıcıları Bölümü -->
                    @if(isset($isGoogleUser) && $isGoogleUser)
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i> Bu sayfa, Google hesabıyla giriş yapan tüm kullanıcıların birleştirilmiş görünümünü göstermektedir.
                        </div>
                        
                        @if(isset($googleUsers) && $googleUsers->count() > 0)
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead class="table-primary">
                                        <tr>
                                            <th class="text-center" style="width: 80px">Avatar</th>
                                            <th>Kullanıcı Adı</th>
                                            <th>E-posta</th>
                                            <th>Son Giriş</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($googleUsers as $user)
                                            <tr>
                                                <td class="text-center">
                                                    @if($user->avatar)
                                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="img-circle" width="50" height="50">
                                                    @else
                                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 50px; height: 50px;">
                                                            <i class="fas fa-user text-secondary"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="align-middle">{{ $user->name }}</td>
                                                <td class="align-middle">{{ $user->email }}</td>
                                                <td class="align-middle">
                                                    @if($user->updated_at)
                                                        {{ \Carbon\Carbon::parse($user->updated_at)->format('d.m.Y H:i') }}
                                                        <div class="small text-muted">{{ \Carbon\Carbon::parse($user->updated_at)->diffForHumans() }}</div>
                                                    @else
                                                        <span class="text-muted">Bilgi yok</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> Google hesabıyla giriş yapan kullanıcı bulunamadı.
                            </div>
                        @endif
                    @else
                        <!-- Normal Ziyaretçi Bilgileri -->
                        @if(isset($visitor))
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center mb-4">
                                        @if(!empty($visitor->avatar))
                                            <img src="{{ $visitor->avatar }}" alt="{{ $visitor->name }}" class="img-circle rounded-circle img-thumbnail mb-2" style="width: 120px; height: 120px;">
                                        @else
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 120px; height: 120px;">
                                                <i class="fas fa-user fa-4x text-secondary"></i>
                                            </div>
                                        @endif
                                        <h5 class="mt-2">{{ $visitor->name ?? 'İsimsiz Ziyaretçi' }}</h5>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">Ziyaretçi ID</h6>
                                                    <p class="card-text"><code>{{ $visitor->visitor_id }}</code></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">IP Adresi</h6>
                                                    <p class="card-text">
                                                        <a href="{{ route('admin.user-stats.ip-details', $visitor->ip_address ?? 'unknown') }}">
                                                            <i class="fas fa-network-wired me-1"></i> {{ $visitor->ip_address ?? 'Bilinmiyor' }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">İlk Ziyaret</h6>
                                                    <p class="card-text">
                                                        @if(isset($visitor->created_at))
                                                            {{ \Carbon\Carbon::parse($visitor->created_at)->format('d.m.Y H:i') }}
                                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($visitor->created_at)->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">Bilinmiyor</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">Son Aktivite</h6>
                                                    <p class="card-text">
                                                        @if(isset($visitor->updated_at))
                                                            {{ \Carbon\Carbon::parse($visitor->updated_at)->format('d.m.Y H:i') }}
                                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($visitor->updated_at)->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">Bilinmiyor</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> Ziyaretçi bilgileri bulunamadı.
                            </div>
                        @endif
                    @endif

                    <!-- Konum Bilgileri -->
                    @if(isset($visitor) && (isset($visitor->latitude) || isset($visitor->longitude)))
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-map-marker-alt me-1"></i> Kullanıcı Konum Bilgileri
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <th>Enlem (Latitude)</th>
                                                        <td>{{ $visitor->latitude ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Boylam (Longitude)</th>
                                                        <td>{{ $visitor->longitude ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Son Güncelleme</th>
                                                        <td>
                                                            @if(isset($visitor->updated_at))
                                                                {{ \Carbon\Carbon::parse($visitor->updated_at)->format('d.m.Y H:i') }}
                                                                <div class="small text-muted">{{ \Carbon\Carbon::parse($visitor->updated_at)->diffForHumans() }}</div>
                                                            @else
                                                                <span class="text-muted">Bilinmiyor</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="text-center">
                                                            <a href="https://www.google.com/maps?q={{ $visitor->latitude }},{{ $visitor->longitude }}" target="_blank" class="btn btn-sm btn-success w-100">
                                                                <i class="fas fa-external-link-alt me-1"></i> Google Maps'te Görüntüle
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Harita Kontrol Butonları -->
                                        @if(isset($visitor->latitude) && isset($visitor->longitude))
                                        <div class="card mt-3">
                                            <div class="card-header bg-light">
                                                <i class="fas fa-sliders-h me-1"></i> Harita Ayarları
                                            </div>
                                            <div class="card-body">
                                                <p class="small mb-2">Harita görünümünü değiştirin:</p>
                                                <div class="btn-group w-100">
                                                    <button id="map-type-roadmap" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-road me-1"></i> Normal
                                                    </button>
                                                    <button id="map-type-satellite" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-satellite me-1"></i> Uydu
                                                    </button>
                                                    <button id="map-type-hybrid" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-globe me-1"></i> Karma
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-7">
                                        @if(isset($visitor->latitude) && isset($visitor->longitude))
                                            <div class="card shadow">
                                                <div class="card-body p-0">
                                                    <div id="location-map" style="height: 350px; width: 100%; border-radius: 4px;"></div>
                                                </div>
                                                <div class="card-footer bg-light py-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i> Haritada nokta üzerinde gezmek için fareyi kullanın. Haritayı büyütmek için çift tıklayın.
                                                    </small>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i> Harita görüntülemek için konum bilgileri yeterli değil.
                                            </div>
                                        @endif
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
                    @endif

                    <!-- Cihaz Bilgileri -->
                    @if(isset($visitor) && isset($visitor->device_info) && !empty($visitor->device_info))
                        <div class="card mt-4">
                            <div class="card-header">
                                <i class="fas fa-mobile-alt me-1"></i> Cihaz Bilgileri
                            </div>
                            <div class="card-body">
                                @php
                                    $deviceInfo = is_string($visitor->device_info) ? json_decode($visitor->device_info) : $visitor->device_info;
                                @endphp
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-desktop fa-3x text-secondary"></i>
                                                </div>
                                                <h5>{{ $deviceInfo->device ?? 'Bilinmeyen Cihaz' }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <th>Tarayıcı</th>
                                                        <td>{{ $deviceInfo->browser ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>İşletim Sistemi</th>
                                                        <td>{{ $deviceInfo->os ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Cihaz</th>
                                                        <td>{{ $deviceInfo->device ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <th>Ekran</th>
                                                        <td>{{ $deviceInfo->screen ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Tarayıcı Versiyonu</th>
                                                        <td>{{ $deviceInfo->browser_version ?? 'Bilinmiyor' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Mobil</th>
                                                        <td>{{ isset($deviceInfo->is_mobile) && $deviceInfo->is_mobile ? 'Evet' : 'Hayır' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i> Cihaz bilgisi bulunmuyor.
                        </div>
                    @endif

                    <!-- Mesaj Geçmişi -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-comments me-1"></i> Mesaj Geçmişi
                            @if(isset($messages))
                                <span class="badge bg-primary ms-2">{{ count($messages) }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(isset($messages) && count($messages) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="messagesTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Mesaj</th>
                                                <th style="width: 100px">Gönderen</th>
                                                <th style="width: 150px">Tarih</th>
                                                <th style="width: 100px">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($messages as $message)
                                                <tr>
                                                    <td>{{ \Illuminate\Support\Str::limit($message->content, 100) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $message->role == 'user' || $message->role == 'sender' ? 'primary' : 'success' }}">
                                                            {{ $message->role == 'user' || $message->role == 'sender' ? 'Kullanıcı' : 'AI' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        {{ \Carbon\Carbon::parse($message->created_at)->format('d.m.Y H:i') }}
                                                        <div class="small text-muted">{{ \Carbon\Carbon::parse($message->created_at)->diffForHumans() }}</div>
                                                    </td>
                                                    <td>
                                                        @if(isset($message->chat_id))
                                                        <a href="{{ route('admin.message-history.chat', ['chatId' => $message->chat_id]) }}" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Mesaj geçmişi bulunamadı.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Sohbetler -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="fas fa-comment-dots me-1"></i> Sohbetler
                            @if(isset($chats))
                                <span class="badge bg-primary ms-2">{{ count($chats) }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(isset($chats) && count($chats) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="chatsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sohbet ID</th>
                                                <th>Başlık</th>
                                                <th>Oluşturulma</th>
                                                <th>Son Güncelleme</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($chats as $chat)
                                                <tr>
                                                    <td>{{ $chat->id }}</td>
                                                    <td>{{ $chat->title ?? 'Başlıksız Sohbet' }}</td>
                                                    <td>
                                                        {{ \Carbon\Carbon::parse($chat->created_at)->format('d.m.Y H:i') }}
                                                    </td>
                                                    <td>
                                                        {{ \Carbon\Carbon::parse($chat->updated_at)->format('d.m.Y H:i') }}
                                                        <div class="small text-muted">{{ \Carbon\Carbon::parse($chat->updated_at)->diffForHumans() }}</div>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('admin.message-history.chat', ['chatId' => $chat->id]) }}" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> Görüntüle
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Sohbet geçmişi bulunamadı.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('admin.user-stats.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kullanıcı İstatistiklerine Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Mesajlar tablosu
    $('#messagesTable').DataTable({
        order: [[2, 'desc']], // Tarihe göre sırala
        pageLength: 10,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json'
        }
    });
    
    // Sohbetler tablosu
    $('#chatsTable').DataTable({
        order: [[3, 'desc']], // Son güncellemeye göre sırala
        pageLength: 10,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Turkish.json'
        }
    });
    
    // Konum haritası varsa yükle
    @if(isset($visitor) && isset($visitor->latitude) && isset($visitor->longitude))
    // Harita yükleme fonksiyonu
    function initMap() {
        // Konum koordinatları
        var latitude = {{ $visitor->latitude }};
        var longitude = {{ $visitor->longitude }};
        var mapPosition = {lat: latitude, lng: longitude};
        
        // Harita oluştur
        var map = new google.maps.Map(document.getElementById('location-map'), {
            zoom: 14,
            center: mapPosition,
            mapTypeId: 'roadmap',
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                position: google.maps.ControlPosition.TOP_RIGHT
            },
            fullscreenControl: true,
            streetViewControl: true,
            zoomControl: true
        });
        
        // Konum işaretleyici ekle
        var marker = new google.maps.Marker({
            position: mapPosition,
            map: map,
            title: '{{ addslashes($visitor->name ?? "Ziyaretçi") }} Konumu',
            animation: google.maps.Animation.DROP,
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            }
        });
        
        // Bilgi penceresi
        var infoContent = '<div style="min-width: 200px; padding: 10px;">' +
                         '<h5 style="margin: 0 0 10px 0; color: #3366cc;">{{ addslashes($visitor->name ?? "Ziyaretçi") }}</h5>' +
                         '<p style="margin: 0; font-size: 13px;"><strong>IP Adresi:</strong> {{ $visitor->ip_address ?? "Bilinmiyor" }}</p>' +
                         '<p style="margin: 0; font-size: 13px;"><strong>Enlem:</strong> {{ $visitor->latitude }}</p>' +
                         '<p style="margin: 0; font-size: 13px;"><strong>Boylam:</strong> {{ $visitor->longitude }}</p>' +
                         '<p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Son güncelleme: {{ isset($visitor->updated_at) ? \Carbon\Carbon::parse($visitor->updated_at)->format("d.m.Y H:i") : "Bilinmiyor" }}</p>' +
                         '</div>';
        
        var infoWindow = new google.maps.InfoWindow({
            content: infoContent
        });
        
        // İşaretleyici tıklama olayı
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });
        
        // Haritayı ilk yüklendiğinde bilgi penceresini aç
        infoWindow.open(map, marker);
        
        // Haritayı yeniden boyutlandır (responsive olması için)
        google.maps.event.addDomListener(window, 'resize', function() {
            google.maps.event.trigger(map, 'resize');
            map.setCenter(mapPosition);
        });
        
        // Harita tipini değiştirme butonları
        document.getElementById('map-type-roadmap').addEventListener('click', function() {
            map.setMapTypeId('roadmap');
        });
        
        document.getElementById('map-type-satellite').addEventListener('click', function() {
            map.setMapTypeId('satellite');
        });
        
        document.getElementById('map-type-hybrid').addEventListener('click', function() {
            map.setMapTypeId('hybrid');
        });
    }
    
    // Google Maps API yüklendikten sonra haritayı başlat
    function loadGoogleMaps() {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            // API zaten yüklü, haritayı doğrudan başlat
            initMap();
        } else {
            // Google Maps API'sini yükle
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&callback=initMap';
            script.async = true;
            script.defer = true;
            
            // Callback fonksiyonunu global scope'a ekle
            window.initMap = initMap;
            
            document.head.appendChild(script);
        }
    }
    
    // Sayfa yüklendiğinde haritayı yükle
    loadGoogleMaps();
    @endif
});
</script>
@endsection
