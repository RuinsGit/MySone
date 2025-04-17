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
                <span class="badge bg-primary ms-2">{{ count($visitors) }}</span>
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
                            <th>Ziyaretçi ID</th>
                            <th>IP Adresi</th>
                            <th class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        @forelse($visitors as $visitor)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2 bg-light rounded-circle">
                                        <span class="avatar-text rounded-circle">{{ strtoupper(substr($visitor->name ?? 'A', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $visitor->name }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td><code class="bg-light p-1 rounded">{{ $visitor->visitor_id }}</code></td>
                            <td>{{ $visitor->ip_address }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.message-history.user', ['visitorId' => $visitor->visitor_id]) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i> Mesajları Görüntüle
                                </a>
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