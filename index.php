<?php
// توکن ربات تلگرام خودت
define('BOT_TOKEN', '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA');

// تابع ارسال پیام متنی
function sendMessage($chat_id, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

// تابع ارسال عکس همراه کپشن
function sendPhoto($chat_id, $photo_url, $caption) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
    $post_fields = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'HTML',
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_exec($ch);
    curl_close($ch);
}

// تابع گرفتن محتوای صفحه
function getPageContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// تابع استخراج 20 فیلم اول
function extractMovies($html) {
    $movies = [];
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $items = $xpath->query("//div[contains(@class, 'item')]");

    foreach ($items as $index => $item) {
        if ($index >= 20) break;

        $img = $xpath->query(".//img", $item);
        $img_src = ($img->length > 0) ? $img->item(0)->getAttribute('src') : '';

        $titleNode = $xpath->query(".//h3//a", $item);
        $title = ($titleNode->length > 0) ? trim($titleNode->item(0)->nodeValue) : 'بدون عنوان';

        $link = ($titleNode->length > 0) ? $titleNode->item(0)->getAttribute('href') : '';

        $movies[] = [
            'title' => $title,
            'link' => $link,
            'img' => $img_src,
        ];
    }
    return $movies;
}

// دریافت ورودی پیام تلگرام
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // چیزی دریافت نشده
    exit;
}

if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = trim($message['text'] ?? '');

    if ($text === "/start") {
        sendMessage($chat_id, "در حال دریافت اطلاعات 20 فیلم اول، لطفا چند لحظه صبر کنید...");

        $html = getPageContent("https://www.film2movie.asia/category/movies/");

        if (!$html) {
            sendMessage($chat_id, "خطا در دریافت صفحه فیلم‌ها.");
            exit;
        }

        $movies = extractMovies($html);

        if (count($movies) == 0) {
            sendMessage($chat_id, "متاسفانه نتوانستم اطلاعات فیلم‌ها را پیدا کنم.");
            exit;
        }

        foreach ($movies as $movie) {
            $caption = "<b>" . htmlspecialchars($movie['title']) . "</b>\n" .
                       "<a href='" . htmlspecialchars($movie['link']) . "'>لینک فیلم</a>";

            sendPhoto($chat_id, $movie['img'], $caption);
            // برای جلوگیری از ارسال سریع و احتمالی بلاک شدن، میتونی اینجا usleep(500000); بزاری (نیم ثانیه)
        }
    } else {
        sendMessage($chat_id, "سلام! برای دریافت 20 فیلم اول، لطفا دستور /start را ارسال کنید.");
    }
}
?>
