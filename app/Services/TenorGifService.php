<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TenorGifService
{
    /**
     * Tenor API anahtarı
     */
    protected $apiKey;
    
    /**
     * Tenor API URL
     */
    protected $apiUrl = "https://tenor.googleapis.com/v2";
    
    /**
     * Cache süresi (dakika)
     */
    protected $cacheDuration = 1440; // 24 saat
    
    /**
     * Varsayılan dil
     */
    protected $locale = "tr_TR";
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = env('TENOR_API_KEY', '');
    }
    
    /**
     * API anahtarının geçerli olup olmadığını kontrol et
     * 
     * @return bool API anahtarı geçerli mi
     */
    public function hasValidApiKey()
    {
        return !empty($this->apiKey);
    }
    
    /**
     * Belirli bir sorguyla GIF ara ve rastgele bir GIF döndür
     *
     * @param string $query Arama sorgusu
     * @param int $limit Kaç GIF getirileceği
     * @return string|null GIF URL'si veya hata durumunda null
     */
    public function getRandomGif($query, $limit = 10)
    {
        try {
            if (!$this->hasValidApiKey()) {
                Log::error('Tenor API anahtarı bulunamadı');
                return null;
            }
            
            // Cache key oluştur
            $cacheKey = "tenor_gif_{$query}_{$limit}";
            
            // Cache'de var mı kontrol et
            if (Cache::has($cacheKey)) {
                $gifs = Cache::get($cacheKey);
                
                // Cache'den rastgele bir GIF al
                if (!empty($gifs)) {
                    return $gifs[array_rand($gifs)];
                }
            }
            
            // API parametreleri
            $params = [
                'key' => $this->apiKey,
                'q' => $query,
                'limit' => $limit,
                'locale' => $this->locale,
                'media_filter' => 'gif,tinygif'
            ];
            
            // API isteği gönder
            $url = "{$this->apiUrl}/search";
            
            Log::info('Tenor API isteği gönderiliyor', [
                'query' => $query,
                'limit' => $limit
            ]);
            
            $response = Http::get($url, $params);
            
            // Başarılı yanıt kontrol et
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['results']) && !empty($data['results'])) {
                    $gifUrls = [];
                    
                    // Tüm GIF URL'lerini topla
                    foreach ($data['results'] as $result) {
                        if (isset($result['media_formats']['gif']['url'])) {
                            $gifUrls[] = $result['media_formats']['gif']['url'];
                        } elseif (isset($result['media_formats']['tinygif']['url'])) {
                            $gifUrls[] = $result['media_formats']['tinygif']['url'];
                        }
                    }
                    
                    // Sonuçları önbelleğe al
                    if (!empty($gifUrls)) {
                        Cache::put($cacheKey, $gifUrls, $this->cacheDuration * 60);
                        
                        // Rastgele bir GIF URL'si döndür
                        return $gifUrls[array_rand($gifUrls)];
                    }
                }
                
                Log::warning('Tenor API için GIF bulunamadı', [
                    'query' => $query
                ]);
                
                return null;
            } else {
                $errorData = $response->json();
                Log::error('Tenor API hatası', [
                    'status' => $response->status(),
                    'error' => $errorData
                ]);
                
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Tenor API istisna hatası: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return null;
        }
    }
    
    /**
     * Kedi GIF'i al
     *
     * @return string|null GIF URL'si
     */
    public function getCatGif()
    {
        return $this->getRandomGif('kedi');
    }
    
    /**
     * Belirli bir anahtar kelime/kategori için GIF al
     * 
     * @param string $category Kategori adı ("komik", "hayvan", "film" vb.)
     * @return string|null GIF URL'si
     */
    public function getCategoryGif($category)
    {
        $categoryQueries = [
            // Hayvanlar
            'kedi' => 'komik kedi',
            'köpek' => 'sevimli köpek',
            'penguen' => 'penguen komik',
            'kuş' => 'komik kuş',
            'at' => 'at koşu',
            'aslan' => 'aslan',
            'ejderha' => 'ejderha',
            'ayı' => 'ayı komik',
            'panda' => 'panda sevimli',
            'kaplan' => 'kaplan',
            'balık' => 'komik balık',
            'fil' => 'fil sevimli',
            'maymun' => 'komik maymun',
            
            // Ünlüler
            'ünlü' => 'ünlü komik',
            'oyuncu' => 'actor funny',
            'şarkıcı' => 'singer funny',
            
            // Film ve diziler
            'dizi' => 'dizi komik sahne',
            'film' => 'film komik sahne',
            'anime' => 'anime gif',
            'star wars' => 'star wars',
            'marvel' => 'marvel',
            'süper kahraman' => 'superhero',
            
            // Duygular
            'komik' => 'komik gif',
            'gülmek' => 'kahkaha',
            'ağlamak' => 'ağlama',
            'korku' => 'korku tepki',
            'şaşkın' => 'şaşırma tepki',
            'sinir' => 'sinirli tepki',
            'sevinç' => 'sevinçli dans',
            'dans' => 'komik dans',
            'alkış' => 'alkış bravo',
            'zafer' => 'zafer kutlama',
            
            // Doğa ve Hava Durumu
            'doğa' => 'doğa manzara',
            'yağmur' => 'yağmur gif',
            'kar' => 'kar yağışı',
            'güneş' => 'güneşli hava',
            'fırtına' => 'fırtına',
            
            // Spor
            'futbol' => 'futbol gol',
            'basketbol' => 'basketbol smaç',
            'tenis' => 'tenis komik',
            'spor' => 'komik spor',
            
            // Türk kültürü
            'türk' => 'türk komik',
            'çay' => 'çay içmek',
            'kebap' => 'kebap yemek',
            
            // Özel günler
            'doğum günü' => 'doğum günü kutlama',
            'yeni yıl' => 'yeni yıl kutlama',
            'bayram' => 'bayram kutlama',
            
            // Diğer
            'araba' => 'araba hızlı',
            'uzay' => 'uzay',
            'robot' => 'robot komik',
            'bebek' => 'sevimli bebek',
            'parti' => 'parti eğlence',
            'oyun' => 'video game funny',
            'bilgisayar' => 'bilgisayar komik'
        ];
        
        $category = mb_strtolower(trim($category), 'UTF-8');
        
        // Eğer kategori direkt olarak tanımlıysa, o kategorinin sorgusunu kullan
        if (isset($categoryQueries[$category])) {
            return $this->getRandomGif($categoryQueries[$category]);
        }
        
        // Değilse direkt olarak anahtar kelimeyi kullan
        return $this->getRandomGif($category);
    }
    
    /**
     * Belirli bir duygu durumu için GIF al
     *
     * @param string $emotion Duygu durumu (mutlu, üzgün, vb.)
     * @return string|null GIF URL'si
     */
    public function getEmotionGif($emotion)
    {
        $emotionQueries = [
            // Pozitif duygular
            'happy' => ['mutlu dans', 'gülen yüz', 'komik tepki', 'kahkaha', 'sevimli mutlu', 'neşeli', 'mutlu kedi', 'mutlu köpek'],
            'excited' => ['heyecan', 'coşku', 'kutlama', 'wow tepki', 'süper', 'çılgın sevinç', 'alkış', 'tebrik'],
            'love' => ['kalp', 'sevgi', 'sarılma', 'aşk', 'öpücük', 'romantik', 'sevgi dolu', 'sevimli çift'],
            'cool' => ['havalı', 'tarz', 'mükemmel', 'cool', 'mükemmellik', 'güneş gözlüğü', 'swagger', 'profesyonel'],
            
            // Negatif duygular
            'angry' => ['sinirli', 'öfke', 'kızgın yüz', 'sinir krizi', 'bağırmak', 'öfke nöbeti', 'kırmızı yüz', 'masaya yumruk'],
            'sad' => ['üzgün', 'ağlayan', 'mutsuz', 'depresif', 'hüzünlü', 'gözyaşı', 'üzgün kedi', 'ağlayan köpek'],
            'confused' => ['kafa karışık', 'ne', 'anlamadım', 'şaşkın bakış', 'garip', 'kafası karışmış', 'soru işareti', 'anlamama'],
            
            // Diğer durumlar
            'surprised' => ['şok', 'şaşırma', 'vay canına', 'ağzı açık', 'tepki', 'sürpriz', 'inanamama', 'hayret'],
            'sleepy' => ['uyku', 'esnemek', 'uyuklama', 'yorgun', 'yatağa atlama', 'uyuyan kedi', 'yorgun köpek', 'uyku vakti'],
            'lol' => ['gülmek', 'komik', 'kahkaha', 'espri', 'absürt', 'gülme krizleri', 'komedi', 'mizah'],
            'thumbsup' => ['başparmak yukarı', 'onay', 'tamam', 'aferin', 'bravo', 'beğenme', 'teşekkür', 'tebrik'],
            'thumbsdown' => ['başparmak aşağı', 'hayır', 'beğenmedim', 'olmadı', 'red', 'ret', 'beğenmeme', 'kaş çatma'],
            'facepalm' => ['yüz avuç içi', 'çaresiz', 'inanamıyorum', 'saçmalık', 'of ya', 'başını tutma', 'utanç', 'pes etme'],
            'crying' => ['ağlamak', 'gözyaşı', 'hüngür', 'üzüntü', 'mahzun', 'ağlama krizi', 'duygusallık', 'çok üzgün'],
            'dancing' => ['dans etmek', 'parti', 'eğlence', 'ritim', 'kıvırmak', 'dans eden hayvan', 'bale', 'davetli dans'],
            'shrug' => ['omuz silkme', 'bilmiyorum', 'umurumda değil', 'ne yapayım', 'boş ver', 'bilmeme', 'kararsızlık', 'önemsiz'],
            'wink' => ['göz kırpma', 'flört', 'şaka', 'gizli işaret', 'anlamlı bakış', 'sır', 'iğneleme', 'komplo'],
            
            // Ek duygular
            'embarrassed' => ['utanmak', 'mahcup', 'kızarmak', 'utanç', 'sıkılgan', 'mahcubiyet', 'utangaç kedi', 'sıkılma'],
            'sarcastic' => ['alaycı', 'ironi', 'dalga geçme', 'kinaye', 'ince alay', 'gizli alay', 'zeki şaka', 'kaş kaldırma'],
            'proud' => ['gurur', 'övünç', 'başarı', 'kıvanç', 'madalya', 'onur', 'gurur duyma', 'başarı sevinci'],
            'bored' => ['sıkılmak', 'esneme', 'can sıkıntısı', 'boş gözler', 'ilgisizlik', 'telefon bakmak', 'ödevden sıkılma', 'beklemekten sıkılma'],
            'scared' => ['korku', 'irkilme', 'kaçmak', 'korkan kedi', 'film korkusu', 'tırsmak', 'saklanma', 'ödü patlamak'],
            'hungry' => ['açlık', 'yemek istemek', 'yiyecek bakışı', 'salya akması', 'hamburger istemek', 'pizza', 'lokantaya koşmak', 'açlıktan ölmek']
        ];
        
        // Duygu türüne bağlı olarak arama stratejisini belirle
        $searchStrategy = 'category'; // Varsayılan strateji: kategori arama
        
        // Özel durumlar için strateji seçimi
        $specificEmotions = ['angry', 'happy', 'sad', 'surprised', 'confused', 'lol', 'love'];
        if (in_array($emotion, $specificEmotions)) {
            // Önemli duygular için %70 kategori %30 genel arama yap
            $searchStrategy = (mt_rand(1, 100) <= 70) ? 'category' : 'search';
        } else {
            // Diğer duygular için %50 kategori %50 genel arama yap
            $searchStrategy = (mt_rand(1, 100) <= 50) ? 'category' : 'search';
        }
        
        // Eğer duygu tanımı yapılmamışsa veya emotionQueries içinde yoksa
        if (!isset($emotionQueries[$emotion])) {
            return $this->getRandomGif('reaction');
        }
        
        // Duygu için tanımlanmış anahtar kelimelerden rastgele bir seçki yap
        $queryOptions = $emotionQueries[$emotion];
        
        // Daha geniş kelime öbekleri oluştur
        $selectedQueries = [];
        
        // Rastgele 2-3 sorgu seç (daha zengin bir arama için)
        $numQueries = mt_rand(2, 3);
        $shuffledOptions = $queryOptions;
        shuffle($shuffledOptions);
        
        for ($i = 0; $i < $numQueries && $i < count($shuffledOptions); $i++) {
            $selectedQueries[] = $shuffledOptions[$i];
        }
        
        // Seçilen sorguları birleştir
        $query = implode(' ', $selectedQueries);
        
        // Arama stratejisine göre GIF getir
        if ($searchStrategy == 'category') {
            // Önce kategori temelli GIF'ler dene
            $gifUrl = $this->getCategoryGif($emotion);
            
            // Eğer bulunamazsa, oluşturulan sorguyla ara
            if (!$gifUrl) {
                $gifUrl = $this->getRandomGif($query);
            }
        } else {
            // Doğrudan sorgu temelli arama yap
            $gifUrl = $this->getRandomGif($query);
            
            // Eğer bulunamazsa, kategori dene
            if (!$gifUrl) {
                $gifUrl = $this->getCategoryGif($emotion);
            }
        }
        
        // Yine de bulunamazsa, son çare olarak duygu adını kullanarak ara
        if (!$gifUrl) {
            $gifUrl = $this->getRandomGif($emotion);
        }
        
        // Duygu arama başarısını logla
        Log::debug('Duygu durumu GIF arama sonuçları', [
            'emotion' => $emotion,
            'search_strategy' => $searchStrategy,
            'query' => $query,
            'success' => !empty($gifUrl)
        ]);
        
        return $gifUrl;
    }
    
    /**
     * Rastgele komik bir kedi GIF'i al
     *
     * @return string|null GIF URL'si
     */
    public function getFunnyCatGif()
    {
        return $this->getRandomGif('komik kedi');
    }
    
    /**
     * Locale ayarla
     *
     * @param string $locale Yerel kod (örn. "tr_TR")
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }
    
    /**
     * Cache süresini ayarla
     *
     * @param int $minutes Dakika cinsinden cache süresi
     * @return $this
     */
    public function setCacheDuration($minutes)
    {
        $this->cacheDuration = $minutes;
        return $this;
    }
} 