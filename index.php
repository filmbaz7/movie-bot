<?php

define('BOT_TOKEN', '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!isset($update['message'])) {
    exit;
}

$chat_id = $update['message']['chat']['id'];
$text = $update['message']['text'] ?? '';

if (strtolower($text) === '/start' || $text === 'فیلم') {
    sendMessage($chat_id, "در حال دریافت اطلاعات 20 فیلم اول، لطفا چند لحظه صبر کنید...");

    $movies = fetchMovies();
    if (!$movies) {
        sendMessage($chat_id, "متاسفانه نتوانستم اطلاعات فیلم‌ها را پیدا کنم.");
        exit;
    }

    foreach ($movies as $movie) {
        $caption = "{$movie['title']}\n\n🔗 لینک: {$movie['link']}";
        sendPhoto($chat_id, $movie['image'], $caption);
    }
}

function fetchMovies() {
    $url = 'https://www.film2movie.asia/';
    $html = curlGet($url);
    if (!$html) return [];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $posts = $xpath->query("//div[contains(@class, 'post')]");
    $movies = [];

    foreach ($posts as $post) {
        $titleNode = $xpath->query(".//h2/a", $post)->item(0);
        $imgNode = $xpath->query(".//img", $post)->item(0);

        if ($titleNode && $imgNode) {
            $title = trim($titleNode->textContent);
            $link = $titleNode->getAttribute('href');
            $img = $imgNode->getAttribute('src');

            if ($title && $link && $img) {
                $movies[] = [
                    'title' => $title,
                    'link' => $link,
                    'image' => $img
                ];
            }

            if (count($movies) >= 20) break;
        }
    }

    return $movies;
}

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function sendMessage($chat_id, $text) {
    file_get_contents(API_URL . "sendMessage?chat_id=$chat_id&text=" . urlencode($text));
}

function sendPhoto($chat_id, $photo_url, $caption = '') {
    file_get_contents(API_URL . "sendPhoto?chat_id=$chat_id&photo=" . urlencode($photo_url) . "&caption=" . urlencode($caption));
}
?>
