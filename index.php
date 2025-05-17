<?php
$botToken = "7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA";
$website = "https://api.telegram.org/bot$botToken";

$update = json_decode(file_get_contents('php://input'), true);
if (!$update) exit;

$message = $update["message"] ?? null;
if (!$message) exit;

$text = strtolower($message["text"] ?? "");
$chat_id = $message["chat"]["id"] ?? "";

if (!$chat_id) exit;

if ($text == "/start") {
    sendMessage($chat_id, "سلام! برای دریافت اطلاعات فیلم، لطفا 'فیلم' را ارسال کنید.");
} elseif ($text == "فیلم") {
    sendMessage($chat_id, "در حال دریافت اطلاعات فیلم، لطفا چند لحظه صبر کنید...");
    $movies = getMovies();
    if (count($movies) > 0) {
        // فقط اولین فیلم رو می‌فرستیم، میشه تغییر بدی که چند فیلم بفرسته
        $movie = $movies[0];
        sendPhoto($chat_id, $movie['image'], 
            "🎬 عنوان: {$movie['title']}\n" .
            "📜 خلاصه: {$movie['description']}\n" .
            "🔗 لینک: {$movie['link']}"
        );
    } else {
        sendMessage($chat_id, "متاسفانه نتوانستم اطلاعات فیلم را پیدا کنم.");
    }
} else {
    sendMessage($chat_id, "دستور نامشخص است. لطفا 'فیلم' را ارسال کنید.");
}

function getMovies() {
    $url = "https://www.film2movie.asia/";
    $html = file_get_contents($url);
    if (!$html) return [];

    $movies = [];

    // با DOMDocument اطلاعات را استخراج می‌کنیم
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // هر فیلم در div با کلاس "block_movie"
    $movieDivs = $xpath->query('//div[contains(@class, "block_movie")]');
    foreach ($movieDivs as $div) {
        // عنوان فیلم
        $titleNode = $xpath->query('.//a[@class="name_film"]', $div);
        $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

        // لینک فیلم
        $link = $titleNode->length > 0 ? $titleNode->item(0)->getAttribute('href') : '';

        // تصویر فیلم
        $imgNode = $xpath->query('.//img', $div);
        $image = $imgNode->length > 0 ? $imgNode->item(0)->getAttribute('src') : '';

        // خلاصه فیلم (ممکنه نباشه، در این سایت معمولاً توضیح کوتاه در span یا div خاص)
        $descNode = $xpath->query('.//p', $div);
        $description = $descNode->length > 0 ? trim($descNode->item(0)->textContent) : 'بدون توضیح';

        if ($title && $link) {
            $movies[] = [
                'title' => $title,
                'link' => $link,
                'image' => $image,
                'description' => $description,
            ];
        }
    }
    return $movies;
}

function sendMessage($chat_id, $text) {
    global $website;
    $url = $website . "/sendMessage?chat_id=$chat_id&text=" . urlencode($text);
    file_get_contents($url);
}

function sendPhoto($chat_id, $photoUrl, $caption = '') {
    global $website;
    $url = $website . "/sendPhoto?chat_id=$chat_id&photo=" . urlencode($photoUrl) . "&caption=" . urlencode($caption);
    file_get_contents($url);
}
?>
