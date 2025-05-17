<?php
// توکن ربات خود را اینجا قرار دهید
$token = '7690458225:AAFfMN5mn0i4P1vKejr8W6_H_tfDiX49LIA';
$website = "https://api.telegram.org/bot$token";

// دریافت آپدیت‌ها
$update = json_decode(file_get_contents("php://input"), TRUE);

$message = $update["message"]["text"] ?? '';
$chat_id = $update["message"]["chat"]["id"] ?? '';

// پاسخ به پیام‌های دریافتی
if ($message == "/start") {
    sendMessage($chat_id, "سلام! نام فیلم را ارسال کنید تا اطلاعات آن را برایتان بیاورم.");
} elseif (!empty($message)) {
    $filmInfo = getMovieInfoFromSite($message);
    sendMessage($chat_id, $filmInfo);
}

// تابع ارسال پیام
function sendMessage($chat_id, $text) {
    global $website;
    file_get_contents($website . "/sendMessage?chat_id=$chat_id&text=" . urlencode($text));
}

// تابع استخراج اطلاعات فیلم از سایت موی‌فیلم
function getMovieInfoFromSite($query) {
    $searchUrl = "https://mymoviefilm.site/?s=" . urlencode($query);
    $html = file_get_contents($searchUrl);

    // بررسی وجود نتایج
    if (strpos($html, 'result') !== false) {
        return "نتیجه‌ای برای «$query» پیدا شد. (اینجا می‌توانید لینک یا اطلاعات فیلم را نمایش دهید)";
    } else {
        return "متأسفم، فیلمی با عنوان «$query» پیدا نشد.";
    }
}
?>
