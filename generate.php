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

function generate($dirPath = '', $currentDir = '', $ariane = '', $privateBaseList = '', $dirs = '')
{
    $page          = (!file_exists(TEMPLATE_PATH . '/index.html')) ? '' : file_get_contents(TEMPLATE_PATH . '/index.html');
    $imageTag      = (!file_exists(TEMPLATE_PATH . '/imagetag.html')) ? '' : file_get_contents(TEMPLATE_PATH . '/imagetag.html');
    $dir           = (!file_exists(TEMPLATE_PATH . '/directory.html')) ? '' : file_get_contents(TEMPLATE_PATH . '/directory.html');
    $arianeTag     = (!file_exists(TEMPLATE_PATH . '/ariane.html')) ? '' : file_get_contents(TEMPLATE_PATH . '/ariane.html');
    $exifTag       = (!file_exists(TEMPLATE_PATH . '/exif.html')) ? '' : file_get_contents(TEMPLATE_PATH . '/exif.html');
    $dirPath       = (empty($dirPath)) ? GALLERY_PATH : preg_replace('|/+|', '/', $dirPath);
    $currentDir    = (empty($currentDir)) ? GALLERY_DIR : $currentDir;
    if ($dirPath !== GALLERY_PATH) {
        list($before, $after) = explode(GALLERY_DIR, $dirPath);
        $parentDir            = str_replace(array('{dirUri}', '{dirName}'), array('../', '..'), $dir);
        $after                = preg_replace('|/+|', '/', $after);
    }
    $galleryBase = PUBLIC_BASE . $after;
    $fullAriane  = $ariane . str_replace(array('{dirName}','{url}'), array($currentDir, $galleryBase), $arianeTag);
    if (is_dir($dirPath)) {
        $thumbsPath  = $dirPath . '/' . $GLOBALS['thumbsDir'];
        $noScan      = (is_array($GLOBALS['noScan'])) ? array_merge($GLOBALS['noScan'], array('.', '..', 'p')) : array('.', '..', 'p');
        if (!is_dir($thumbsPath)) mkdir($thumbsPath);
        $gallery = dir($dirPath);
        while (($entry = $gallery->read()) !== false) {
            if (preg_match($GLOBALS['imagePattern'], $entry)) {
                $imagesList[] = $entry;
            }
            elseif (is_dir($dirPath . '/' .$entry) && !in_array($entry, $noScan) && $entry[0] !== '_') {
                $dirs[] = str_replace(array('{dirUri}', '{dirName}'), array($galleryBase . '/' . $entry, $entry), $dir);
                generate($dirPath . '/' .$entry, $entry, $fullAriane, $privateBaseList);
            }
        }
        if (is_array($imagesList)) {
            $extractExif = function ($text = '', $source = '') use (&$extractExif, &$type, &$dirPath, &$name)
            {
                if ($type === 2 && !empty($text)) {
                    $exif = (is_array($source)) ? $source : exif_read_data($dirPath . '/' . $name);
                    if (is_array($exif) && count($exif) > 0 ) {
                        foreach ($exif as $pattern => $replace) {
                            $text = (is_array($replace)) ? $extractExif($text, $replace) : str_replace('{' . $pattern . '}', $replace, $text);
                        }
                        return $text;
                    }
                }
            };
            ($sort === 'desc') ? rsort($imagesList) : sort($imagesList);
            $imageFunctions = array(1 => array('fromgif', 'gif'), 2 => array('fromjpeg', 'jpeg', 90), 3 => array('frompng', 'png', 9), 4 => array('fromwbmp', '2wbmp'));
            foreach ($imagesList as $key => $name) {
                list($width, $height, $type, $attr) = getimagesize($dirPath . '/' . $name);
                $createFrom     = 'imagecreate' . $imageFunctions[$type][0];
                $createTo       = 'image' . $imageFunctions[$type][1];
                $thumbHeightMax = round($GLOBALS['thumbWidth'] * $GLOBALS['thumbRatio'][1] / $GLOBALS['thumbRatio'][0]);
                $thumbHeight    = (round($height * $GLOBALS['thumbWidth'] / $width) > $thumbHeightMax) ? $thumbHeightMax : round($height * $GLOBALS['thumbWidth'] / $width);
                $thumbWidth     = (round($height * $GLOBALS['thumbWidth'] / $width) > $thumbHeightMax) ? round($width * $thumbHeightMax / $height) : $GLOBALS['thumbWidth'];
                $thumbUri       = $galleryBase . '/' . $GLOBALS['thumbsDir'] . '/' . $name;
                if ($type === 1 && preg_match('/(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}/s', file_get_contents($dirPath . '/' . $name))) {
                    $thumbUri = $galleryBase . '/' . $name;
                }
                elseif (!file_exists($thumbsPath . '/' . $name) && (imagetypes() & $type)) {
                    $source = $createFrom($dirPath . '/' . $name);
                    $thumb  = imagecreatetruecolor($thumbWidth, $thumbHeight);
                    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                    imagedestroy($source);
                    $createTo($thumb, $thumbsPath . '/' . $name, $imageFunctions[$type][2]);
                }
                $commentName  = preg_replace($GLOBALS['imagePattern'], '${1}.html', $name);
                $imageComment = (file_exists($dirPath . '/' .$commentName)) ? file_get_contents($dirPath . '/' . $commentName) : '';
                $from         = array('{thumbUri}', '{thumbWidth}', '{thumbHeight}', '{imageUri}', '{imageWidth}', '{imageHeight}', '{imageComment}', '{imageExif}');
                $to           = array($thumbUri, $thumbWidth, $thumbHeight, $galleryBase . '/' . $name, $width, $height, $imageComment, $extractExif($exifTag));
                $imageTags[]  = str_replace($from, $to, $imageTag);
                if (is_dir($dirPath . '/p')) {
                    symlink($dirPath . '/' . $name, $dirPath . '/p/' . $name);
                    generate($dirPath . '/p', $currentDir, $ariane, $imageTags, $dirs);
                }
                if (strrpos($dirPath, '/p') !== false && is_array($privateBaseList)) {
                    if ($currentDir === GALLERY_DIR) unset($parentDir);
                }
            }
        }
        $comment  = (file_exists($dirPath . '/comment.html')) ? file_get_contents($dirPath . '/comment.html') : '';
        $images   = (is_array($imageTags)) ? implode(PHP_EOL, $imageTags) : '';
        $subDirs  = (is_array($dirs)) ? implode(PHP_EOL, $dirs) : '';
        $pageFrom = array('{galleryPath}', '{images}', '{parentDir}', '{subDirs}', '{ariane}', '{currentDir}', '{comment}');
        $pageTo   = array(PUBLIC_BASE, $images, $parentDir, $subDirs, $ariane, $currentDir, $comment);
        $page     = preg_replace('/{(.+)}/', '', str_replace($pageFrom, $pageTo, $page));
        file_put_contents($dirPath . '/index.html', $page, LOCK_EX);
    }
}
if (is_dir(GALLERY_PATH) && file_exists(TEMPLATE_PATH)) generate();