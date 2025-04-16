@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Kullanıcı İstatistikleri</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Kullanıcı İstatistikleri</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Günlük Mesaj İstatistikleri
                </div>
                <div class="card-body">
                    <canvas id="dailyMessageChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Cihaz Dağılımı
                </div>
                <div class="card-body">
                    <canvas id="deviceChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Benzersiz Ziyaretçiler (Oturum ID'ye Göre)
        </div>
        <div class="card-body">
            <table id="visitorStatsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Ziyaretçi ID</th>
                        <th>IP Adresi</th>
                        <th>Mesaj Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visitorStats as $stat)
                    <tr>
                        <td>{{ str_replace('"', '', $stat->visitor_id) }}</td>
                        <td>{{ $stat->ip_address }}</td>
                        <td>{{ $stat->message_count }}</td>
                        <td>
                            <a href="{{ route('admin.user-stats.visitor-details', ['visitorId' => str_replace('"', '', $stat->visitor_id)]) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Ziyaretçi Detayı
                            </a>
                            <a href="{{ route('admin.user-stats.ip-details', $stat->ip_address) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-network-wired"></i> IP Detayı
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            IP Adresi İstatistikleri
        </div>
        <div class="card-body">
            <table id="ipStatsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>IP Adresi</th>
                        <th>Mesaj Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ipStats as $stat)
                    <tr>
                        <td>{{ $stat->ip_address }}</td>
                        <td>{{ $stat->message_count }}</td>
                        <td>
                            <a href="{{ route('admin.user-stats.ip-details', $stat->ip_address) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Detaylar
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-laptop me-1"></i>
                    Tarayıcı Dağılımı
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tarayıcı</th>
                                <th>Kullanıcı Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deviceStats['browsers'] as $browser => $count)
                            <tr>
                                <td>{{ $browser }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-desktop me-1"></i>
                    İşletim Sistemi Dağılımı
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>İşletim Sistemi</th>
                                <th>Kullanıcı Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deviceStats['operating_systems'] as $os => $count)
                            <tr>
                                <td>{{ $os }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-mobile-alt me-1"></i>
                    Cihaz Tipi Dağılımı
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Cihaz Tipi</th>
                                <th>Kullanıcı Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deviceStats['device_types'] as $deviceType => $count)
                            <tr>
                                <td>{{ $deviceType }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
<script>
    // Günlük mesaj istatistikleri için grafik
    var dailyCtx = document.getElementById("dailyMessageChart");
    var dailyChart = new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: [
                @foreach($dailyMessageStats as $stat)
                "{{ $stat->date }}",
                @endforeach
            ],
            datasets: [{
                label: "Mesaj Sayısı",
                lineTension: 0.3,
                backgroundColor: "rgba(2,117,216,0.2)",
                borderColor: "rgba(2,117,216,1)",
                pointRadius: 5,
                pointBackgroundColor: "rgba(2,117,216,1)",
                pointBorderColor: "rgba(255,255,255,0.8)",
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "rgba(2,117,216,1)",
                pointHitRadius: 50,
                pointBorderWidth: 2,
                data: [
                    @foreach($dailyMessageStats as $stat)
                    {{ $stat->message_count }},
                    @endforeach
                ],
            }],
        },
        options: {
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        maxTicksLimit: 5
                    },
                    gridLines: {
                        color: "rgba(0, 0, 0, .125)",
                    }
                }],
            },
            legend: {
                display: false
            }
        }
    });
    
    // Cihaz tipi dağılımı için grafik
    var deviceCtx = document.getElementById("deviceChart");
    var deviceChart = new Chart(deviceCtx, {
        type: 'pie',
        data: {
            labels: [
                @foreach($deviceStats['device_types'] as $deviceType => $count)
                "{{ $deviceType }}",
                @endforeach
            ],
            datasets: [{
                data: [
                    @foreach($deviceStats['device_types'] as $count)
                    {{ $count }},
                    @endforeach
                ],
                backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6f42c1', '#fd7e14', '#20c997'],
            }],
        },
    });
    
    // DataTables
    $(document).ready(function() {
        $('#ipStatsTable').DataTable({
            order: [[1, 'desc']]
        });
        
        $('#visitorStatsTable').DataTable({
            order: [[2, 'desc']]
        });
    });
</script>
@endsection 