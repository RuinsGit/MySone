@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-users text-primary me-2"></i>
        Kullanıcı İstatistikleri
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Kullanıcı İstatistikleri</li>
    </ol>
    
    <!-- İstatistik Kartları -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Toplam Ziyaretçi</h5>
                        </div>
                        <div class="fs-2 fw-bold">{{ $visitors->total() }}</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <div>Tüm kullanıcılar</div>
                    <div class="text-white"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kullanıcı Tablosu -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Ziyaretçi Listesi
            </div>
            <div class="d-flex">
                <input type="text" class="form-control form-control-sm me-2" id="userSearchInput" placeholder="Ara...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="usersTable">
                    <thead class="table-light">
                        <tr>
                            <th>Kullanıcı</th>
                            <th>Cihaz Bilgisi</th>
                            <th>IP Adresi</th>
                            <th>Son Aktivite</th>
                            <th>Mesaj Sayısı</th>
                            <th class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visitors as $visitor)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if(!empty($visitor->avatar))
                                        <img src="{{ $visitor->avatar }}" alt="{{ $visitor->name }}" class="rounded-circle me-2" style="width: 36px; height: 36px;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded-circle me-2" style="width: 36px; height: 36px;">
                                            <i class="fas fa-user text-secondary"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $visitor->name }}</div>
                                        <div class="small text-muted">ID: {{ substr($visitor->visitor_id, 0, 10) }}...</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $deviceInfo = $visitor->device_info_decoded;
                                    $browser = $deviceInfo['browser'] ?? 'Bilinmiyor';
                                    $os = $deviceInfo['os'] ?? 'Bilinmiyor';
                                    $device = $deviceInfo['device'] ?? 'Bilinmiyor';
                                    
                                    // İşletim sistemi ikonları
                                    $osIcons = [
                                        'Windows' => 'fab fa-windows',
                                        'Mac' => 'fab fa-apple',
                                        'iOS' => 'fab fa-apple',
                                        'Android' => 'fab fa-android',
                                        'Linux' => 'fab fa-linux',
                                        'Ubuntu' => 'fab fa-ubuntu'
                                    ];
                                    
                                    // Tarayıcı ikonları
                                    $browserIcons = [
                                        'Chrome' => 'fab fa-chrome',
                                        'Firefox' => 'fab fa-firefox',
                                        'Safari' => 'fab fa-safari',
                                        'Edge' => 'fab fa-edge',
                                        'Opera' => 'fab fa-opera',
                                        'IE' => 'fab fa-internet-explorer'
                                    ];
                                    
                                    // İkon belirleme
                                    $osIcon = 'fas fa-desktop';
                                    foreach($osIcons as $key => $icon) {
                                        if(stripos($os, $key) !== false) {
                                            $osIcon = $icon;
                                            break;
                                        }
                                    }
                                    
                                    $browserIcon = 'fas fa-globe';
                                    foreach($browserIcons as $key => $icon) {
                                        if(stripos($browser, $key) !== false) {
                                            $browserIcon = $icon;
                                            break;
                                        }
                                    }
                                    
                                    $deviceIcon = 'fas fa-desktop';
                                    if(stripos($device, 'Mobile') !== false || stripos($device, 'Phone') !== false) {
                                        $deviceIcon = 'fas fa-mobile-alt';
                                    } elseif(stripos($device, 'Tablet') !== false) {
                                        $deviceIcon = 'fas fa-tablet-alt';
                                    }
                                @endphp
                                
                                <div class="mb-1"><i class="{{ $osIcon }} me-1"></i> {{ $os }}</div>
                                <div class="mb-1"><i class="{{ $browserIcon }} me-1"></i> {{ $browser }}</div>
                                <div><i class="{{ $deviceIcon }} me-1"></i> {{ $device }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-network-wired me-2 text-secondary"></i>
                                    <a href="{{ route('admin.user-stats.ip-details', $visitor->ip_address) }}" class="text-decoration-none">
                                        {{ $visitor->ip_address }}
                                    </a>
                                </div>
                                <div class="small mt-1">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                    <span>{{ $visitor->ip_location['country'] ?? 'Bilinmiyor' }}</span>
                                    @if(!empty($visitor->ip_location['city']))
                                        <span>, {{ $visitor->ip_location['city'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if(isset($visitor->last_active))
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ \Carbon\Carbon::parse($visitor->last_active)->format('d.m.Y H:i:s') }}">
                                        {{ \Carbon\Carbon::parse($visitor->last_active)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-muted">Bilinmiyor</span>
                                @endif
                                <div class="small text-muted">
                                    <i class="far fa-calendar-alt me-1"></i> Kayıt: {{ \Carbon\Carbon::parse($visitor->created_at)->format('d.m.Y') }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $visitor->message_count }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.user-stats.visitor-details', ['visitorId' => $visitor->visitor_id]) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Detaylar
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Sayfalama -->
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Toplam {{ $visitors->total() }} ziyaretçi | Sayfa {{ $visitors->currentPage() }}/{{ $visitors->lastPage() }}</span>
                </div>
                <div>
                    {{ $visitors->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // DataTable
        var table = $('#usersTable').DataTable({
            paging: false,
            info: false,
            responsive: true,
            dom: 'rt',
            order: [[3, 'desc']] // Son aktiviteye göre sırala
        });
        
        // Arama kutusunu aktifleştir
        $('#userSearchInput').keyup(function() {
            table.search($(this).val()).draw();
        });
    });
</script>
@endsection
