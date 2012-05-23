<?php
/*
NanoAlbum
a simple and small PHP photo album /gallery
Goals:
-KISS - keep ist small and simple
-no database required
-zero configuration / little configuration
-small footprint: basic funtionality in just one single file
-no wasting of display area, use whole screen
-design for modern browsers , html5, css3
-design for mobile devices
-save bandwidth, using Client cache where possible
-provide original photos for download and viewing
-Licence: BSD licensed so you can do what you want with it
-demo: http://kurmis.com/album
*/

define('TITLE','Album');		// set the title/headline to whatever you like
define('THUMBNAIL_SIZE',160);		// size of the thumbnails in the album overview (160)
define('MEDIUM_SIZE',600);		// size of the image on the preview (600)
define('PRELOAD_IMAGES',true);		// preload next and previous image for faster gallery navigation (true)
define('CSS_INLINE', false);		// will use some bandwith but saves one extra http-request (true)
define('SELF', $_SERVER['SCRIPT_NAME']);
define('FOOTER','&copy; 2012 <a href="http://oliver-kurmis.de">Oliver Kurmis</a> | <a href="http://www.lima-city.de/homepage/ref:260626" title="webmaster &amp; community">powered by lima-city</a> | ');
$tStart=microtime(true);

// if nothing changed to what the client has in its cache, just sent the '304 Not Modified' http header
// otherwise send Caching-Information and Etag in http headers and then the content to the client (the browser)
function sendIfChanged($sContent, $iMaxAge=60) {
    header("Pragma: public"); 
    header("Cache-Control: max-age=".$iMaxAge); 		// let the browser cache the content
    header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$iMaxAge) . ' GMT'); 
    $sEtag=md5($sConten);
    if ($_SERVER['HTTP_IF_NONE_MATCH'] == $sEtag) {
	// Okay, the browser already has the
	// latest version of our page in his
	// cache. So just tell him that
	// the page was not modified and DON'T
	// send the content -> this saves bandwith and
	// speeds up the loading for the visitor
	header('HTTP/1.1 304 Not Modified');
    } else {	
	header('Etag: '.$sEtag); 
	echo $sContent;
    }
}

function getCss ($detached=false) {
    $sCss='
body { margin: 0px; padding: 10px; font: 12px Arial, Helvetica, sans-serif; color: #222; }
#page {text-align:left; }
#content {margin:auto;}
a {text-decoration:none;}
h1, h2 {margin: 5px;}
div#albums {}
div.albumThumb {background-color: #ddd; margin: 2px; border: 2px solid #999; border-radius: 7px; font-weight:bold; vertical-align:middle; float: left; width: '. (THUMBNAIL_SIZE + 20) .'px; height: '. (THUMBNAIL_SIZE + 50) .'px; text-align:center;}
.thumb {border: 2px solid #999; margin: 5px; max-width:'.THUMBNAIL_SIZE.'px; max-height:'.THUMBNAIL_SIZE.'px;vertical-align:middle;box-shadow: 3px 2px 5px #aaa;}
a.album {}
a:hover img.thumb {border: 2px solid blue;}
img { border: 0px;}
div.details {text-align:center;}
.preload {max-width: 50px; max-height:50px; display:none;}
div.details img {vertical-align:middle;box-shadow: 3px 2px 5px #aaa;}
div.descr {font-weight:bold; margin:10px;}
a.prevnext {padding:5px 10px; font-size: 60px;color: #999; border: 1px solid #999;border-radius: 5px;box-shadow: 3px 2px 5px #aaa;}
#footer { padding: 20px; font-size: 10px; color: #999; }
#footer a {color: #999;}';
    if ($detached) {
	header('Content-Type: text/css; charset=UTF-8',true);
	sendIfChanged($sCss, 50000);
	exit();
    }
	
    return $sCss;
}

function getPage ($sContent) {
    global $tStart;
	header('Content-Type: text/html; charset=UTF-8',true);
    	if (CSS_INLINE)
	    $sCssTag = '<style type="text/css">'.getCss().'</style>';
    	else
	    $sCssTag = '<link type="text/css" rel="stylesheet" href="'.SELF.'?css">';
	$sHtml='<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.TITLE.'</title>
'.$sCssTag.'
</head>
<body>
<div id="page">
	<div id="content">
	<h1><a accesskey="1" href="./">'.TITLE.'</a></h1>';
	$sHtml.=$sContent;
	$sHtml.='
</div>
<footer>
<div id="footer">
'.FOOTER.'
<a href="http://validator.w3.org/check?uri=referer">valid HTML 5</a> | 
<a href="http://jigsaw.w3.org/css-validator/check/referer">valid CSS 3</a> | 
'.(round(1000*(microtime(true)-$tStart))).' ms 
</div>
</footer>
</div>
</body>
</html>';
    sendIfChanged($sHtml);
}

function fastimagecopyresampled (&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
  // Plug-and-Play fastimagecopyresampled function replaces much slower imagecopyresampled.
  // Just include this function and change all "imagecopyresampled" references to "fastimagecopyresampled".
  // Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
  // Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
  //
  // Optional "quality" parameter (defaults is 3). Fractional values are allowed, for example 1.5. Must be greater than zero.
  // Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
  // 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
  // 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
  // 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
  // 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
  // 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.

  if (empty($src_image) || empty($dst_image) || $quality <= 0) { return false; }
  if ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
    $temp = imagecreatetruecolor ($dst_w * $quality + 1, $dst_h * $quality + 1);
    imagecopyresized ($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1, $dst_h * $quality + 1, $src_w, $src_h);
    imagecopyresampled ($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $dst_w * $quality, $dst_h * $quality);
    imagedestroy ($temp);
  } else imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
  return true;
}

// Create a thumbnail JPEG and send it to the browser
function getThumbImage ($filepath, $size = THUMBNAIL_SIZE) {
    	$path = dirname($filepath);
	$file = basename($filepath);
	$thumbspath=$path.'/.thumbs/'.$size;
	$thumbfilename  = $thumbspath.'/'.$file;
	
	if (!is_file($thumbfilename)) {
		if(stristr($file, ".jpg")||stristr($file, ".jpeg") ) 
			$src = imagecreatefromjpeg($filepath);
		 else die('not supportet');
		list($width,$height)=getimagesize($filepath); 	// get image dimensions
	    	if ($width > $height) {
			$newwidth = $size;			// landscape
			$newheight=round(($height/$width) * $size);
	   	} else {
		    	$newheight = $size;			// portrait
		    	$newwidth=round(($width/$height) * $size);
	    	}
		
		$tmp=imagecreatetruecolor($newwidth,$newheight);
		fastimagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height); // generate a new resized image
		if (!is_dir($thumbspath))
			if(!@mkdir($thumbspath,0700,true))
		    		die('sorry, unable to create thumbnail directory, please check permissions');
		imageinterlace($tmp, true); 		    	// turn interlace on, better for slow connections e.g. on mobile devices
		imagejpeg($tmp,$thumbfilename); 	    	// create and save jpg
		imagedestroy($src);
		imagedestroy($tmp);
	}
	

	$expires = 60*60*24*14; 		                // seconds, minutes, hours, days	
	header("Pragma: public"); 
	header("Cache-Control: maxage=".$expires); 		// let the browser cache the images
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT'); 		
	header("Content-Type: image/jpeg",false);
	header("Content-Length: ".@filesize("$thumbfilename"));
    	if (!@readfile($thumbfilename)) {			// send file content to the browser
		header("Content-Type: text/plain",true);	// or if not possible
		die('could not read file:'.$thumbfilename); // give simple error message
	};
	exit();
}

function getDetails ($filepath) {
    	$path = dirname($filepath);
    	$file = basename($filepath);
    	list($aDirs,$aImages)=getDirectory($path);
    	
    	$pref=$next=false;
	foreach ($aImages as $i => $sFile) {
	    if ($sFile == $file) {		// find previous and next image
		if ($i>0) $pref = $aImages[$i-1];
		$next = $aImages[$i+1];
		break;
	    }
	}
    	$sHtml='<div class="details">';
    	if ($path != '.')
	    	$sHtml.='<a href="'.getAlbumUrl($path).'" title="go to album '.$path.'"><h2>'.$path.'</h2></a>';
    	if ($pref)
	    	$sHtml.='
<a class="prevnext" href="'.getDetailsUrl($path.'/'.$pref).'">&lt;</a>';
    	$sHtml.='
		<a id="img" href="'.url_encode($filepath).'" title="'.$file.' klick to view fullsize"><img alt="'.$file.'" src="'.getThumbUrl($filepath,MEDIUM_SIZE).'"></a>';
	if ($next)
	    	$sHtml.='
    			<a class="prevnext" href="'.getDetailsUrl($path.'/'.$next).'">&gt;</a>';
	$sHtml.='
		<div class="descr">'.$file.'</div>
	</div>';
    
	if (PRELOAD_IMAGES) { // preload prev/next image
		if ($next)
	    		$sHtml.='<img class="preload" alt="next image" src="'.getThumbUrl($path.'/'.$next,MEDIUM_SIZE).'">';
	    	if ($pref)
	    		$sHtml.='<img class="preload" alt="previous image" src="'.getThumbUrl($path.'/'.$pref,MEDIUM_SIZE).'">';
	}
	return $sHtml;
}

// get all directory entries into a sorted array
function getDir ($directory) {
    	$aDir=array();
	if (empty($directory))
    		$directory = './';
	$handle = openDir($directory);
 	while (false !== ($sFile=readDir($handle)))
	    if ($sFile[0] != "." ) 		// ignore ".", "..", and ".thumbs" 
		$aDir[]=$sFile;
	closeDir($handle);
	sort($aDir);
	return $aDir;
}

function getDirectory ($directory) {
    	$aDirs=$aImages=array();
	if (empty($directory))
    		$directory = '.';
    	$directory.='/';
	$handle = openDir($directory);
 	while (false !== ($sFile=readDir($handle)))
	    if ($sFile[0] != "." ) {		// ignore ".", "..", and ".thumbs" 
		if (is_dir($directory.$sFile)) {
			$aDirs[]=$sFile;
	    	} elseif (stristr($sFile, ".jpg") || stristr($sFile, ".jpeg")) {
			$aImages[]=$sFile;
	    	}
	    }
	closeDir($handle);
	sort($aDirs);
    	sort($aImages);
	return array($aDirs,$aImages);
}

function url_encode ($filepath) {
    return str_replace("%2F", "/", rawurlencode($filepath));
}

function getThumbUrl ($filepath, $size = THUMBNAIL_SIZE) {
    	$path = dirname($filepath);
	$file = basename($filepath);
	$thumbspath=$path.'/.thumbs/'.$size;
	$thumbfilename  = $thumbspath.'/'.$file;
	if (!is_file($thumbfilename)) {
	    if ($size == MEDIUM_SIZE)
		return SELF."?m=".urlencode($filepath);
	     return SELF."?t=".urlencode($filepath);
	}
    	return url_encode($thumbfilename);
}

function getAlbumUrl ($sPath) {
    return SELF.'?a='.urlencode($sPath);
}

function getDetailsUrl ($filepath) {
    return SELF.'?d='.urlencode($filepath).'#img';
}

function getAlbumThumbnail ($directory) {
    list($aDirs,$aImages)=getDirectory($directory);
    if ($i=count($aImages)) {
	$iMiddle = round($i/2);
	return "<img class=\"thumb\" alt=\"".$aImages[$iMiddle]."\" src=\"".getThumbUrl($directory.'/'.$aImages[$iMiddle])."\">";
    }
    return false;
}

function getAlbum($directory) {
    $sHeadline=$sAlbums=$sThumbs='';
    $path = dirname($filepath);

    list($aDirs,$aImages)=getDirectory($directory);
    if ($directory) {
	    $sHeadline.='<h2>'.$directory.'</h2>';
	    $directory.='/';
    }
    foreach ($aDirs as $sFile)
        $sAlbums.="
<div title=\"album\" class=\"albumThumb\"><a class=\"album\" href=\"".getAlbumUrl($directory.$sFile)."\" >".$sFile.'<br>'.getAlbumThumbnail($directory.$sFile)."</a></div>";
    foreach ($aImages as $sFile)
        $sThumbs.="
    <a href=\"".getDetailsUrl($directory.$sFile)."\"><img class=\"thumb\" alt=\"$sFile\" title=\"$sFile\" src=\"".getThumbUrl($directory.$sFile)."\"></a>";
    if ($sAlbums)				// put sub albums in an extra div
        $sAlbums = '
        <div id="albums">'.$sAlbums.'
</div><div style="clear:both"></div>';
    return($sHeadline.$sAlbums.$sThumbs);
}


if ($_REQUEST['t'])				        // get thumbnail with the given path and filename
	getThumbImage($_REQUEST['t']);
elseif ($_REQUEST['m']) 			        // get mid size image with the given path and filename
	getThumbImage($_REQUEST['m'],MEDIUM_SIZE);
elseif (isset($_GET['css'])) 			        // get mid size image with the given path and filename
	getCss(true);
elseif ($_REQUEST['d']) 			        // get css file
	$sHtml=getDetails($_REQUEST['d']);
else
	$sHtml=getAlbum($_REQUEST['a']); 		// get album of images

getPage($sHtml);
?>