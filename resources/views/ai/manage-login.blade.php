@extends('layouts.app')

@section('title', 'SoneAI Yönetim Girişi')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="bi bi-shield-lock me-2"></i>SoneAI Yönetim Girişi</h4>
                </div>
                <div class="card-body">
                    @if(isset($error))
                    <div class="alert alert-danger">
                        {{ $error }}
                    </div>
                    @endif
                    
                    <form method="POST" action="{{ route('manage.login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Yönetim sayfasına erişmek için şifre gereklidir.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-unlock me-1"></i> Giriş Yap
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('chat') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-chat-dots me-1"></i> Sohbet Sayfasına Dön
                </a>
            </div>
        </div>
    </div>
</div>
@endsection 