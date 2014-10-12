<?php
$imagePattern     = '/.*\.[jpg|JPG]/u';
$galleryPath      = 'gallery';
$templateDir      = 'template';
$thumbsDir        = '_thumbs';
$thumbsPath       = $galleryPath . '/' . $thumbsDir;
$thumbWidth       = 200;
$galleryBase      = '';

date_default_timezone_set('UTC');

define('SCRIPT_PATH', str_replace('generate.php', '', __FILE__));

if (file_exists(SCRIPT_PATH . 'config.php')) include SCRIPT_PATH . 'config.php';

function generate($dirPath = '')
{
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
        $galleryFile = $dirPath . '/index.html';
        if (!is_dir($thumbsPath)) mkdir($thumbsPath);
        $gallery = dir($dirPath);
        while (($entry = $gallery->read()) !== false) {
            if (preg_match($GLOBALS['imagePattern'], $entry)) {
                $imagesList[] = $entry;
            }
            elseif (is_dir($dirPath . '/' .$entry) && !in_array($entry, array('.', '..')) && $entry[0] !== '_') {
                $dirList[] = $entry;
                generate($dirPath . '/' .$entry);
            }
        }
        $tplPath = SCRIPT_PATH . $GLOBALS['templateDir'];
        if (is_array($imagesList)) {
            if (file_exists($tplPath . '/index.html')) $page = file_get_contents($tplPath . '/index.html');
            if (file_exists($tplPath . '/firstimagetag.html')) $firstImageTag = file_get_contents($tplPath . '/firstimagetag.html');
            if (file_exists($tplPath . '/lastimagetag.html')) $lastImageTag = file_get_contents($tplPath . '/lastimagetag.html');
            if (file_exists($tplPath . '/directory.html')) $dir = file_get_contents($tplPath . '/directory.html');
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
                $assign = function($tag) use ($galleryBase, $thumbsDir, $name, $width, $height, $thumbWidth, $thumbHeight)
                {
                    $from = array('/{thumbUri}/', '/{thumbWidth}/', '/{thumbHeight}/', '/{imageUri}/', '/{imageWidth}/', '/{imageHeight}/');
                    $to   = array($galleryBase . '/' . $thumbsDir . '/' . $name, $thumbWidth, $thumbHeight, $galleryBase . '/' . $name, $width, $height);
                    return preg_replace($from, $to, $tag);
                };
                $firstTags[] = $assign($firstImageTag);
                $lastTags[]  = $assign($lastImageTag);
            }
        }
        $assignDir = function($dirUri, $dirName) use ($dir)
        {
            return str_replace(array('{dirUri}', '{dirName}'), array($dirUri, $dirName), $dir);
        };
        if (substr_count(str_replace($GLOBALS['galleryBase'], '', $galleryBase), '/') > 0) {
            $dirs[] = $assignDir('../', '../');
        }
        if (is_array($dirList)) {
            foreach ($dirList as $key => $name) {
                $dirs[] = $assignDir($galleryBase . '/' . $name, $name);
            }
        }
        $replace  = (is_array($firstTags)) ? implode(PHP_EOL, $firstTags) : '';
        $noScript = (is_array($lastTags)) ? implode(PHP_EOL, $lastTags) : '';
        $subDirs  = (is_array($dirs)) ? implode(PHP_EOL, $dirs) : '';
        $page     = str_replace('{galleryPath}', $GLOBALS['galleryBase'], $page);
        $page     = str_replace('{images}', $replace, $page);
        $page     = str_replace('{imagesNoScript}', $noScript, $page);
        $page     = str_replace('{subDirs}', $subDirs, $page);
        file_put_contents($galleryFile, $page, LOCK_EX);
    }
}
if (is_dir(SCRIPT_PATH . $galleryPath) && file_exists(SCRIPT_PATH . $pageTemplate)) {
    generate();
}
?>