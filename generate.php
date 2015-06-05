<?php
$imagePattern = '/([^.]+)\.(jpg|png|gif)/Ui';
$galleryDir   = 'gallery';
$templateDir  = 'template';
$thumbsDir    = '_thumbs';
$thumbsPath   = $galleryDir . '/' . $thumbsDir;
$thumbWidth   = 200;
$thumbRatio   = array('4','3');
$publicBase   = '';

date_default_timezone_set('UTC');

define('SCRIPT_PATH', str_replace('generate.php', '', __FILE__));
if (file_exists(SCRIPT_PATH . 'config.php')) include SCRIPT_PATH . 'config.php';
define('GALLERY_PATH', (!empty($argv[1])) ? $argv[1] : SCRIPT_PATH . $galleryDir);
define('PUBLIC_BASE', (!empty($argv[2])) ? rtrim($argv[2], '/') : rtrim($publicBase, '/'));
define('GALLERY_DIR', (!empty(PUBLIC_BASE)) ? array_pop(explode('/', PUBLIC_BASE)) : $galleryDir);
define('TEMPLATE_PATH', (is_dir(GALLERY_PATH . '/_' . $templateDir)) ? GALLERY_PATH . '/_' . $templateDir : SCRIPT_PATH . $templateDir);

function generate($dirPath = '', $currentDir = '', $ariane = '')
{
    if (file_exists(TEMPLATE_PATH . '/index.html')) $page = file_get_contents(TEMPLATE_PATH . '/index.html');
    if (file_exists(TEMPLATE_PATH . '/firstimagetag.html')) $firstImageTag = file_get_contents(TEMPLATE_PATH . '/firstimagetag.html');
    if (file_exists(TEMPLATE_PATH . '/lastimagetag.html')) $lastImageTag = file_get_contents(TEMPLATE_PATH . '/lastimagetag.html');
    if (file_exists(TEMPLATE_PATH . '/directory.html')) $dir = file_get_contents(TEMPLATE_PATH . '/directory.html');
    if (file_exists(TEMPLATE_PATH . '/ariane.html')) $arianeTag = file_get_contents(TEMPLATE_PATH . '/ariane.html');
    if (empty($dirPath))  {
        $dirPath    = GALLERY_PATH;
        $currentDir = GALLERY_DIR;
    }
    else {
        $dirPath = preg_replace('|/+|', '/', $dirPath);
        list($before, $after) = explode(GALLERY_DIR, $dirPath);
        $parentDir            = str_replace(array('{dirUri}', '{dirName}'), array('../', '..'), $dir);
        $after   = preg_replace('|/+|', '/', $after);
    }
    $galleryBase = PUBLIC_BASE . $after;
    $fullAriane  = $ariane . str_replace(array('{dirName}','{url}'), array($currentDir, $galleryBase), $arianeTag);
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
                $dirs[] = str_replace(array('{dirUri}', '{dirName}'), array($galleryBase . '/' . $entry, $entry), $dir);
                generate($dirPath . '/' .$entry, $entry, $fullAriane);
            }
        }
        if (is_array($imagesList)) {
            ($sort === 'desc') ? rsort($imagesList) : sort($imagesList);
            $imageFunctions = array(1 => array('fromgif', 'gif'), 2 => array('fromjpeg', 'jpeg', 90), 3 => array('frompng', 'png', 9), 4 => array('fromwbmp', '2wbmp'));
            foreach ($imagesList as $key => $name) {
                $imageUri                           = $dirPath . '/' . $name;
                list($width, $height, $type, $attr) = getimagesize($imageUri);
                $createFrom     = 'imagecreate' . $imageFunctions[$type][0];
                $createTo       = 'image' . $imageFunctions[$type][1];
                $thumbHeight    = round($height * $thumbWidth / $width);
                $thumbHeightMax = round($thumbWidth * $GLOBALS['thumbRatio'][1] / $GLOBALS['thumbRatio'][0]);
                $thumbWidth     = ($thumbHeight > $thumbHeightMax) ? round($width * $thumbHeightMax / $height) : $GLOBALS['thumbWidth'];
                $thumbUri = $galleryBase . '/' . $thumbsDir . '/' . $name;
                if ($type === 1 && preg_match('/(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}/s', file_get_contents($imageUri))) {
                    $thumbUri = $galleryBase . '/' . $name;
                }
                elseif (!file_exists($thumbsPath . '/' . $name) && (imagetypes() & $type)) {
                    $source = $createFrom($imageUri);
                    $thumb  = imagecreatetruecolor($thumbWidth, $thumbHeight);
                    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                    imagedestroy($source);
                    $createTo($thumb, $thumbsPath . '/' . $name, $imageFunctions[$type][2]);
                }
                $commentName  = preg_replace($GLOBALS['imagePattern'], '${1}.html', $name);
                $imageComment = (file_exists($dirPath . '/' .$commentName)) ? file_get_contents($dirPath . '/' . $commentName) : '';
                $from         = array('{thumbUri}', '{thumbWidth}', '{thumbHeight}', '{imageUri}', '{imageWidth}', '{imageHeight}', '{imageComment}');
                $to           = array($thumbUri, $thumbWidth, $thumbHeight, $galleryBase . '/' . $name, $width, $height, $imageComment);
                $firstTags[]  = str_replace($from, $to, $firstImageTag);
                $lastTags[]   = str_replace($from, $to, $lastImageTag);
            }
        }
        $comment  = (file_exists($dirPath . '/comment.html')) ? file_get_contents($dirPath . '/comment.html') : '';
        $replace  = (is_array($firstTags)) ? implode(PHP_EOL, $firstTags) : '';
        $noScript = (is_array($lastTags)) ? implode(PHP_EOL, $lastTags) : '';
        $subDirs  = (is_array($dirs)) ? implode(PHP_EOL, $dirs) : '';
        $pageFrom = array('{galleryPath}', '{images}', '{imagesNoScript}', '{parentDir}', '{subDirs}', '{ariane}', '{currentDir}', '{comment}');
        $pageTo   = array(PUBLIC_BASE, $replace, $noScript, $parentDir, $subDirs, $ariane, $currentDir, $comment);
        $page     = str_replace($pageFrom, $pageTo, $page);
        file_put_contents($galleryFile, $page, LOCK_EX);
    }
}
if (is_dir(GALLERY_PATH) && file_exists(TEMPLATE_PATH)) generate();
?>
