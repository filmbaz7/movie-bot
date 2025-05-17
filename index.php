<?php
$botToken = '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA';
$apiURL = "https://api.telegram.org/bot$botToken/";

$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    exit();
}

$chatId = $update['message']['chat']['id'] ?? null;
$text = strtolower(trim($update['message']['text'] ?? ''));

if (!$chatId) {
    exit();
}

if ($text === '/start') {
    sendMessage($chatId, "سلام!\nبرای دریافت اطلاعات 20 فیلم اول، کلمه 'فیلم' را ارسال کنید.");
    exit();
}

if ($text === 'فیلم') {
    sendMessage($chatId, "در حال دریافت اطلاعات 20 فیلم اول، لطفا چند لحظه صبر کنید...");

    $url = "https://www.film2movie.asia/category/movies/";

    $html = file_get_contents($url);
    if (!$html) {
        sendMessage($chatId, "متاسفانه نتوانستم اطلاعات فیلم را دریافت کنم.");
        exit();
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // گرفتن 20 فیلم اول داخل article با کلاس jeg_post
    $movies = $xpath->query("//article[contains(@class,'jeg_post')]");

    if ($movies->length == 0) {
        sendMessage($chatId, "متاسفانه نتوانستم اطلاعات فیلم را پیدا کنم.");
        exit();
    }

    $count = 0;
    $maxMovies = 20;

    for ($i = 0; $i < $movies->length && $count < $maxMovies; $i++) {
        $movie = $movies->item($i);

        $titleNode = $xpath->query(".//h3[contains(@class,'jeg_post_title')]/a", $movie);
        $title = $titleNode->length > 0 ? trim($titleNode->item(0)->nodeValue) : 'عنوان نامشخص';
        $link = $titleNode->length > 0 ? $titleNode->item(0)->getAttribute('href') : '';

        $imgNode = $xpath->query(".//img[contains(@class,'wp-post-image')]", $movie);
        $imgUrl = $imgNode->length > 0 ? $imgNode->item(0)->getAttribute('src') : '';

        $descNode = $xpath->query(".//div[contains(@class,'jeg_post_excerpt')]", $movie);
        $description = $descNode->length > 0 ? trim($descNode->item(0)->nodeValue) : '';

        $message = "🎬 *$title*\n\n$description\n\n[مشاهده در سایت]($link)";

        if ($imgUrl) {
            sendPhoto($chatId, $imgUrl, $message);
        } else {
            sendMessage($chatId, $message);
        }

        $count++;
        sleep(1);
    }
    exit();
}

function sendMessage($chatId, $text) {
    global $apiURL;
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => false,
    ];
    file_get_contents($apiURL . "sendMessage?" . http_build_query($data));
}

function sendPhoto($chatId, $photoUrl, $caption) {
    global $apiURL;
    $data = [
        'chat_id' => $chatId,
        'photo' => $photoUrl,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
    ];
    file_get_contents($apiURL . "sendPhoto?" . http_build_query($data));
}
?>
