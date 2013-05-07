<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/Securimage/securimage.php');

$image = new Securimage();
$image->perturbation = 0.9;
$image->use_transparent_text = true;
$image->text_transparency_percentage = 75;
$image->code_length = 5;
$image->multi_text_color = array(new Securimage_Color("#222222"), new Securimage_Color("#444444"), new Securimage_Color("#666666"), new Securimage_Color("#888888"));
$image->use_multi_text = true;
$image->line_color = new Securimage_Color("#eaeaea");
$image->image_height = 50;
$image->show();