<?php

define('BOT_TOKEN', '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) {
    exit;
}

$chat_id = $update['message']['chat']['id'];
$text = $update['message']['text'] ?? '';

if (strtolower($text) === '/start' || $text === 'فیلم') {
    sendMessage($chat_id, "در حال دریافت اطلاعات 20 فیلم اول، لطفا چند لحظه صبر کنید...");

    $movies = getLatestMovies();
    
    if (!$movies) {
        sendMessage($chat_id, "متاسفانه نتوانستم اطلاعات فیلم‌ها را پیدا کنم.");
    } else {
        foreach ($movies as $movie) {
            $caption = "{$movie['title']}\n\nلینک: {$movie['link']}";
            sendPhoto($chat_id, $movie['image'], $caption);
        }
    }
}

function getLatestMovies() {
    $html = file_get_contents("https://www.film2movie.asia/");
    if (!$html) return null;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $items = $xpath->query("//div[contains(@class,'post-content')]//div[contains(@class,'post')]");

    $movies = [];
    $count = 0;

    foreach ($items as $item) {
        if ($count >= 20) break;

        $titleNode = $xpath->query(".//h2/a", $item)->item(0);
        $imgNode = $xpath->query(".//img", $item)->item(0);

        if ($titleNode && $imgNode) {
            $movies[] = [
                'title' => trim($titleNode->textContent),
                'link' => $titleNode->getAttribute('href'),
                'image' => $imgNode->getAttribute('src')
            ];
            $count++;
        }
    }

    return $movies;
}

function sendMessage($chat_id, $text) {
    file_get_contents(API_URL . "sendMessage?chat_id=$chat_id&text=" . urlencode($text));
}

function sendPhoto($chat_id, $photo, $caption = '') {
    file_get_contents(API_URL . "sendPhoto?chat_id=$chat_id&photo=" . urlencode($photo) . "&caption=" . urlencode($caption));
}
?>
