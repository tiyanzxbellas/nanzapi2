<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Random Islamic Quotes (Bahasa Indonesia)
// Contoh: (tanpa parameter)
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Nanzz'];

$quotes = [
    ["quote" => "Allah tidak membebani seseorang melainkan sesuai dengan kesanggupannya.", "source" => "QS. Al-Baqarah: 286"],
    ["quote" => "Maka sesungguhnya bersama kesulitan ada kemudahan.", "source" => "QS. Al-Insyirah: 5"],
    ["quote" => "Dan Dia mendapatimu sebagai seorang yang bingung, lalu Dia memberikan petunjuk.", "source" => "QS. Ad-Dhuha: 7"],
    ["quote" => "Maka ingatlah kepada-Ku, Aku pun akan ingat kepadamu.", "source" => "QS. Al-Baqarah: 152"],
    ["quote" => "Dan barangsiapa bertawakal kepada Allah, niscaya Allah akan mencukupkan keperluannya.", "source" => "QS. At-Talaq: 3"],
    ["quote" => "Sebaik-baik kalian adalah yang paling baik akhlaknya.", "source" => "HR. Bukhari"],
    ["quote" => "Barangsiapa beriman kepada Allah dan hari akhir, hendaklah ia berkata baik atau diam.", "source" => "HR. Bukhari"],
    ["quote" => "Janganlah kamu bersikap lemah, dan janganlah pula kamu bersedih hati.", "source" => "QS. Ali Imran: 139"],
    ["quote" => "Sesungguhnya Allah beserta orang-orang yang sabar.", "source" => "QS. Al-Baqarah: 153"],
    ["quote" => "Berdoalah kepada-Ku, niscaya akan Aku kabulkan bagimu.", "source" => "QS. Ghafir: 60"],
    ["quote" => "Orang mukmin yang paling sempurna imannya adalah yang paling baik akhlaknya.", "source" => "HR. Tirmidzi"],
    ["quote" => "Seseorang akan bersama orang yang dicintainya.", "source" => "HR. Bukhari"],
    ["quote" => "Karena sesungguhnya sesudah kesulitan itu ada kemudahan.", "source" => "QS. Al-Insyirah: 6"],
    ["quote" => "Senyummu di hadapan saudaramu adalah sedekah.", "source" => "HR. Tirmidzi"],
    ["quote" => "Bukanlah orang yang kuat itu yang menang dalam perkelahian, tetapi orang yang kuat adalah yang mampu menahan amarahnya.", "source" => "HR. Bukhari"],
    ["quote" => "Sesungguhnya amal itu tergantung niatnya.", "source" => "HR. Bukhari"],
    ["quote" => "Tidak sempurna iman seseorang hingga ia mencintai saudaranya seperti ia mencintai dirinya sendiri.", "source" => "HR. Bukhari"],
    ["quote" => "Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.", "source" => "HR. Ahmad"],
    ["quote" => "Sesungguhnya Allah itu indah dan menyukai keindahan.", "source" => "HR. Muslim"],
    ["quote" => "Amalan yang paling dicintai Allah adalah yang dikerjakan secara terus-menerus meskipun sedikit.", "source" => "HR. Bukhari"],
    ["quote" => "Tidaklah beriman seseorang yang perutnya kenyang sementara tetangganya kelaparan.", "source" => "HR. Bukhari"],
    ["quote" => "Barangsiapa tidak menyayangi, maka ia tidak akan disayangi.", "source" => "HR. Bukhari"],
    ["quote" => "Cukuplah kematian sebagai pengingat.", "source" => "HR. Tirmidzi"],
    ["quote" => "Dunia adalah penjara bagi orang mukmin dan surga bagi orang kafir.", "source" => "HR. Muslim"],
    ["quote" => "Janganlah kamu marah, maka bagimu surga.", "source" => "HR. Bukhari"],
    ["quote" => "Berlomba-lombalah dalam kebaikan.", "source" => "QS. Al-Baqarah: 148"],
    ["quote" => "Dan bersabarlah, sesungguhnya Allah beserta orang-orang yang sabar.", "source" => "QS. Al-Anfal: 46"],
    ["quote" => "Hai orang-orang yang beriman, jadikanlah sabar dan shalat sebagai penolongmu.", "source" => "QS. Al-Baqarah: 153"],
    ["quote" => "Maka nikmat Tuhanmu yang manakah yang kamu dustakan?", "source" => "QS. Ar-Rahman: 13"],
    ["quote" => "Dan Dia bersama kamu di mana saja kamu berada.", "source" => "QS. Al-Hadid: 4"],
    ["quote" => "Tidak ada yang mustahil bagi Allah.", "source" => "QS. Yasin: 82"],
    ["quote" => "Dan hanya kepada Allah hendaknya kamu bertawakal, jika kamu benar-benar orang yang beriman.", "source" => "QS. Al-Maidah: 23"],
    ["quote" => "Sesungguhnya rahmat Allah itu dekat kepada orang-orang yang berbuat baik.", "source" => "QS. Al-A'raf: 56"],
    ["quote" => "Sabar itu indah.", "source" => "HR. Muslim"],
    ["quote" => "Mencari ilmu adalah kewajiban setiap muslim.", "source" => "HR. Ibnu Majah"],
    ["quote" => "Ucapan yang baik adalah sedekah.", "source" => "HR. Bukhari"],
    ["quote" => "Sebaik-baik kekayaan adalah kekayaan jiwa.", "source" => "HR. Bukhari"],
    ["quote" => "Berbuat baiklah sebagaimana Allah telah berbuat baik kepadamu.", "source" => "QS. Al-Qasas: 77"],
    ["quote" => "Sesungguhnya shalatku, ibadahku, hidupku, dan matiku hanya untuk Allah Tuhan semesta alam.", "source" => "QS. Al-An'am: 162"],
    ["quote" => "Jangan berputus asa dari rahmat Allah.", "source" => "QS. Az-Zumar: 53"],
    ["quote" => "Wahai orang-orang yang beriman, bertaqwalah kepada Allah dengan sebenar-benar taqwa.", "source" => "QS. Ali Imran: 102"],
    ["quote" => "Dan tolong-menolonglah kamu dalam kebaikan dan taqwa.", "source" => "QS. Al-Maidah: 2"],
    ["quote" => "Cukuplah Allah sebagai penolong kami, dan Dia adalah sebaik-baik pelindung.", "source" => "QS. Ali Imran: 173"],
    ["quote" => "Barangsiapa yang bertakwa kepada Allah niscaya Dia akan mengadakan baginya jalan keluar.", "source" => "QS. At-Talaq: 2"],
    ["quote" => "Dan memberinya rezeki dari arah yang tidak disangka-sangka.", "source" => "QS. At-Talaq: 3"],
    ["quote" => "Perkataan yang baik dan pemberian maaf lebih baik dari sedekah yang diiringi dengan sesuatu yang menyakitkan.", "source" => "QS. Al-Baqarah: 263"],
    ["quote" => "Sesungguhnya Allah mencintai orang-orang yang bertaubat dan orang-orang yang mensucikan diri.", "source" => "QS. Al-Baqarah: 222"],
    ["quote" => "Maka apabila kamu telah selesai dari suatu urusan, kerjakanlah dengan sungguh-sungguh urusan yang lain.", "source" => "QS. Al-Insyirah: 7"],
    ["quote" => "Dan hanya kepada Tuhanmulah hendaknya kamu berharap.", "source" => "QS. Al-Insyirah: 8"],
    ["quote" => "Hidup di dunia ini hanya sementara, dan kehidupan akhirat adalah kekal.", "source" => "QS. Al-A'la: 16-17"],
];

shuffle($quotes);
$random = $quotes[array_rand($quotes)];

echo json_encode(array_merge($credit, [
    'status' => true,
    'result' => $random
]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>