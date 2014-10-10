<?php
$imagePattern     = '/.*\.[jpg|JPG]/u';
$galleryPath      = 'gallery';
$galleryFile      = 'index.html';
$thumbsDir        = 'thumbs';
$thumbsPath       = $galleryPath . '/' . $thumbsDir;
$pageTemplate     = 'template/index.html';
$imageTag         = '<a href="{imageUri}"><img src="blank.gif" alt="" data-echo="{thumbUri}"></a>';
$imageNoScriptTag = '<a href="{imageUri}"><img src="{thumbUri}"></a>';
$thumbWidth       = 200;
$galleryBase      = '';

date_default_timezone_set('UTC');

define('SCRIPT_PATH', str_replace('generate.php', '', __FILE__));

if (file_exists(SCRIPT_PATH . 'config.php')) {
    include SCRIPT_PATH . 'config.php';
}

function generate($dirPath = '')
{
    $imageTag         = $GLOBALS['imageTag'];
    $imageNoScriptTag = $GLOBALS['imageNoScriptTag'];
    //$dirPath = rtrim($dirPath, '/');
    if (empty($dirPath))  {
        $dirPath = SCRIPT_PATH . $GLOBALS['galleryPath'];
        $galleryBase = $GLOBALS['galleryBase'];
    }
    else {
        list($before, $after) = explode($GLOBALS['galleryPath'], $dirPath);
        $galleryBase = $GLOBALS['galleryBase'] . $after;
    }
    if (is_dir($dirPath)) {
        $thumbsDir   = $GLOBALS['thumbsDir'];
        $thumbsPath  = $dirPath . '/' . $thumbsDir;
        $galleryFile = $dirPath . '/' . $GLOBALS['galleryFile'];
        if (!is_dir($thumbsPath)) mkdir($thumbsPath);
        $gallery = dir($dirPath);
        $noScan  = array('.', '..', $GLOBALS['thumbsDir']);
        while (($entry = $gallery->read()) !== false) {
            if (preg_match($GLOBALS['imagePattern'], $entry)) {
                $imagesList[] = $entry;
            }
            elseif (!in_array($entry, $noScan) && is_dir($entry)) {
                generate($dirPath);
            }
        }
        if (is_array($imagesList)) {
            ($sort === 'desc') ? rsort($imagesList) : sort($imagesList);
            foreach ($imagesList as $key => $name) {
                $imageUri                           = $dirPath . '/' . $name;
                list($width, $height, $type, $attr) = getimagesize($imageUri);
                $thumbWidth                         = $GLOBALS['thumbWidth'];
                $thumbHeight                        = round($height * $thumbWidth / $width);
                if (!file_exists($thumbsPath . '/' . $name)) {
                    $source = imagecreatefromjpeg ($imageUri);
                    $thumb  = imagecreatetruecolor($thumbWidth, $thumbHeight);
                    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                    imagedestroy($source);
                    imagejpeg($thumb, $thumbsPath . '/' . $name, 100);
                }
                $currentImage         = str_replace('{thumbUri}', $galleryBase . '/' . $thumbsDir . '/' . $name, $imageTag);
                $currentImage         = str_replace('{thumbWidth}', $thumbWidth, $currentImage);
                $currentImage         = str_replace('{thumbHeight}', $thumbHeight, $currentImage);
                $currentImage         = str_replace('{imageUri}', $galleryBase . '/' . $name, $currentImage);
                $currentImage         = str_replace('{imageWidth}', $width, $currentImage);
                $currentImage         = str_replace('{imageHeight}', $height, $currentImage);
                $images[]             = $currentImage;
                $currentImageNoScript = str_replace('{thumbUri}', $galleryBase . '/' . $thumbsDir . '/' . $name, $imageNoScriptTag);
                $currentImageNoScript = str_replace('{thumbWidth}', $thumbWidth, $currentImageNoScript);
                $currentImageNoScript = str_replace('{thumbHeight}', $thumbHeight, $currentImageNoScript);
                $currentImageNoScript = str_replace('{imageUri}', $galleryBase . '/' . $name, $currentImageNoScript);
                $currentImageNoScript = str_replace('{imageWidth}', $width, $currentImageNoScript);
                $currentImageNoScript = str_replace('{imageHeight}', $height, $currentImageNoScript);
                $imagesNoScript[]     = $currentImageNoScript;
            }
        }
        $page     = file_get_contents(SCRIPT_PATH . $GLOBALS['pageTemplate']);
        $replace  = (is_array($images)) ? implode(PHP_EOL, $images) : '';
        $noScript = (is_array($imagesNoScript)) ? implode(PHP_EOL, $imagesNoScript) : '';
        $page     = str_replace('{galleryPath}', $galleryBase, $page);
        $page     = str_replace('{images}', $replace, $page);
        $page     = str_replace('{imagesNoScript}', $noScript, $page);
        file_put_contents($galleryFile, $page, LOCK_EX);
    }
}

if (is_dir(SCRIPT_PATH . $galleryPath) && file_exists(SCRIPT_PATH . $pageTemplate)) {
    generate();
}
?>