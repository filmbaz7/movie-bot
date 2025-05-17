<?php
define('BOT_TOKEN', '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// فعال کردن گزارش خطا
error_reporting(E_ALL);
ini_set('display_errors', 1);

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!isset($update['message'])) {
    exit;
}

$chat_id = $update['message']['chat']['id'];
$text = $update['message']['text'] ?? '';

if (strtolower($text) === '/start' || $text === 'فیلم') {
    sendMessage($chat_id, "🔍 در حال جستجو در بانک فیلم... لطفاً منتظر بمانید");
    
    try {
        $movies = fetchMovies();
        if (empty($movies)) {
            sendMessage($chat_id, "⚠️ متاسفانه هیچ فیلمی یافت نشد. لطفاً بعداً تلاش کنید.");
            exit;
        }
        
        sendMessage($chat_id, "✅ " . count($movies) . " فیلم برتر پیدا شد!");
        
        foreach ($movies as $index => $movie) {
            // تاخیر برای جلوگیری از محدودیت سرور
            if ($index % 5 === 0) {
                sleep(2);
            }
            
            $caption = "🎬 " . $movie['title'] . "\n\n🌐 لینک: " . $movie['link'];
            sendPhoto($chat_id, $movie['image'], $caption);
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        sendMessage($chat_id, "⚠️ خطایی در سیستم رخ داده است. لطفاً با پشتیبانی تماس بگیرید.");
    }
}

function fetchMovies() {
    $url = 'https://www.film2movie.asia/';
    $html = curlGet($url);
    
    if (!$html) {
        throw new Exception("Failed to fetch website content");
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    // به روزرسانی XPath بر اساس ساختار جدید سایت
    $posts = $xpath->query("//div[contains(@class, 'post-entry')]"); // تغییر کلاس به نسخه جدید
    
    if ($posts->length === 0) {
        throw new Exception("No posts found in HTML structure");
    }

    $movies = [];
    
    foreach ($posts as $post) {
        $titleNode = $xpath->query(".//h3/a", $post)->item(0); // تغییر از h2 به h3
        $imgNode = $xpath->query(".//img[contains(@class, 'wp-post-image')]", $post)->item(0);
        
        if (!$titleNode || !$imgNode) continue;

        $title = trim($titleNode->textContent);
        $link = $titleNode->getAttribute('href');
        $img = $imgNode->getAttribute('src');

        // حذف پارامترهای اضافی از URL تصویر
        $img = strtok($img, '?');

        // بررسی معتبر بودن URL تصویر
        if (!filter_var($img, FILTER_VALIDATE_URL)) {
            continue;
        }

        $movies[] = [
            'title' => $title,
            'link' => $link,
            'image' => $img
        ];

        if (count($movies) >= 20) break;
    }

    return $movies;
}

function curlGet($url) {
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Accept-Language: en-US,en;q=0.9',
            'Referer: https://www.google.com/'
        ]
    ]);
    
    $data = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("CURL Error: " . curl_error($ch));
        return false;
    }
    
    curl_close($ch);
    return $data;
}

function sendMessage($chat_id, $text) {
    $url = API_URL . "sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
    file_get_contents($url);
}

function sendPhoto($chat_id, $photo_url, $caption) {
    $url = API_URL . "sendPhoto?" . http_build_query([
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => substr($caption, 0, 1024), // محدودیت طول کپشن
        'parse_mode' => 'HTML'
    ]);
    
    $response = file_get_contents($url);
    
    if (!$response) {
        error_log("Failed to send photo: " . $photo_url);
    }
}
?>
