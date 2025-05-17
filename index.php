<?php

$token = "7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA";
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update["message"])) exit;

$chat_id = $update["message"]["chat"]["id"];
$text = $update["message"]["text"];

if (strtolower($text) == "فیلم") {
    sendMovies($chat_id);
} else {
    sendMessage($chat_id, "برای دریافت فیلم جدید فقط بنویس: فیلم");
}

// تابع ارسال پیام متنی
function sendMessage($chat_id, $text) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage";
    file_get_contents($url . "?chat_id=$chat_id&text=" . urlencode($text));
}

// تابع ارسال عکس با کپشن
function sendPhoto($chat_id, $photo_url, $caption) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendPhoto";
    file_get_contents($url . "?chat_id=$chat_id&photo=" . urlencode($photo_url) . "&caption=" . urlencode($caption));
}

// تابع دریافت فیلم‌ها از سایت
function sendMovies($chat_id) {
    $html = file_get_contents("https://www.film2movie.asia/");
    preg_match_all('/<div class="postbox">(.*?)<\/div>/s', $html, $matches);

    if (!empty($matches[1])) {
        $first = $matches[1][0];

        preg_match('/<img.*?src="(.*?)"/', $first, $img);
        preg_match('/<h2 class="title">(.*?)<\/h2>/', $first, $title);
        preg_match('/<a href="(.*?)"/', $first, $link);

        $caption = "🎬 عنوان: " . strip_tags($title[1]) . "\n🔗 لینک: " . $link[1];
        sendPhoto($chat_id, $img[1], $caption);
    } else {
        sendMessage($chat_id, "❌ نتونستم اطلاعات فیلم رو دریافت کنم.");
    }
}
?>
