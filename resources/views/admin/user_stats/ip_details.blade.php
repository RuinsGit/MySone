@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-network-wired text-primary me-2"></i>
        IP Detayları: {{ $ip }}
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.user-stats.index') }}">Kullanıcı İstatistikleri</a></li>
        <li class="breadcrumb-item active">IP Detayları</li>
    </ol>

    <div class="row">
        <!-- IP Bilgileri -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    IP Adresi Bilgileri
                </div>
                <div class="card-body">
                    <!-- IP Info Box -->
                    <div class="d-flex align-items-center mb-4">
                        <div class="ip-icon bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 64px; height: 64px;">
                            <i class="fas fa-network-wired text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $ip }}</h4>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <span>
                                    {{ $ipDetails['country'] }} 
                                    @if(!empty($ipDetails['city']))
                                        <span>, {{ $ipDetails['city'] }}</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- IP Details List -->
                    <div class="list-group list-group-flush border-top pt-3">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-globe me-2"></i> Ülke</span>
                            <span>{{ $ipDetails['country'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-map me-2"></i> Bölge</span>
                            <span>{{ $ipDetails['region'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-city me-2"></i> Şehir</span>
                            <span>{{ $ipDetails['city'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-building me-2"></i> Servis Sağlayıcı (ISP)</span>
                            <span>{{ $ipDetails['isp'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-briefcase me-2"></i> Organizasyon</span>
                            <span>{{ $ipDetails['org'] }}</span>
                        </div>
                    </div>
                    
                    <!-- Security Info -->
                    <div class="mt-4">
                        <h6 class="fw-bold">Güvenlik Bilgileri</h6>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="small text-muted">VPN/Proxy</div>
                                        <div class="fw-semibold">
                                            @if($ipDetails['isVpn'] || $ipDetails['isProxy'])
                                                <span class="text-warning">Tespit Edildi</span>
                                            @else
                                                <span class="text-success">Temiz</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="icon-box rounded-circle {{ ($ipDetails['isVpn'] || $ipDetails['isProxy']) ? 'bg-warning' : 'bg-success' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas {{ ($ipDetails['isVpn'] || $ipDetails['isProxy']) ? 'fa-mask' : 'fa-shield-alt' }} text-white"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="small text-muted">Hosting/Datacenter</div>
                                        <div class="fw-semibold">
                                            @if($ipDetails['isHosting'])
                                                <span class="text-warning">Hosting IP</span>
                                            @else
                                                <span class="text-success">Bireysel IP</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="icon-box rounded-circle {{ $ipDetails['isHosting'] ? 'bg-warning' : 'bg-success' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas {{ $ipDetails['isHosting'] ? 'fa-server' : 'fa-home' }} text-white"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="small text-muted">Cihaz Türü</div>
                                        <div class="fw-semibold">
                                            @if($ipDetails['isMobile'] ?? false)
                                                <span>Mobil</span>
                                            @else
                                                <span>Masaüstü/Sabit</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="icon-box rounded-circle bg-info d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas {{ ($ipDetails['isMobile'] ?? false) ? 'fa-mobile-alt' : 'fa-desktop' }} text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Harita ve Ziyaretçiler -->
        <div class="col-lg-7">
            <div class="row">
                <!-- Harita -->
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-map-marked-alt me-1"></i>
                            Konum Haritası
                        </div>
                        <div class="card-body">
                            @if($ipDetails['country'] != 'Yerel IP' && $ipDetails['country'] != 'Bilgi Yok' && $ipDetails['country'] != 'Hata')
                                <div id="map" class="rounded" style="height: 300px;"></div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Bu IP adresi için harita gösterilemiyor: {{ $ipDetails['country'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Ziyaretçiler -->
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-users me-1"></i>
                            Bu IP Adresini Kullanan Ziyaretçiler
                        </div>
                        <div class="card-body">
                            @if(count($visitors) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kullanıcı</th>
                                                <th>Ziyaretçi ID</th>
                                                <th>Kayıt Tarihi</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($visitors as $visitor)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if(!empty($visitor->avatar))
                                                                <img src="{{ $visitor->avatar }}" alt="{{ $visitor->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                                                            @else
                                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                                    <i class="fas fa-user text-secondary small"></i>
                                                                </div>
                                                            @endif
                                                            <div>{{ $visitor->name }}</div>
                                                        </div>
                                                    </td>
                                                    <td><small class="text-muted">{{ substr($visitor->visitor_id, 0, 12) }}...</small></td>
                                                    <td>{{ \Carbon\Carbon::parse($visitor->created_at)->format('d.m.Y H:i') }}</td>
                                                    <td>
                                                        <a href="{{ route('admin.user-stats.visitor-details', ['visitorId' => $visitor->visitor_id]) }}" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> Detaylar
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Bu IP adresini kullanan ziyaretçi kaydı bulunamadı.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Son Mesajlar -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-comment-dots me-1"></i>
                Bu IP'den Gönderilen Mesajlar
            </div>
            <div>
                <a href="{{ route('admin.user-stats.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> İstatistiklere Dön
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($messages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="messagesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Mesaj</th>
                                <th>Sohbet</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($messages as $message)
                                @php
                                    $metadata = is_string($message->metadata) ? json_decode($message->metadata, true) : $message->metadata;
                                    $visitorId = $metadata['visitor_id'] ?? null;
                                    $visitorName = $metadata['visitor_name'] ?? 'İsimsiz Ziyaretçi';
                                @endphp
                                <tr>
                                    <td>{{ $visitorName }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($message->content, 80) }}</td>
                                    <td>
                                        <a href="{{ route('admin.message-history.chat', ['chatId' => $message->chat_id]) }}" class="text-decoration-none">
                                            Sohbet #{{ $message->chat_id }}
                                        </a>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($message->created_at)->format('d.m.Y H:i') }}</td>
                                    <td>
                                        @if($visitorId)
                                            <a href="{{ route('admin.user-stats.visitor-details', ['visitorId' => $visitorId]) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-user"></i> Ziyaretçi
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Sayfalama -->
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">Toplam {{ $messages->total() }} mesaj | Sayfa {{ $messages->currentPage() }}/{{ $messages->lastPage() }}</span>
                    </div>
                    <div>
                        {{ $messages->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Bu IP adresinden gönderilmiş mesaj kaydı bulunamadı.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<style>
    .icon-box {
        display: flex;
        justify-content: center;
        align-items: center;
        min-width: 40px;
        min-height: 40px;
    }
</style>
@endsection

@section('js')
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
    $(document).ready(function() {
        // DataTable
        $('#messagesTable').DataTable({
            paging: false,
            info: false,
            responsive: true,
            dom: 'rt',
            order: [[3, 'desc']] // Son aktiviteye göre sırala
        });
        
        @if(isset($ipDetails['country']) && $ipDetails['country'] != 'Yerel IP' && $ipDetails['country'] != 'Bilgi Yok' && $ipDetails['country'] != 'Hata')
            // Harita gösterimi
            var map = L.map('map').setView([0, 0], 2);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // IP konumunu almak için API isteği
            fetch(`https://ipapi.co/{{ $ip }}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (data.latitude && data.longitude) {
                        var marker = L.marker([data.latitude, data.longitude]).addTo(map);
                        marker.bindPopup("<b>{{ $ip }}</b><br>{{ $ipDetails['country'] }}, {{ $ipDetails['city'] }}").openPopup();
                        map.setView([data.latitude, data.longitude], 13);
                    }
                })
                .catch(err => {
                    console.error('IP konum bilgisi alınamadı:', err);
                    
                    // Alternatif olarak ülkeye göre genel bir konum göster
                    var countryCoordinates = {
                        'Turkey': [39.9334, 32.8597],
                        'United States': [37.7749, -122.4194],
                        'Germany': [52.5200, 13.4050],
                        'United Kingdom': [51.5074, -0.1278],
                        'France': [48.8566, 2.3522],
                        'Russia': [55.7558, 37.6173],
                        'China': [39.9042, 116.4074],
                        'Japan': [35.6762, 139.6503],
                        'Australia': [-33.8688, 151.2093],
                        'Brazil': [-23.5505, -46.6333],
                        'Canada': [45.4215, -75.6972],
                        'India': [28.6139, 77.2090]
                    };
                    
                    var country = "{{ $ipDetails['country'] }}";
                    if (countryCoordinates[country]) {
                        var coords = countryCoordinates[country];
                        var marker = L.marker(coords).addTo(map);
                        marker.bindPopup("<b>{{ $ip }}</b><br>{{ $ipDetails['country'] }}").openPopup();
                        map.setView(coords, 6);
                    } else {
                        // Varsayılan dünya görünümü
                        map.setView([0, 0], 2);
                    }
                });
        @endif
    });
</script>
@endsection
