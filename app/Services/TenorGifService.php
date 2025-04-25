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
                    $selectedGif = $gifs[array_rand($gifs)];
                    
                    // GIF URL kontrolü - URL olduğundan emin ol
                    if (filter_var($selectedGif, FILTER_VALIDATE_URL)) {
                        return $selectedGif;
                    } else {
                        // URL değilse, cache'den temizle ve yeniden iste
                        Cache::forget($cacheKey);
                        // Recursive olarak yeniden çağırma riski nedeniyle null dön
                        Log::warning('Önbellekteki GIF geçerli bir URL değil', ['query' => $query, 'gif' => $selectedGif]);
                        return null;
                    }
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
                            $gifUrl = $result['media_formats']['gif']['url'];
                            // URL kontrolü yap
                            if (filter_var($gifUrl, FILTER_VALIDATE_URL)) {
                                $gifUrls[] = $gifUrl;
                            }
                        } elseif (isset($result['media_formats']['tinygif']['url'])) {
                            $gifUrl = $result['media_formats']['tinygif']['url'];
                            // URL kontrolü yap
                            if (filter_var($gifUrl, FILTER_VALIDATE_URL)) {
                                $gifUrls[] = $gifUrl;
                            }
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
            'kedi' => 'çizgi film kedi, cartoon cat',
            'köpek' => 'çizgi film köpek, cartoon dog',
            'penguen' => 'çizgi film penguen, cartoon penguin',
            'kuş' => 'çizgi film kuş, cartoon bird',
            'at' => 'çizgi film at, cartoon horse',
            'aslan' => 'çizgi film aslan, cartoon lion',
            'ejderha' => 'çizgi film ejderha, cartoon dragon',
            'ayı' => 'çizgi film ayı, cartoon bear',
            'panda' => 'çizgi film panda, cartoon panda',
            'kaplan' => 'çizgi film kaplan, cartoon tiger',
            'balık' => 'çizgi film balık, cartoon fish',
            'fil' => 'çizgi film fil, cartoon elephant',
            'maymun' => 'çizgi film maymun, cartoon monkey',

            // Ünlüler
            'ünlü' => 'çizgi film ünlü, cartoon celebrity',
            'oyuncu' => 'çizgi film oyuncu, cartoon actor',
            'şarkıcı' => 'çizgi film şarkıcı, cartoon singer',

            // Film ve diziler
            'dizi' => 'çizgi film dizi, cartoon series',
            'film' => 'çizgi film film, cartoon movie',
            'anime' => 'çizgi film gif, cartoon animation gif',  // "anime gif" yerine genelleştirdim
            'star wars' => 'çizgi film star wars, star wars cartoon',
            'marvel' => 'çizgi film marvel, marvel cartoon',
            'süper kahraman' => 'çizgi film süper kahraman, cartoon superhero',

            // Duygular
            'komik' => 'komik çizgi film, funny cartoon',
            'gülmek' => 'gülen çizgi film, cartoon laughing',
            'ağlamak' => 'ağlayan çizgi film, cartoon crying',
            'korku' => 'korkmuş çizgi film, cartoon scared',
            'şaşkın' => 'şaşkın çizgi film, cartoon surprised',
            'sinir' => 'sinirli çizgi film, cartoon angry',
            'sevinç' => 'mutlu çizgi film, cartoon happy',
            'dans' => 'dans eden çizgi film, cartoon dancing',
            'alkış' => 'alkışlayan çizgi film, cartoon clapping',
            'zafer' => 'zafer kazanmış çizgi film, cartoon victory',

            // Doğa ve Hava Durumu
            'doğa' => 'çizgi film doğa, cartoon nature',
            'yağmur' => 'çizgi film yağmur, cartoon rain',
            'kar' => 'çizgi film kar, cartoon snow',
            'güneş' => 'çizgi film güneş, cartoon sun',
            'fırtına' => 'çizgi film fırtına, cartoon storm',

            // Spor
            'futbol' => 'çizgi film futbol, cartoon football',
            'basketbol' => 'çizgi film basketbol, cartoon basketball',
            'tenis' => 'çizgi film tenis, cartoon tennis',
            'spor' => 'çizgi film spor, cartoon sport',

            // Türk kültürü
            'türk' => 'çizgi film türk karakter, cartoon turkish character',
            'çay' => 'çizgi film çay, cartoon tea',
            'kebap' => 'çizgi film kebap, cartoon kebab',

            // Özel günler
            'doğum günü' => 'çizgi film doğum günü, cartoon birthday',
            'yeni yıl' => 'çizgi film yeni yıl, cartoon new year',
            'bayram' => 'çizgi film bayram, cartoon holiday',

            // Diğer
            'araba' => 'çizgi film araba, cartoon car',
            'uzay' => 'çizgi film uzay, cartoon space',
            'robot' => 'çizgi film robot, cartoon robot',
            'bebek' => 'çizgi film bebek, cartoon baby',
            'parti' => 'çizgi film parti, cartoon party',
            'oyun' => 'çizgi film oyun, cartoon game',
            'bilgisayar' => 'çizgi film bilgisayar, cartoon computer',
        ];
        
        $category = mb_strtolower(trim($category), 'UTF-8');
        
        // Tercih edilen stiller
        $styles = ['anime', 'çizgi film', 'animasyon', 'cartoon', 'disney', 'pixar'];
        $randomStyle = $styles[array_rand($styles)];
        
        // Eğer kategori direkt olarak tanımlıysa, o kategorinin sorgusunu kullan
        if (isset($categoryQueries[$category])) {
            return $this->getRandomGif($categoryQueries[$category]);
        }
        
        // Değilse direkt olarak anahtar kelimeyi stil ile birleştirerek kullan
        return $this->getRandomGif($randomStyle . ' ' . $category);
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
            'happy' => ['happy cartoon', 'çizgi film mutlu', 'happy animation', 'mutlu animasyon', 'cartoon laughter', 'çizgi film kahkaha', 'cute happy animal', 'sevimli mutlu hayvan'],
            'excited' => ['excited cartoon', 'çizgi film heyecan', 'excited animation', 'heyecanlı animasyon', 'cartoon celebration', 'çizgi film kutlama', 'cheering animal', 'tezahürat yapan hayvan'],
            'love' => ['cartoon love', 'çizgi film aşk', 'romantic animation', 'romantik animasyon', 'hugging animals', 'sarılmış hayvanlar', 'cute couple cartoon', 'sevimli çift çizgi film'],
            'cool' => ['cool cartoon', 'çizgi film havalı', 'cool animation character', 'havalı animasyon karakteri', 'sunglasses cartoon', 'güneş gözlüklü çizgi film', 'stylish animal', 'tarz hayvan'],

            // Negatif duygular
            'angry' => ['angry cartoon', 'furious animation', 'öfke dolu animasyon', 'angry animal'],
            'sad' => ['sad cartoon', 'çizgi film üzgün', 'crying animation', 'ağlayan animasyon', 'teary animal', 'gözyaşlı hayvan', 'depressed cartoon', 'çizgi film depresyon'],
            'confused' => ['confused cartoon', 'çizgi film şaşkın', 'confused animation', 'kafa karışık animasyon', 'surprised animal', 'şaşkın hayvan', 'what cartoon', 'çizgi film ne oluyor'],

            // Diğer durumlar
            'surprised' => ['surprised cartoon', 'çizgi film şaşırma', 'shock animation', 'şok animasyon', 'surprised animal', 'şaşırmış hayvan', 'cartoon reaction', 'çizgi film tepki'],
            'sleepy' => ['sleepy cartoon', 'çizgi film uykulu', 'yawning animation', 'esneyen animasyon', 'sleeping animal', 'uyuyan hayvan', 'tired cartoon', 'yorgun çizgi film'],
            'lol' => ['funny cartoon', 'komik çizgi film', 'laughing animation', 'kahkaha atan animasyon', 'laughing animal', 'gülen hayvan', 'cartoon joke', 'çizgi film espri'],
            'thumbsup' => ['thumbs up cartoon', 'çizgi film başparmak yukarı', 'approval animation', 'onay animasyon', 'ok animal', 'onaylayan hayvan', 'great cartoon', 'harika çizgi film'],
            'thumbsdown' => ['thumbs down cartoon', 'çizgi film başparmak aşağı', 'rejected animation', 'red edilen animasyon', 'no animal', 'hayır diyen hayvan', 'dislike cartoon', 'çizgi film beğenmeme'],
            'facepalm' => ['facepalm cartoon', 'çizgi film yüzünü kapatan', 'embarrassed animation', 'utanmış animasyon', 'facepalm animal', 'yüzünü kapatan hayvan', 'oops cartoon', 'çizgi film utanç'],
            'crying' => ['crying cartoon', 'çizgi film ağlama', 'tears animation', 'gözyaşı animasyon', 'sad animal', 'üzgün hayvan', 'crying character', 'ağlayan karakter'],
            'dancing' => ['dancing cartoon', 'çizgi film dans', 'dancing animation', 'dans eden animasyon', 'party animal', 'parti yapan hayvan', 'dance cartoon', 'çizgi film dans'],
            'shrug' => ['shrug cartoon', 'çizgi film omuz silkme', 'don’t know animation', 'bilmiyorum animasyon', 'shrugging animal', 'omuz silken hayvan', 'meh cartoon', 'çizgi film umursamaz'],
            'wink' => ['wink cartoon', 'çizgi film göz kırpma', 'flirty animation', 'flörtöz animasyon', 'winking animal', 'göz kırpan hayvan', 'playful cartoon', 'oyuncu çizgi film'],

            // Ek duygular
            'embarrassed' => ['embarrassed cartoon', 'çizgi film utanmış', 'blushing animation', 'kızarmış animasyon', 'shy animal', 'utangaç hayvan', 'awkward cartoon', 'çizgi film mahcup'],
            'sarcastic' => ['sarcastic cartoon', 'alaycı çizgi film', 'ironic animation', 'ironik animasyon', 'smirking animal', 'alaycı hayvan', 'mocking cartoon', 'çizgi film alay'],
            'proud' => ['proud cartoon', 'gururlu çizgi film', 'achievement animation', 'başarı animasyon', 'victorious animal', 'zafer kazanan hayvan', 'medal cartoon', 'madalyalı çizgi film'],
            'bored' => ['bored cartoon', 'sıkılmış çizgi film', 'yawning animation', 'esneyen animasyon', 'bored animal', 'sıkılmış hayvan', 'waiting cartoon', 'bekleyen çizgi film'],
            'scared' => ['scared cartoon', 'korkmuş çizgi film', 'fearful animation', 'korku dolu animasyon', 'scared animal', 'korkmuş hayvan', 'spooked cartoon', 'ürkek çizgi film'],
            'hungry' => ['hungry cartoon', 'aç çizgi film', 'eating animation', 'yemek animasyon', 'hungry animal', 'aç hayvan', 'drooling cartoon', 'ağzı sulanan çizgi film'],

        ];
        
        // Tercih edilen stil eklemeleri (her duygu için ek arama terimleri)
        $styleTerms = [ 'çizgi film', 'animasyon', 'cartoon', 'hayvan', 'kedi', 'köpek', 'penguen', 'disney', 'pixar'];
        
        // Rastgele bir stil seç
        $randomStyle = $styleTerms[array_rand($styleTerms)];
        
        // Duygu türüne bağlı olarak arama stratejisini belirle
        $searchStrategy = 'category'; // Varsayılan strateji: kategori arama
        
        // Özel durumlar için strateji seçimi
        $specificEmotions = ['angry', 'happy', 'sad', 'surprised', 'confused', 'lol', 'love', 'embarrassed', 'proud', 'bored', 'scared', 'hungry'];
        if (in_array($emotion, $specificEmotions)) {
            // Önemli duygular için %70 kategori %30 genel arama yap
            $searchStrategy = (mt_rand(1, 100) <= 70) ? 'category' : 'search';
        } else {
            // Diğer duygular için %50 kategori %50 genel arama yap
            $searchStrategy = (mt_rand(1, 100) <= 50) ? 'category' : 'search';
        }
        
        // Eğer duygu tanımı yapılmamışsa veya emotionQueries içinde yoksa
        if (!isset($emotionQueries[$emotion])) {
            // Fallback olarak anime/çizgi film tepki GIF'i dönelim
            $fallbackQuery = $randomStyle . " reaction";
            $fallbackGif = $this->getRandomGif($fallbackQuery);
            
            // URL kontrolü
            if ($fallbackGif && filter_var($fallbackGif, FILTER_VALIDATE_URL)) {
                return $fallbackGif;
            }
            
            // Fallback çözümü - burada en azından bir şey dönmeliyiz
            return $this->getFallbackGif();
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
        
        // Deneme sayacı - sonsuz döngüden kaçınmak için
        $attempts = 0;
        $maxAttempts = 3;
        $gifUrl = null;
        
        while (!$gifUrl && $attempts < $maxAttempts) {
            $attempts++;
            
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
            
            // URL kontrolü
            if ($gifUrl && !filter_var($gifUrl, FILTER_VALIDATE_URL)) {
                Log::warning('Geçersiz GIF URL\'si döndü', ['emotion' => $emotion, 'url' => $gifUrl]);
                $gifUrl = null; // Geçersiz URL'yi temizle
            }
        }
        
        // Yine de bulunamazsa, son çare olarak duygu adını kullanarak ara
        if (!$gifUrl) {
            // Duyguyu anime/çizgi film stiliyle birleştir
            $finalQuery = $randomStyle . " " . $emotion;
            $gifUrl = $this->getRandomGif($finalQuery);
            
            // URL kontrolü
            if ($gifUrl && !filter_var($gifUrl, FILTER_VALIDATE_URL)) {
                Log::warning('Geçersiz GIF URL\'si döndü (durum 2)', ['emotion' => $emotion, 'url' => $gifUrl]);
                $gifUrl = null;
            }
        }
        
        // Hiçbir şekilde GIF bulunamazsa, fallback GIF'i kullan
        if (!$gifUrl) {
            $gifUrl = $this->getFallbackGif();
        }
        
        // Duygu arama başarısını logla
        Log::debug('Duygu durumu GIF arama sonuçları', [
            'emotion' => $emotion,
            'search_strategy' => $searchStrategy,
            'query' => $query,
            'style' => $randomStyle,
            'success' => !empty($gifUrl),
            'attempts' => $attempts,
            'is_url' => filter_var($gifUrl, FILTER_VALIDATE_URL)
        ]);
        
        return $gifUrl;
    }
    
    /**
     * Her durumda çalışacak fallback GIF URL'si
     * 
     * @return string GIF URL
     */
    private function getFallbackGif()
    {
        // Önceden tanımlanmış güvenilir anime/çizgi film/hayvan GIF URL'leri
        $fallbackGifs = [
            'https://media.tenor.com/3TgPers01D8AAAAd/sad-anime.gif',
            'https://media.tenor.com/TfI9HZ4zXqMAAAAd/anime-happy.gif',
            'https://media.tenor.com/TbmCt3NbUkMAAAAd/laughing-laugh.gif',
            'https://media.tenor.com/l-lI4JLEqccAAAAd/anime-slap.gif',
            'https://media.tenor.com/Gc7g6RS5ERsAAAAd/anime-cat.gif',
            'https://media.tenor.com/6BHHtOWYuKgAAAAd/anime-girl.gif',
            'https://media.tenor.com/tMpL7xn16L0AAAAd/dog-cool.gif',
            'https://media.tenor.com/LoYl6-D1c6wAAAAd/cat-kitty.gif',
            'https://media.tenor.com/9mHavQsj2WMAAAAd/penguin-hi.gif',
            'https://media.tenor.com/Vt2P0L_ONEQAAAAd/frozen-olaf.gif'
        ];
        
        // Rastgele bir tane seç
        return $fallbackGifs[array_rand($fallbackGifs)];
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