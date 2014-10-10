<?php
$imagePattern     = '/.*\.[jpg|JPG]/u';
$galleryPath      = 'gallery';
$templateDir      = 'template';
$thumbsDir        = 'thumbs';
$thumbsPath       = $galleryPath . '/' . $thumbsDir;
$thumbWidth       = 200;
$galleryBase      = '';

date_default_timezone_set('UTC');

define('SCRIPT_PATH', str_replace('generate.php', '', __FILE__));

if (file_exists(SCRIPT_PATH . 'config.php')) include SCRIPT_PATH . 'config.php';

function generate($dirPath = '')
{
    $imageTag         = $GLOBALS['imageTag'];
    $imageNoScriptTag = $GLOBALS['imageNoScriptTag'];
    if (empty($dirPath))  {
        $dirPath     = SCRIPT_PATH . $GLOBALS['galleryPath'];
    }
    else {
        list($before, $after) = explode($GLOBALS['galleryPath'], $dirPath);
    }
    $galleryBase = $GLOBALS['galleryBase'] . $after;
    if (is_dir($dirPath)) {
        $thumbsDir   = $GLOBALS['thumbsDir'];
        $thumbsPath  = $dirPath . '/' . $thumbsDir;
        $galleryFile = $dirPath . '/' . $GLOBALS['galleryFile'];
        if (!is_dir($thumbsPath)) mkdir($thumbsPath);
        $gallery = dir($dirPath);
        while (($entry = $gallery->read()) !== false) {
            if (preg_match($GLOBALS['imagePattern'], $entry)) {
                $imagesList[] = $entry;
            }
            elseif (!in_array($entry, array('.', '..', $GLOBALS['thumbsDir'])) && is_dir($entry)) {
                $dirList[] = $entry;
                generate($dirPath);
            }
        }
        $tplPath = SCRIPT_PATH . $GLOBALS['templatePath'];
        if (is_array($imagesList)) {
            if (file_exists($tplPath . '/index.html')) $page = file_get_contents($tplPath . '/index.html');
            if (file_exists($tplPath . '/imagetag.html')) $imageTag = file_get_contents($tplPath . '/imagetag.html');
            if (file_exists($tplPath . '/imagenoscripttag.html')) $imageNoScriptTag = file_get_contents($tplPath . '/imagenoscripttag.html');
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
        if (is_array($dirList)) {
            if (file_exists($tplPath . '/directory.html')) $dir = file_get_contents($tplPath . '/directory.html');
            foreach ($dirList as $key => $name) {
                $dirUri     = $dirPath . '/' . $name;
                $currentDir = str_replace('{dirUri}', $dirUri, $dir);
                $currentDir = str_replace('{dirName}', $name, $currentDir);
                $dirs[]     = $currentDir;
            }
        }
        $replace  = (is_array($images)) ? implode(PHP_EOL, $images) : '';
        $noScript = (is_array($imagesNoScript)) ? implode(PHP_EOL, $imagesNoScript) : '';
        $subDirs  = (is_array($dirs)) ? implode(PHP_EOL, $dirs) : '';
        $page     = str_replace('{galleryPath}', $galleryBase, $page);
        $page     = str_replace('{images}', $replace, $page);
        $page     = str_replace('{imagesNoScript}', $noScript, $page);
        $age      = str_replace('{subDirs}', $subDirs, $page);
        file_put_contents($galleryFile, $page, LOCK_EX);
    }
}
if (is_dir(SCRIPT_PATH . $galleryPath) && file_exists(SCRIPT_PATH . $pageTemplate)) {
    generate();
}
?>