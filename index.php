<?php
// توکن ربات رو اینجا بذار
define('BOT_TOKEN', '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA');

// توابع کمکی برای ارسال پیام
function sendMessage($chat_id, $text, $parse_mode = 'HTML') {
    $url = "https://api.telegram.org/bot".BOT_TOKEN."/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode,
        'disable_web_page_preview' => true,
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function sendPhoto($chat_id, $photo_url, $caption = '') {
    $url = "https://api.telegram.org/bot".BOT_TOKEN."/sendPhoto";
    $post_fields = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// تابع برای گرفتن HTML صفحه فیلم‌ها
function getHtml($url) {
    $options = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
        ]
    ];
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

// تابع استخراج 20 فیلم اول
function getTop20Movies() {
    $html = getHtml('https://www.film2movie.asia/category/movies/');

    if (!$html) return false;

    $movies = [];

    // استفاده از DOMDocument برای پارس کردن HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // سلکتور فیلم‌ها (بسته به ساختار سایت)
    // بررسی می‌کنیم که فیلم‌ها داخل div با کلاس "post" هستند
    $posts = $xpath->query("//div[contains(@class,'post')]");

    if ($posts->length == 0) return false;

    $count = 0;
    foreach ($posts as $post) {
        if ($count >= 20) break;

        // لینک فیلم
        $a = $xpath->query(".//a", $post)->item(0);
        $href = $a ? $a->getAttribute('href') : '';

        // عنوان فیلم
        $title = $a ? trim($a->textContent) : '';

        // عکس فیلم
        $img = $xpath->query(".//img", $post)->item(0);
        $img_url = $img ? $img->getAttribute('src') : '';

        if ($href && $title && $img_url) {
            $movies[] = [
                'title' => $title,
                'link' => $href,
                'img' => $img_url
            ];
            $count++;
        }
    }
    return $movies;
}

// دریافت آپدیت‌ها از تلگرام
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? '';

if ($text == '/start') {
    sendMessage($chat_id, "در حال دریافت اطلاعات 20 فیلم اول، لطفا چند لحظه صبر کنید...");

    $movies = getTop20Movies();

    if (!$movies) {
        sendMessage($chat_id, "متاسفانه نتوانستم اطلاعات فیلم‌ها را پیدا کنم.");
        exit;
    }

    foreach ($movies as $movie) {
        $caption = "<b>".$movie['title']."</b>\n"."<a href='".$movie['link']."'>مشاهده فیلم</a>";
        sendPhoto($chat_id, $movie['img'], $caption);
        usleep(300000); // کمی توقف برای جلوگیری از محدودیت تلگرام
    }
}
