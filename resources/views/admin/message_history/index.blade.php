@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Mesaj Geçmişi</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Mesaj Geçmişi</li>
    </ol>
    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Toplam Mesaj</h5>
                            <div class="small opacity-75">Sistem genelinde</div>
                        </div>
                        <div class="fs-2 fw-bold">{{ $messages->total() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Aktif Kullanıcılar</h5>
                            <div class="small opacity-75">Farklı ziyaretçi</div>
                        </div>
                        <div class="fs-2 fw-bold">{{ count($groupedVisitors) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-search me-1"></i>
                Arama ve Filtreleme
            </div>
            <div>
                <a href="{{ route('admin.message-history.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-redo me-1"></i> Tümünü Göster
                </a>
                <a href="{{ route('admin.message-history.visitors') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-users me-1"></i> Kullanıcılar
                </a>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse" aria-expanded="true" aria-controls="searchCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="card-body collapse show" id="searchCollapse">
            <form action="{{ route('admin.message-history.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-comment"></i></span>
                        <input type="text" name="search" id="search" class="form-control" value="{{ $search }}" placeholder="Mesaj içeriğinde ara...">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-filter"></i></span>
                        <select name="filter_by" id="filter_by" class="form-select">
                            <option value="all" {{ $filterBy == 'all' ? 'selected' : '' }}>Tümü</option>
                            <option value="user" {{ $filterBy == 'user' ? 'selected' : '' }}>Kullanıcı Mesajları</option>
                            <option value="ai" {{ $filterBy == 'ai' ? 'selected' : '' }}>AI Mesajları</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <select name="user_name" id="user_name" class="form-select">
                            <option value="">Tüm Kullanıcılar</option>
                            @foreach($groupedVisitors as $name => $group)
                                <option value="{{ $name }}" {{ isset($userName) && $userName == $name ? 'selected' : '' }}>
                                    {{ $name }} ({{ $group['count'] }} oturum)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-primary me-2 flex-grow-1">
                        <i class="fas fa-search me-1"></i> Ara
                    </button>
                    <a href="{{ route('admin.message-history.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
                
                @if(!empty($visitorId))
                <input type="hidden" name="visitor_id" value="{{ $visitorId }}">
                @endif
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-comments me-1"></i>
            Mesaj Listesi
            <span class="badge bg-primary ms-2">{{ $messages->total() }} mesaj</span>
        </div>
        <div class="card-body">
            <table id="messagesTable" class="table table-striped table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Kullanıcı</th>
                        <th class="text-center">Sohbet ID</th>
                        <th class="text-center">Gönderen</th>
                        <th>İçerik</th>
                        <th>Tarih</th>
                        <th class="text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $message)
                    <tr>
                        <td class="text-center">{{ $message->id }}</td>
                        <td>
                            @if($message->visitor_name)
                                @php
                                    $visitorId = null;
                                    if (isset($message->metadata) && is_array($message->metadata) && isset($message->metadata['visitor_id'])) {
                                        $visitorId = $message->metadata['visitor_id'];
                                    } elseif (is_string($message->metadata)) {
                                        $decoded = json_decode($message->metadata, true);
                                        if (is_array($decoded) && isset($decoded['visitor_id'])) {
                                            $visitorId = $decoded['visitor_id'];
                                        }
                                    }
                                    // Visitor ID'yi temizle
                                    $cleanVisitorId = $visitorId ? preg_replace('/[; ].*$/', '', $visitorId) : '';
                                    
                                    // Avatar bilgisini al
                                    $avatar = null;
                                    if ($cleanVisitorId) {
                                        $avatar = \DB::table('visitor_names')
                                            ->where('visitor_id', $cleanVisitorId)
                                            ->value('avatar');
                                    }
                                @endphp
                                @if($visitorId)
                                <form method="POST" action="{{ route('admin.message-history.view-user') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="visitor_id" value="{{ $cleanVisitorId }}">
                                    <button type="submit" class="btn btn-link text-decoration-none p-0 border-0 text-start d-flex align-items-center">
                                        @if(!empty($avatar))
                                            <img src="{{ $avatar }}" alt="{{ $message->visitor_name }}" class="me-1 rounded-circle" width="24" height="24">
                                        @else
                                            <i class="fas fa-user-circle me-1 text-primary"></i>
                                        @endif
                                        {{ $message->visitor_name }}
                                    </button>
                                </form>
                                @else
                                <span class="text-muted d-flex align-items-center">
                                    <i class="fas fa-user me-1"></i> {{ $message->visitor_name }}
                                </span>
                                @endif
                            @else
                                <span class="text-muted d-flex align-items-center">
                                    <i class="fas fa-user me-1"></i> Anonim
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.message-history.chat', ['chatId' => $message->chat_id]) }}" class="badge bg-secondary text-decoration-none">
                                {{ $message->chat_id }}
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $message->sender == 'user' ? 'primary' : 'success' }}">
                                {{ $message->sender == 'user' ? ($message->visitor_name ?: 'Kullanıcı') : 'AI' }}
                            </span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($message->content, 100) }}</td>
                        <td>
                            <span data-bs-toggle="tooltip" title="{{ $message->created_at->format('d.m.Y H:i:s') }}">
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center">
                                <a href="{{ route('admin.message-history.chat', ['chatId' => $message->chat_id]) }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Sohbeti Görüntüle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @php
                                    $visitorId = null;
                                    if (isset($message->metadata) && is_array($message->metadata) && isset($message->metadata['visitor_id'])) {
                                        $visitorId = $message->metadata['visitor_id'];
                                    } elseif (is_string($message->metadata)) {
                                        $decoded = json_decode($message->metadata, true);
                                        if (is_array($decoded) && isset($decoded['visitor_id'])) {
                                            $visitorId = $decoded['visitor_id'];
                                        }
                                    }
                                    // Visitor ID'yi temizle
                                    $cleanVisitorId = $visitorId ? preg_replace('/[; ].*$/', '', $visitorId) : '';
                                @endphp
                                @if($visitorId)
                                <form method="POST" action="{{ route('admin.message-history.view-user') }}" class="d-inline ms-1">
                                    @csrf
                                    <input type="hidden" name="visitor_id" value="{{ $cleanVisitorId }}">
                                    <button type="submit" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Kullanıcı Mesajları">
                                        <i class="fas fa-user"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Toplam {{ $messages->total() }} mesaj | Sayfa {{ $messages->currentPage() }}/{{ $messages->lastPage() }}</span>
                </div>
                <div>
                    @if($messages->hasPages())
                        {{ $messages->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('#messagesTable').DataTable({
            order: [[5, 'desc']],
            pageLength: 25,
            paging: false,
            info: false,
            searching: false
        });
        
        // Tooltip'leri etkinleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endsection 