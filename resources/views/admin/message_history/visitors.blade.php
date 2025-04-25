@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-users text-primary me-2"></i>
        Kullanıcı Listesi
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.message-history.index') }}">Mesaj Geçmişi</a></li>
        <li class="breadcrumb-item active">Kullanıcılar</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-1"></i>
                Sistemdeki Kullanıcılar
                <span class="badge bg-primary ms-2">{{ count($groupedVisitors) }}</span>
            </div>
            <div>
                <a href="{{ route('admin.message-history.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Tüm Mesajlar
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="userSearchInput" placeholder="Kullanıcı ara...">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="visitorsTable" class="table table-striped table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Kullanıcı Adı</th>
                            <th>Oturum Sayısı</th>
                            <th>IP Adresi</th>
                            <th class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        @forelse($groupedVisitors as $name => $group)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2 bg-light rounded-circle">
                                        @php
                                            $firstVisitor = $group['first_visitor'];
                                            $avatar = DB::table('visitor_names')
                                                ->where('visitor_id', $firstVisitor->visitor_id)
                                                ->value('avatar');
                                        @endphp
                                        
                                        @if(!empty($avatar))
                                            <img src="{{ $avatar }}" alt="{{ $name }}" class="avatar-img rounded-circle" width="32" height="32">
                                        @else
                                            <span class="avatar-text rounded-circle">{{ strtoupper(substr($name ?? 'A', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $name }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $group['count'] }}</span>
                                <button class="btn btn-sm btn-link text-decoration-none" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse-{{ md5($name) }}" 
                                        aria-expanded="false">
                                    <i class="fas fa-info-circle"></i> Detaylar
                                </button>
                            </td>
                            <td>{{ $group['first_visitor']->ip_address }}</td>
                            <td class="text-center">
                                @php
                                    // Çerez sorununu önlemek için ID'yi düzenle
                                    $cleanVisitorId = preg_replace('/[; ].*$/', '', $group['first_visitor']->visitor_id);
                                @endphp
                                <form method="POST" action="{{ route('admin.message-history.view-user') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="visitor_id" value="{{ $cleanVisitorId }}">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> Mesajları Görüntüle
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="collapse-{{ md5($name) }}">
                            <td colspan="4" class="p-3">
                                <h6 class="border-bottom pb-2 mb-3">{{ $name }} kullanıcısının tüm oturumları ({{ $group['count'] }})</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Ziyaretçi ID</th>
                                                <th>IP Adresi</th>
                                                <th>Kayıt Tarihi</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group['visitors'] as $visitor)
                                            <tr>
                                                <td><code class="small">{{ $visitor->visitor_id }}</code></td>
                                                <td>{{ $visitor->ip_address }}</td>
                                                <td>{{ \Carbon\Carbon::parse($visitor->created_at)->format('d.m.Y H:i:s') }}</td>
                                                <td>
                                                    @php
                                                        // Çerez sorununu önlemek için ID'yi düzenle
                                                        $cleanVisitorId = preg_replace('/[; ].*$/', '', $visitor->visitor_id);
                                                    @endphp
                                                    <form method="POST" action="{{ route('admin.message-history.view-user') }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="visitor_id" value="{{ $cleanVisitorId }}">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i> Görüntüle
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i> Henüz hiç kullanıcı kaydedilmemiş
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .avatar {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-text {
        font-size: 14px;
        font-weight: bold;
        color: #6c757d;
    }
</style>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        var table = $('#visitorsTable').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Ara...",
                lengthMenu: "_MENU_ kayıt göster",
                info: "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                infoEmpty: "Kayıt yok",
                infoFiltered: "(_MAX_ kayıt arasından filtrelendi)",
                paginate: {
                    first: "İlk",
                    last: "Son",
                    next: "Sonraki",
                    previous: "Önceki"
                }
            },
            pageLength: 25,
            responsive: true
        });
        
        // Özel arama kutusu
        $('#userSearchInput').keyup(function() {
            table.search($(this).val()).draw();
        });
    });
</script>
@endsection 