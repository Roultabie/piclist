<?php
$imagePattern     = '/.*\.[jpg|JPG]/u';
$galleryPath      = 'gallery';
$galleryFile      = 'index.html';
$thumbsDir        = 'thumbs';
$thumbsPath       = $galleryPath . '/' . $thumbsDir;
$pageTemplate     = 'template/index.html';
$imageTag         = '<img src="{imageUri}" width="{imageWidth}" height="{imageHeight}">';
$imageNoScriptTag = '<img src="{imageUri}" width="{imageWidth}" height="{imageHeight}">';
$thumbWidth       = 200;
$galleryBase      = '';

date_default_timezone_set('UTC');

define('SCRIPT_PATH', str_replace('generate.php', '', __FILE__));

if (file_exists(SCRIPT_PATH . 'config.php')) {
    include SCRIPT_PATH . 'config.php';
}

if (is_dir(SCRIPT_PATH . $galleryPath) && file_exists(SCRIPT_PATH . $pageTemplate)) {
    if (!is_dir(SCRIPT_PATH . $thumbsPath)) mkdir(SCRIPT_PATH . $thumbsPath);
    $gallery = dir(SCRIPT_PATH . $galleryPath);
    while (($entry = $gallery->read()) !== false) {
        if (preg_match($imagePattern, $entry)) {
            $imagesList[] = $entry;
        }
    }
    if (is_array($imagesList)) {
        ($sort === 'desc') ? rsort($imagesList) : sort($imagesList);
        foreach ($imagesList as $key => $name) {
            $imageUri                           = SCRIPT_PATH . $galleryPath . '/' . $name;
            list($width, $height, $type, $attr) = getimagesize($imageUri);
            $thumbHeight                        = round($height * $thumbWidth / $width);
            if (!file_exists(SCRIPT_PATH . $thumbsPath . '/' . $name)) {
                $source = imagecreatefromjpeg ($imageUri);
                $thumb  = imagecreatetruecolor($thumbWidth, $thumbHeight);
                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                imagedestroy($source);
                imagejpeg($thumb, SCRIPT_PATH . $thumbsPath . '/' . $name, 100);
            }
            $currentImage         = str_replace('{imageUri}', $galleryBase . '/' . $thumbsDir . '/' . $name, $imageTag);
            $currentImage         = str_replace('{imageWidth}', $thumbWidth, $currentImage);
            $currentImage         = str_replace('{imageHeight}', $thumbHeight, $currentImage);
            $images[]             = $currentImage;
            $currentImageNoScript = str_replace('{imageUri}', $galleryBase . '/' . $thumbsDir . '/' . $name, $imageNoScriptTag);
            $currentImageNoScript = str_replace('{imageWidth}', $thumbWidth, $currentImageNoScript);
            $currentImageNoScript = str_replace('{imageHeight}', $thumbHeight, $currentImageNoScript);
            $imagesNoScript[]     = $currentImageNoScript;
        }
    }
    $page     = file_get_contents(SCRIPT_PATH . $pageTemplate);
    $replace  = (is_array($images)) ? implode(PHP_EOL, $images) : '';
    $noScript = (is_array($imagesNoScript)) ? implode(PHP_EOL, $imagesNoScript) : '';
    $page     = str_replace('{galleryPath}', $galleryBase, $page);
    $page     = str_replace('{images}', $replace, $page);
    $page     = str_replace('{imagesNoScript}', $noScript, $page);
    file_put_contents(SCRIPT_PATH . $galleryPath . '/' . $galleryFile, $page, LOCK_EX);
}
?>