<?php

session_start();
header ('Content-Type: image/gif');

$captcha_code=rand(100,999);
//Завожу собственно сгенерированный код каптчи в массив сессии, чтобы потом его проверить в другом месте.
// А  конкретней в функции проверки на ошибки function guestbook_entry_obligatory_fields_error_check (...)
$_SESSION['captcha_code']=$captcha_code;

$captcha_digit_color_1=rand(10 , 200);
$captcha_digit_color_2=rand(10 , 200);
$captcha_digit_color_3=rand(10 , 200);

$txt_box=imagettfbbox(10, 5, "./fonts/Charmonman-Bold.ttf", $captcha_code);


$test_image=imagecreatetruecolor(120, 40);
$rgb_1=imagecolorallocate($test_image, $captcha_digit_color_1, $captcha_digit_color_2, $captcha_digit_color_3);
$rgb_2=imagecolorallocate($test_image, $captcha_digit_color_3, $captcha_digit_color_2, $captcha_digit_color_1);

imagettftext($test_image, 22, -8, 30, 30, $rgb_2, "./fonts/Charmonman-Bold.ttf", $captcha_code);

imagefill($test_image, 0, 0, $rgb_1);
imagegif($test_image);
imagedestroy($img);

echo '</br>';
?>