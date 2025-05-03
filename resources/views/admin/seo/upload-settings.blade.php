@extends('back.layouts.app')

@section('title', 'Dosya Yükleme Ayarları')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">PHP Dosya Yükleme Ayarları</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.seo.index') }}">SEO Ayarları</a></li>
        <li class="breadcrumb-item active">Dosya Yükleme Ayarları</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-cogs me-1"></i>
                PHP Konfigürasyon Ayarları
            </div>
            <div>
                <a href="{{ route('admin.seo.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> SEO Ayarlarına Dön
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Bu sayfada sunucunuzun dosya yükleme ile ilgili PHP ayarlarını görebilirsiniz. Eğer limitleri artırmak istiyorsanız, php.ini dosyanızı düzenlemeniz veya hosting sağlayıcınızla iletişime geçmeniz gerekebilir.
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Ayar</th>
                            <th>Değer</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>upload_max_filesize</td>
                            <td>{{ $settings['upload_max_filesize'] }}</td>
                            <td>Tek bir dosyanın maksimum yükleme boyutu</td>
                        </tr>
                        <tr>
                            <td>post_max_size</td>
                            <td>{{ $settings['post_max_size'] }}</td>
                            <td>POST verisinin maksimum boyutu (birden fazla dosya yüklerken bu değer upload_max_filesize'dan büyük olmalıdır)</td>
                        </tr>
                        <tr>
                            <td>max_file_uploads</td>
                            <td>{{ $settings['max_file_uploads'] }}</td>
                            <td>Tek bir istek ile yüklenebilecek maksimum dosya sayısı</td>
                        </tr>
                        <tr>
                            <td>max_execution_time</td>
                            <td>{{ $settings['max_execution_time'] }} saniye</td>
                            <td>Bir PHP betiğinin çalışabileceği maksimum süre (büyük dosyalar için artırılabilir)</td>
                        </tr>
                        <tr>
                            <td>memory_limit</td>
                            <td>{{ $settings['memory_limit'] }}</td>
                            <td>Bir PHP betiğinin kullanabileceği maksimum bellek miktarı</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-lightbulb me-1"></i>
            Dosya Yükleme Sorunlarını Çözme
        </div>
        <div class="card-body">
            <h5>Genel Sorun Giderme Adımları</h5>
            <ol>
                <li>Dosya boyutu PHP limitlerinden büyük mü kontrol edin (upload_max_filesize ve post_max_size)</li>
                <li>public/storage dizininin yazılabilir olduğundan emin olun</li>
                <li>Symbolic Link oluşturulduğundan emin olun: <code>php artisan storage:link</code></li>
                <li>Yeterli disk alanı olduğunu kontrol edin</li>
                <li>Geçici dosya dizininin (upload_tmp_dir) yazılabilir olduğunu doğrulayın</li>
                <li>Form'a <code>enctype="multipart/form-data"</code> eklendiğinden emin olun</li>
            </ol>
            
            <div class="mt-3">
                <h5>PHP Limitlerini Artırmak</h5>
                <p>Eğer hosting kontrolünüz varsa, php.ini dosyasında aşağıdaki değerleri artırabilirsiniz:</p>
                <pre><code>upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M</code></pre>
                
                <p>Shared hosting kullanıyorsanız, bir .htaccess dosyası veya .user.ini dosyası ile bu değerleri değiştirebilirsiniz:</p>
                <pre><code># .htaccess örneği
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection 