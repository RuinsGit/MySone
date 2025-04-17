@extends('back.layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-comments text-primary me-2"></i>
        Sohbet #{{ $chat->id }} <small class="text-muted fs-5">{{ $chat->title }}</small>
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.message-history.index') }}">Mesaj Geçmişi</a></li>
        <li class="breadcrumb-item active">Sohbet Detayları</li>
    </ol>
    
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
                    <div>Bu sohbetteki mesajlar</div>
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
                    <div>AI yanıtları</div>
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
                    <div>Kullanıcı soruları</div>
                    <div class="text-white"><i class="fas fa-user"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card bg-dark text-white mb-4">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="text-center mb-3">
                        <i class="fas fa-exchange-alt fs-1"></i>
                    </div>
                    <div class="text-center">
                        @php
                            $userPercentage = $stats['total_messages'] > 0 ? round(($stats['user_messages'] / $stats['total_messages']) * 100) : 0;
                            $aiPercentage = 100 - $userPercentage;
                        @endphp
                        <div class="fw-bold fs-4">{{ $userPercentage }}% / {{ $aiPercentage }}%</div>
                        <div class="small">Kullanıcı / AI Oranı</div>
                    </div>
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
                        Sohbet Bilgileri
                    </div>
                    <div class="d-flex">
                        <a href="{{ route('admin.message-history.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Tüm Mesajlar
                        </a>
                        @if($visitorId)
                            <a href="{{ route('admin.message-history.user', ['visitorId' => $visitorId]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-user me-1"></i> Kullanıcı Profili
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th style="width: 150px;">Sohbet ID:</th>
                            <td><code class="bg-light p-1 rounded">{{ $chat->id }}</code></td>
                        </tr>
                        <tr>
                            <th>Sohbet Başlığı:</th>
                            <td>
                                <i class="fas fa-comment-dots me-1 text-primary"></i>
                                {{ $chat->title ?: 'İsimsiz Sohbet' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Kullanıcı:</th>
                            <td>
                                @if($visitorId)
                                    <a href="{{ route('admin.message-history.user', ['visitorId' => $visitorId]) }}" class="text-decoration-none">
                                        <i class="fas fa-user-circle me-1 text-primary"></i>
                                        {{ $visitorName ?? 'İsimsiz Ziyaretçi' }}
                                    </a>
                                @else
                                    <span class="text-muted"><i class="fas fa-user me-1"></i> İsimsiz Ziyaretçi</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Oluşturma Tarihi:</th>
                            <td>
                                <i class="far fa-calendar-alt me-1"></i>
                                <span data-bs-toggle="tooltip" title="{{ $chat->created_at->format('d.m.Y H:i:s') }}">
                                    {{ $chat->created_at->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Son Güncelleme:</th>
                            <td>
                                <i class="fas fa-history me-1"></i>
                                <span data-bs-toggle="tooltip" title="{{ $chat->updated_at->format('d.m.Y H:i:s') }}">
                                    {{ $chat->updated_at->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Sohbet Süreci
                </div>
                <div class="card-body">
                    <div class="timeline mb-4">
                        <div class="timeline-item">
                            <div class="timeline-item-marker">
                                <div class="timeline-item-marker-indicator bg-primary-soft">
                                    <i class="fas fa-hourglass-start text-primary"></i>
                                </div>
                            </div>
                            <div class="timeline-item-content">
                                <span class="fw-bold">Sohbet Başlangıcı</span>
                                <span class="text-muted ms-2">
                                    {{ $stats['first_message'] ? $stats['first_message']->format('d.m.Y H:i:s') : 'Bilinmiyor' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-item-marker">
                                <div class="timeline-item-marker-indicator bg-success-soft">
                                    <i class="fas fa-comments text-success"></i>
                                </div>
                            </div>
                            <div class="timeline-item-content">
                                <span class="fw-bold">Mesajlaşma Süreci</span>
                                <span class="text-muted ms-2">
                                    {{ $stats['total_messages'] }} mesaj
                                </span>
                                <div class="mt-2 progress">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $userPercentage }}%" aria-valuenow="{{ $userPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                        {{ $stats['user_messages'] }} Kullanıcı
                                    </div>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $aiPercentage }}%" aria-valuenow="{{ $aiPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                        {{ $stats['ai_messages'] }} AI
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-item-marker">
                                <div class="timeline-item-marker-indicator bg-warning-soft">
                                    <i class="fas fa-hourglass-end text-warning"></i>
                                </div>
                            </div>
                            <div class="timeline-item-content">
                                <span class="fw-bold">Son Aktivite</span>
                                <span class="text-muted ms-2">
                                    {{ $stats['last_message'] ? $stats['last_message']->format('d.m.Y H:i:s') : 'Bilinmiyor' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <div class="badge bg-light text-dark p-2">
                            <span class="text-muted">Toplam Süre: </span>
                            @if($stats['first_message'] && $stats['last_message'])
                                <span class="fw-bold">
                                    {{ $stats['first_message']->diffInMinutes($stats['last_message']) }} dakika
                                </span>
                            @else
                                <span>Belirlenemedi</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-comments me-1"></i>
                Sohbet Mesajları
            </div>
            <div>
                <a href="#" class="btn btn-sm btn-outline-secondary print-chat">
                    <i class="fas fa-print me-1"></i> Yazdır
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="chat-container shadow-sm" style="max-width: 900px; margin: 0 auto;">
                <div class="chat-header bg-light p-3 rounded-top border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-comment-dots me-1"></i>
                                {{ $chat->title ?: 'İsimsiz Sohbet' }}
                            </h5>
                            <div class="text-muted small">
                                <i class="far fa-calendar-alt me-1"></i> {{ $chat->created_at->format('d.m.Y H:i:s') }}
                            </div>
                        </div>
                        <div>
                            @if($visitorId)
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle me-1 text-primary"></i>
                                    <span>{{ $visitorName ?? 'İsimsiz Ziyaretçi' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="chat-messages p-3 bg-light">
                    @foreach($messages as $message)
                        <div class="message-container mb-3 {{ $message->sender == 'user' ? 'text-end' : '' }}">
                            <div class="d-inline-block p-3 rounded shadow-sm {{ $message->sender == 'user' ? 'bg-primary text-white' : 'bg-white' }}" style="max-width: 80%; text-align: left;">
                                <div class="message-header small mb-2 d-flex justify-content-between align-items-center">
                                    <strong>
                                        @if($message->sender == 'user')
                                            <i class="fas fa-user me-1"></i> {{ $visitorName ?? 'Kullanıcı' }}
                                        @else
                                            <i class="fas fa-robot me-1"></i> AI Asistan
                                        @endif
                                    </strong>
                                    <span class="{{ $message->sender == 'user' ? 'text-white' : 'text-muted' }} ms-2 small" data-bs-toggle="tooltip" title="{{ $message->created_at->format('d.m.Y H:i:s') }}">
                                        {{ $message->created_at->format('H:i') }}
                                    </span>
                                </div>
                                <div class="message-content">
                                    {!! nl2br(e($message->content)) !!}
                                </div>
                                <div class="message-footer mt-2 text-end small">
                                    <span class="badge {{ $message->sender == 'user' ? 'bg-light text-primary' : 'bg-primary text-white' }}">
                                        #{{ $message->id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .chat-container {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .message-container {
        margin-bottom: 15px;
    }
    
    .message-content {
        word-break: break-word;
    }
    
    .bg-primary-soft {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-soft {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    /* Timeline stili */
    .timeline {
        position: relative;
        padding-left: 1.5rem;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0.5rem;
        bottom: 0;
        border-left: 1px dashed #e0e0e0;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 1rem;
    }
    
    .timeline-item-marker {
        position: absolute;
        left: -1.5rem;
        width: 1rem;
        height: 1rem;
    }
    
    .timeline-item-marker-indicator {
        width: 2rem;
        height: 2rem;
        border-radius: 100%;
        text-align: center;
        line-height: 2rem;
    }
    
    .timeline-item-content {
        padding-left: 0.5rem;
        padding-bottom: 1rem;
    }

    @media print {
        .breadcrumb, .card-header button, .btn, 
        header, footer, .navbar, .sidebar {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-body {
            padding: 0 !important;
        }
        
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
        
        .chat-container {
            max-width: 100% !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Tooltip'leri etkinleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Yazdırma fonksiyonu
        $('.print-chat').on('click', function(e) {
            e.preventDefault();
            window.print();
        });
    });
</script>
@endsection 