<?php
$imagePattern = '/[^.]+\.(jpg|png|gif)/u';
$galleryDir   = 'gallery';
$templateDir  = 'template';
$thumbsDir    = '_thumbs';
$thumbsPath   = $galleryDir . '/' . $thumbsDir;
$thumbWidth   = 200;
$publicBase   = '';

date_default_timezone_set('UTC');

define('SCRIPT_PATH', str_replace('generate.php', '', __FILE__));
if (file_exists(SCRIPT_PATH . 'config.php')) include SCRIPT_PATH . 'config.php';
define('GALLERY_PATH', (!empty($argv[1])) ? $argv[1] : SCRIPT_PATH .$galleryDir);
define('PUBLIC_BASE', (!empty($argv[2])) ? $argv[2] : $publicBase);
define('GALLERY_DIR', (!empty(PUBLIC_BASE)) ? array_pop(explode('/', PUBLIC_BASE)) : $galleryDir);
define('TEMPLATE_PATH', (is_dir(GALLERY_PATH . '/_' . $templateDir)) ? GALLERY_PATH . '/_' . $templateDir : SCRIPT_PATH . $templateDir);

function generate($dirPath = '')
{
    if (empty($dirPath))  {
        $dirPath = GALLERY_PATH;
    }
    else {
        list($before, $after) = explode(GALLERY_DIR, $dirPath);
    }
    $galleryBase = PUBLIC_BASE . $after;
    if (is_dir($dirPath)) {
        $thumbsDir   = $GLOBALS['thumbsDir'];
        $thumbsPath  = $dirPath . '/' . $thumbsDir;
        $galleryFile = $dirPath . '/index.html';
        $noScan      = (is_array($GLOBALS['noScan'])) ? array_merge($GLOBALS['noScan'], array('.', '..')) : array('.', '..');
        if (!is_dir($thumbsPath)) mkdir($thumbsPath);
        $gallery = dir($dirPath);
        while (($entry = $gallery->read()) !== false) {
            if (preg_match($GLOBALS['imagePattern'], $entry)) {
                $imagesList[] = $entry;
            }
            elseif (is_dir($dirPath . '/' .$entry) && !in_array($entry, $noScan) && $entry[0] !== '_') {
                $dirList[] = $entry;
                generate($dirPath . '/' .$entry);
            }
        }
        if (file_exists(TEMPLATE_PATH . '/index.html')) $page = file_get_contents(TEMPLATE_PATH . '/index.html');
        if (file_exists(TEMPLATE_PATH . '/firstimagetag.html')) $firstImageTag = file_get_contents(TEMPLATE_PATH . '/firstimagetag.html');
        if (file_exists(TEMPLATE_PATH . '/lastimagetag.html')) $lastImageTag = file_get_contents(TEMPLATE_PATH . '/lastimagetag.html');
        if (file_exists(TEMPLATE_PATH . '/directory.html')) $dir = file_get_contents(TEMPLATE_PATH . '/directory.html');
        if (is_array($imagesList)) {
            ($sort === 'desc') ? rsort($imagesList) : sort($imagesList);
            $imageFunctions = array(1 => array('fromgif', 'gif'), 2 => array('fromjpeg', 'jpeg', 90), 3 => array('frompng', 'png', 9), 4 => array('fromwbmp', '2wbmp'));
            foreach ($imagesList as $key => $name) {
                $imageUri                           = $dirPath . '/' . $name;
                list($width, $height, $type, $attr) = getimagesize($imageUri);
                $createFrom = 'imagecreate' . $imageFunctions[$type][0];
                $createTo   = 'image' . $imageFunctions[$type][1];
                $thumbWidth                         = $GLOBALS['thumbWidth'];
                $thumbHeight                        = round($height * $thumbWidth / $width);
                if (!file_exists($thumbsPath . '/' . $name)) {
                    $source = $createFrom($imageUri);
                    $thumb  = imagecreatetruecolor($thumbWidth, $thumbHeight);
                    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                    imagedestroy($source);
                    $createTo($thumb, $thumbsPath . '/' . $name, $imageFunctions[$type][2]);
                }
                $assign = function($tag) use ($galleryBase, $thumbsDir, $name, $width, $height, $thumbWidth, $thumbHeight)
                {
                    $from = array('{thumbUri}', '{thumbWidth}', '{thumbHeight}', '{imageUri}', '{imageWidth}', '{imageHeight}');
                    $to   = array($galleryBase . '/' . $thumbsDir . '/' . $name, $thumbWidth, $thumbHeight, $galleryBase . '/' . $name, $width, $height);
                    return str_replace($from, $to, $tag);
                };
                $firstTags[] = $assign($firstImageTag);
                $lastTags[]  = $assign($lastImageTag);
            }
        }
        $assignDir = function($dirUri, $dirName) use (&$dirs, $dir)
        {
            $dirs[] = str_replace(array('{dirUri}', '{dirName}'), array($dirUri, $dirName), $dir);
        };
        if (strpos($after, '/') !== false) $assignDir('../', '..');
        if (is_array($dirList)) {
            foreach ($dirList as $key => $name) {
                $assignDir($galleryBase . '/' . $name, $name);
            }
        }
        $replace  = (is_array($firstTags)) ? implode(PHP_EOL, $firstTags) : '';
        $noScript = (is_array($lastTags)) ? implode(PHP_EOL, $lastTags) : '';
        $subDirs  = (is_array($dirs)) ? implode(PHP_EOL, $dirs) : '';
        $pageFrom = array('{galleryPath}', '{images}', '{imagesNoScript}', '{subDirs}');
        $pageTo   = array(PUBLIC_BASE,  $replace, $noScript, $subDirs);
        $page     = str_replace($pageFrom, $pageTo, $page);
        file_put_contents($galleryFile, $page, LOCK_EX);
    }
}
if (is_dir(GALLERY_PATH) && file_exists(TEMPLATE_PATH)) generate();
?>