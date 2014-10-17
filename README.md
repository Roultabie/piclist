#piclist

*Simple php static gallery generator (less than 5kb)*  
*Needd PHP >= 5.3

##How to use

###_Script installation_

- Download the script at https://github.com/Roultabie/piclist/archive/master.zip
- Extract it on your web server
- Create a cron task like this : ```* 1 * * * php /path/to/the/script/generate.php```
- In the case of using the script in sub directory, you must specify the entire url to access him in config file like : ```$galleryBase = '//your.domain.com/pics/';```

###_Server configuration (optional)_

- Create a VirtualHost that points to the gallery dir like ```/path/to/the/script/gallery```

###_Uploading_

- Upload your pics and dirs in the gallery dir and wait for the cron do its job.

##Extras

###_config.php_

A config.php can be created to changing options of the script.  

Availables vars are :  

```
<?php
$imagePattern = '/([^.]+)\.(jpg|png|gif)/u'; // A pattern PCRE of specific pics (like png or gif format or your imagination of script use)
$galleryDir   = 'gallery'; // If you want rename default gallery dirname
$templateDir  = 'template'; // If you want rename default template dirname
$thumbsDir    = '_thumbs'; // If you want rename default thumbs dirname (don't forger undescore)
$thumbsPath   = $galleryDir . '/' . $thumbsDir; // ... Never change it, if you don't know what you do
$thumbWidth   = 200; // Width of thumbs
$thumbRatio   = array('4','3'); // Ratio of thumbs
$publicBase   = ''; // In the case of using the script in sub directory, you must specify the entire url to access him here like /access/to/my/gallery
?>
```

###_Create your own theme_

You just need to create a dir named  ```_template``` in your gallery dir,
This dir need this 5 files :  

- index.html (gallery main page)
- firstimagetag.html (principal image tag)
- lastimagetag.html (in case if you are using javascript, you can use this for ```<noscript>``` section
- directory.html (the sub dir view)
- ariane.html (the breadcumb)

For display elements, you must use tags syntax : ```{mytag}``` in this files.

Availables tags are :  

- **{galleryPath}** : Replaced by the the url of gallery
- **{images}** : Replaced by the base img tag
- **{imagesNoScript}** : Replace the no script img tag
- **{imageUri}** : Replaced by the url of current image
- **{imageWidth}** and {imageHeight} : Respectively replaced by the image width and height
- **{thumbUri}** : Replaced by the url of current thumb
- **{thumbWidth}** and **{thumbHeight}** : Respectively replaced by the thumb width and height
- **{subDirs}** : Replaced by sub directories
- **{ariane}** : The breadcumb
- **{currentDir}** : Replaced by current dir name
- **{comment}** : Comment of current gallery (if comment.html file exists)
- **imageComment}** : Comment of each image (if img.html files exists)

###_Using multiples galleries_

#### In another path

The command line to launch is : ```php generate.php /path/to/another/path (if needed: /my/sub/dir/gallery)```  

Commande lines examples : 

Script dir is on ```/var/www``` and users galleries are in ```/home/{user1,userX}/~public/``` and the url to access them are like : user1.website.com, userX.website.com  

```php /var/www/piclist/generate.php /home/user1/~public/```  
```php /var/www/piclist/generate.php /home/userX/~public/```  

Now, users have galleries on differents sub dirs. User1 in mywebsite/pictures , user2 42/misc/perfect/images url to access them are ~public/gallery and ~public/42/misc/perfect/images:   

```#User 1:
php /var/www/piclist/generate.php /home/user1/~public/pictures /gallery

#user2:
php /var/www/piclist/generate.php /home/user2/~public/42/misc/perfect/images /42/misc/perfect/images```  

Users can theming galleries with _template dir in galleries root.

###_Recursive gallery_

Just create sub directories and upload your pictures.
