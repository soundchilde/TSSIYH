<?php
/*Create a simple Youtube Playlist on the fly, without logging into your Google account:
http://www.labnol.org/internet/create-youtube-playlists/28827/
*/
if($_REQUEST['showdebug']==true){
	$debug=true;
	print_r($_POST);
	echo "<br><br>";
}

// Video IDs will be passed in Post
//if(isset($_POST['Add2Playlist'])) $_SESSION['POST']=$_POST;
$PlaylistName='My TSSIYH Playlist';
if($_REQUEST['playlist']!="") $PlaylistName=$_REQUEST['playlist'];

$videoCounter=0;
foreach(array_keys($_POST) as $post){
	if($debug) echo urldecode($post)." => {$_POST[$post]}";
    if($_POST[$post]!="on"){
		if($debug) echo " => Skipping<br>";
		continue;
	}
	//extract Youtube ID
	$urlInfo = parse_url(urldecode($post));
	$urlVars = array();
	parse_str($urlInfo['query'], $urlVars);
	
	$videoID=$urlVars['v'];
	if($videoID==""){ //sometimes, you wont get a videoID from 'v' parameter because the link maybe in youtu.be format
	  if($debug) echo"<br>"; print_r($urlInfo);  //eg.: Array ( [scheme] => http [host] => youtu_be [path] => /nWZMPxFdc1A&h=2AQH35hPm&s=1 ) 
	  if(stripos($urlInfo['host'],'youtu')!==false && $urlInfo['path']!=""){
	    $ampos = stripos($urlInfo['path'],"&", 1);
		$videoID=substr($urlInfo['path'], 1, $ampos-1);
	  }
	}
	
	$videoCounter++;
    if($videoCounter>1) $prefix=",";
    $videolist.=$prefix.$videoID;
	if($debug) echo "  | vid#$videoCounter => $videoID<br>";
}
if($videoCounter>0){
	$finallink="http://youtube.com/watch_videos?video_ids=".$videolist;
	echo "<br><br>Sending $videoCounter tracks to YouTube PlayLister:<br>$finallink";
	header("Location: $finallink");
} else {
	echo "<center>
			<h2><u>No valid YouTube videoIDs found!</u></h2>
			<br>Please go Back to the Indexer, select a few videos and try again!
		 </center>";
}
?>
