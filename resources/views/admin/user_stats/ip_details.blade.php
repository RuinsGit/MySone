@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">IP Adresi Detayları: {{ $ip }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.user-stats.index') }}">Kullanıcı İstatistikleri</a></li>
        <li class="breadcrumb-item active">IP Detayları</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Genel Bilgiler
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Toplam Mesaj Sayısı:</th>
                            <td>{{ $stats['total_messages'] }}</td>
                        </tr>
                        <tr>
                            <th>Kullanıcı Mesajları:</th>
                            <td>{{ $stats['user_messages'] }}</td>
                        </tr>
                        <tr>
                            <th>AI Mesajları:</th>
                            <td>{{ $stats['ai_messages'] }}</td>
                        </tr>
                        <tr>
                            <th>Benzersiz Ziyaretçi Sayısı:</th>
                            <td>{{ $stats['unique_visitors'] }}</td>
                        </tr>
                        <tr>
                            <th>İlk Mesaj Tarihi:</th>
                            <td>{{ $stats['first_message'] ? $stats['first_message']->created_at->format('d.m.Y H:i:s') : 'Bilgi yok' }}</td>
                        </tr>
                        <tr>
                            <th>Son Mesaj Tarihi:</th>
                            <td>{{ $stats['last_message'] ? $stats['last_message']->created_at->format('d.m.Y H:i:s') : 'Bilgi yok' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-laptop me-1"></i>
                    Cihaz Bilgileri
                </div>
                <div class="card-body">
                    @if($deviceInfo)
                    <table class="table">
                        <tr>
                            <th>Tarayıcı:</th>
                            <td>{{ $deviceInfo['browser'] ?? 'Bilinmiyor' }}</td>
                        </tr>
                        <tr>
                            <th>İşletim Sistemi:</th>
                            <td>{{ $deviceInfo['os'] ?? 'Bilinmiyor' }}</td>
                        </tr>
                        <tr>
                            <th>Cihaz Tipi:</th>
                            <td>{{ $deviceInfo['device_type'] ?? 'Bilinmiyor' }}</td>
                        </tr>
                        <tr>
                            <th>User Agent:</th>
                            <td><small>{{ $deviceInfo['user_agent'] ?? 'Bilinmiyor' }}</small></td>
                        </tr>
                    </table>
                    @else
                    <div class="alert alert-info">Bu IP için cihaz bilgisi bulunamadı.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Bu IP'yi Kullanan Ziyaretçiler
        </div>
        <div class="card-body">
            @if(count($visitorIds) > 0)
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Ziyaretçi ID</th>
                        <th>Ziyaretçi Adı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visitorIds as $visitorId)
                    <tr>
                        <td>{{ str_replace('"', '', $visitorId) }}</td>
                        <td>
                            @php
                                $cleanVisitorId = str_replace('"', '', $visitorId);
                                $visitorName = \DB::table('visitor_names')
                                    ->where('visitor_id', $cleanVisitorId)
                                    ->value('name');
                            @endphp
                            {{ $visitorName ?? 'İsimsiz Ziyaretçi' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.user-stats.visitor-details', ['visitorId' => str_replace('"', '', $visitorId)]) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Ziyaretçi Detaylarını Görüntüle
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="alert alert-info">Bu IP için ziyaretçi bilgisi bulunamadı.</div>
            @endif
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-comments me-1"></i>
            Mesaj Geçmişi
        </div>
        <div class="card-body">
            <table id="messagesTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sohbet ID</th>
                        <th>Gönderen</th>
                        <th>İçerik</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $message)
                    <tr>
                        <td>{{ $message->id }}</td>
                        <td>{{ $message->chat_id }}</td>
                        <td>
                            @if($message->sender == 'user')
                                @php
                                    $visitorId = null;
                                    $visitorName = null;
                                    try {
                                        $metadata = is_array($message->metadata) 
                                            ? $message->metadata 
                                            : json_decode($message->metadata, true);
                                        
                                        if (isset($metadata['visitor_id'])) {
                                            $visitorId = $metadata['visitor_id'];
                                            $visitorName = \DB::table('visitor_names')
                                                ->where('visitor_id', $visitorId)
                                                ->value('name');
                                        }
                                    } catch (\Exception $e) {
                                        // JSON çözümlenemedi veya hata oluştu, sessizce devam et
                                    }
                                @endphp
                                <span class="badge bg-primary">
                                    {{ $visitorName ? $visitorName : 'Kullanıcı' }}
                                </span>
                            @else
                                <span class="badge bg-success">AI</span>
                            @endif
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($message->content, 100) }}</td>
                        <td>{{ $message->created_at->format('d.m.Y H:i:s') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-3">
                {{ $messages->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#messagesTable').DataTable({
            order: [[4, 'desc']],
            pageLength: 25,
            paging: false
        });
    });
</script>
@endsection 