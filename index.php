<?php
$TOKEN = "7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA";
$API_URL = "https://api.telegram.org/bot$TOKEN/";

$update = json_decode(file_get_contents("php://input"), TRUE);
$chat_id = $update["message"]["chat"]["id"];
$text = strtolower($update["message"]["text"]);

function sendMessage($chat_id, $text) {
    global $API_URL;
    file_get_contents($API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text));
}

function sendPhoto($chat_id, $photo_url, $caption) {
    global $API_URL;
    $post_fields = array(
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption
    );

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type:multipart/form-data"
    ));
    curl_setopt($ch, CURLOPT_URL, $API_URL . "sendPhoto");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $output = curl_exec($ch);
}

function getLatestMovie() {
    $html = file_get_contents("https://www.film2movie.asia/");
    preg_match('/<div class="postbox">.*?<a href="(.*?)".*?<img src="(.*?)".*?title="(.*?)"/s', $html, $matches);
    return [
        "link" => $matches[1] ?? '',
        "image" => $matches[2] ?? '',
        "title" => html_entity_decode($matches[3] ?? '', ENT_QUOTES, 'UTF-8')
    ];
}

if ($text == "/start") {
    sendMessage($chat_id, "سلام! برای دریافت فیلم جدید، بنویس: فیلم");
} elseif ($text == "فیلم") {
    $movie = getLatestMovie();
    if ($movie["title"]) {
        $caption = $movie["title"] . "\n" . $movie["link"];
        sendPhoto($chat_id, $movie["image"], $caption);
    } else {
        sendMessage($chat_id, "مشکلی در دریافت اطلاعات فیلم پیش آمد.");
    }
}
?>
