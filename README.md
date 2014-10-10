#piclist

*Simple php static gallery generator (less than 5kb)*

##How to use

###_Script installation_

- Download the script at https://github.com/Roultabie/piclist/archive/master.zip
- Extract it on your web server
- Create a cron task like this : ```* 1 * * * php /path/to/the/script/generate.php```
- In the case of using the script in sub directory, you must specify the entire url to access him in config file like : ```$galleryBase = '//your.domain.com/pics/';```

###_Server configuration (optional)_

- Create a VirtualHost that points to the gallery dir like ```/path/to/the/script/gallery```

###_Uploading_

- Upload your pics in the gallery dir and wait for the cron do its job.

##Extras

###_config.php_

A config.php can be created to changing options of the script.  

Availables vars are :  

```
<?php
$imagePattern     = '/.*\.[jpg|JPG]/u'; // A pattern PCRE of specific pics (like png or gif format or your imagination of script use)
$galleryPath      = 'gallery'; // If you want move gallery dirname
$galleryFile      = 'index.html'; // If you want use gallery with other script or call him in a specific name (like pics.html)
$thumbsDir        = 'thumbs'; // If you want move thumbs dirname
$thumbsPath       = $galleryPath . '/' . $thumbsDir; // ... Never change it, if you don't know what you do
$pageTemplate     = 'template/index.html'; // If you want use your own theme, add the path here
$imageTag         = '<a href="{imageUri}"><img src="blank.gif" alt="" data-echo="{thumbUri}"></a>'; // Base img tag
$imageNoScriptTag = '<a href="{imageUri}"><img src="{thumbUri}"></a>'; // img tag if javascript is disabled
$thumbWidth       = 200; // the width of thumb
$galleryBase      = ''; // In the case of using the script in sub directory, you must specify the entire url to access him here
?>
```

###_Create your own theme_

You just need to add your theme dir with an index.html in the script root and declare him in config.php:  

Availables tags ares :  

- **{galleryPath}** : Replaced by the the url of gallery
- **{images}** : Replaced by the base img tag
- **{imagesNoScript}** : Replace the no script img tag
- **{imageUri}** : Replaced by the url of current image
- **{imageWidth}** and {imageHeight} : Respectively replaced by the image width and height
- **{thumbUri}** : Replaced by the url of current thumb
- **{thumbWidth}** and **{thumbHeight}** : Respectively replaced by the thumb width and height

###_Using multiples galleries_

#### In the same script dir

_Coming soon_

#### In another path

Copy your script in your new path and adapt config.php

###_Recursive gallery_

Actually, I don't known if coding specific recursive gallery is important, I want a KISS and light system.
Perhaps in few times...