<?
//error_reporting(~E_ALL);
session_start();

global $yt, $PlaylistName, $Playlist;
$newPath = set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']."/Zend/ZendGdata-1.12.3/library");
require_once "Zend/Loader.php";	
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');


if($_REQUEST['showdebug']=="false" || $_SESSION['showdebug']==false){
	$debug=false;
	$_SESSION['showdebug']=false;
} else if($_REQUEST['showdebug']=="true" || $_REQUEST['showdebug']==true || $_SESSION['showdebug']==true){
	$debug=true;
	$_SESSION['showdebug']=true;
}

//$debug=true;

if(isset($_POST['Add2Playlist'])){
	$_SESSION['POST']=$_POST;
	if($debug){
		print_r($_SESSION);
		//print_r($_REQUEST);
		//print_r($_POST);
		echo "<br>";
	}
}

echo "<center><h2><u>Youtube Playlister for That Song Stuck in your Head</u></h2><br></center>";

$apikey = "AI39si7ZAEQ4QUKW6GFm9UvQHK_v41jxZH4stjzgWoLTsZ_pFCDecXOD64dSMCVIkkqqUQQrfm-7FvP6Opia10Lmuf_w5Qre2Q";
$httpClient = getAuthSubHttpClient();
if($httpClient==null) die();

try {
	$yt = new Zend_Gdata_YouTube($httpClient, "TSSIYH-Playlister-0.1", null, $apikey);	//$this->apikey
} catch (Zend_App_Exception $e) {
	die("<b>Failed to create Youtube object:</b> ".$e->getMessage()."<br>");
}
$yt->setMajorProtocolVersion(2);

//=================================================================================================================================
$PlaylistName='My TSSIYH Playlist';
if($_REQUEST['playlist']!="") $PlaylistName=$_REQUEST['playlist'];

echo "<b><u>Current User's playlists</u></b><br>";
getAndPrintPlaylistListFeed(false); //will also search for PlaylistName and if found, then will store in Playlist

if($Playlist===null){
	try {
	$Playlist = $yt->newPlaylistListEntry();
	$Playlist->title = $yt->newTitle()->setText($PlaylistName);
	$Playlist->summary = $yt->newSummary()->setText('Playlist derived from our awesome facebook group "That Song Stuck in your Head"');

	$postLocation = 'http://gdata.youtube.com/feeds/api/users/default/playlists';
	
		$yt->insertEntry($Playlist, $postLocation);
		/*You can add a video to a playlist by using a VideoEntry object. The following code retrieves a VideoEntry object with a known entry ID and then adds it to the playlist corresponding to the PlaylistListEntry object. Since the request does not specify a position where the video will appear in the playlist, the new video is added to the end of the playlist.
		*/
		$_SESSION['Playlist'] = true;
	} catch (Zend_Gdata_App_Exception $e) {
		$_SESSION['Playlist'] = false;
		die("<b>Failed to add new Playlist:</b> ".$e->getMessage()."<br>");
	}
} else $_SESSION['Playlist'] = true;


/* The following code updates a playlist's description.
$playlistToBeUpdated->description->setText('updated playlist description');
$playlistToBeUpdated->save();
*/

//if($debug){ echo "<br>PLAYLIST array:</b> "; print_r($Playlist);}
if(isset($_SESSION['POST']['Add2Playlist'])){
  $playlistID=$Playlist->getPlaylistId();
  $playlistURL = "http://www.youtube.com/view_play_list?p=".$playlistID;
  if($debug) echo "<b>Playlist's View Url=$playlistURL</b><br>";

  $postUrl = $Playlist->getPlaylistVideoFeedUrl();
  //$postUrl = 'http://gdata.youtube.com/feeds/api/playlists/'.$playlistID.'?v=2';
  echo "<h4><u>Adding selected songs to <a href='$playlistURL'>\"$PlaylistName\"</a></u></h4>";
  //if($debug) echo "Playlist's postUrl=<b>$postUrl</b><br>";
  $feed = $yt->getPlaylistVideoFeed($postUrl);
  //$feedlink=getPlayListLink($feed);
  //echo "Generated feedLink=<b>$feedlink</b><br>";

  $POST=$_SESSION['POST'];
  $videoCounter=0;
  foreach(array_keys($POST) as $post){
	if($debug) echo urldecode($post)." => {$POST[$post]}<br>";
    if($POST[$post]!="on") continue;

	//extract Youtube ID
	$urlInfo = parse_url(urldecode($post)); // to get url components (scheme:host:query)
	$urlVars = array();
	parse_str($urlInfo['query'], $urlVars);
	if($debug) echo "urlVars array".print_r($urlVars , true)."<br>"; //"parsed => ".print_r($urlInfo,true).
	
	$videoID=$urlVars['v'];
	$videoCounter++;
	try { 
	  // video entry to be added
	  $videoEntryToAdd = $yt->getVideoEntry($videoID);
	  //print_r($videoEntryToAdd);
	
	  // create a new Zend_Gdata_PlaylistEntry, passing in the underling DOMElement of the VideoEntry
	  $newPlaylistEntry = $yt->newPlaylistVideoEntry($videoEntryToAdd->getDOM()); //previously was newPlaylistListEntry
	  $newPlaylistEntry->setPosition($yt->newPosition($videoCounter)); // was added to prevent error Invalid Video Position 0
	  // post
	
	  $yt->insertEntry($newPlaylistEntry, $postUrl); //, 'Zend_Gdata_YouTube_PlaylistVideoEntry', array('X-GData-Key' => "key=$apikey"));
	
	  //addVideosToPlaylist($videoID, $Playlist, $yt);
	  
	  /*$playlistVideoEntryToBeModified = $yt->getPlaylistVideoFeedUrl($newPlaylistListEntry);
	  // move to top of playlist by setting position to 1
	  $playlistVideoEntryToBeModified->setPosition($yt->newPosition($videoCounter));
	  // update by putting the new entry to the entry's edit link
	  try {
		$yt->updateEntry($playlistVideoEntryToBeModified, $playlistVideoEntryToBeModified->getEditLink()->getHref());
	  } catch (Zend_App_Exception $e) {
		echo "<b>Failed to move video#$videoCounter position</b> ".$e->getMessage()."<br>";
	  }*/
	  echo "{$videoEntryToAdd->getVideoTitle()} added to Playlist <b>successfully!</b><br>";
	
	} catch (Zend_App_Exception $e1) {
	  echo "<b>Failed to add video#$videoCounter $videoID to Playlist, exception1:</b> ".$e1->getMessage()."<br>";
	} catch (Zend_Gdata_App_HttpException $httpException) {
		echo "<b>Failed to add video#$videoCounter $videoID</b>, httpEx: ".$httpException->getRawResponseBody()."<br>";
	} catch (Zend_Gdata_App_Exception $e2) {
	  echo "<b>Failed to add video#$videoCounter $videoID to Playlist, exception2:</b> ".$e2->getMessage()."<br>";
	} catch (Zend_Uri_Exception $e3) {
	  echo "<b>Failed to add video#$videoCounter $videoID to Playlist, Invalid video or URL:</b> ".$e3->getMessage()."<br>";
	}
  }
}


if($playlistURL!=""){
	echo '<br><br><form name="Go2Playlist" action="'.$playlistURL.'" method="post">
	<center>
	<input name="Go" type="submit" value="Go to Playlist" style="height: 25px; width: 200px"/>
	</center>
	</form>';
}

exit;
//--------------------------------------------------------------------------------------------------------------
function getAuthSubRequestUrl()
{
    $next = 'http://soundchilde.com/TSSIYH/playlister.php';
    $scope = 'http://gdata.youtube.com';
    $secure = false;
    $session = true;
    return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure, $session);
}
//--------------------------------------------------
function getAuthSubHttpClient()
{
    if (!isset($_SESSION['sessionToken']) && !isset($_GET['token']) ){
        echo '<a href="' . getAuthSubRequestUrl() . '">Login to your YouTube account first!</a>';
        return;
    } else if (!isset($_SESSION['sessionToken']) && isset($_GET['token'])) {
      $_SESSION['sessionToken'] = Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
    }

    $httpClient = Zend_Gdata_AuthSub::getHttpClient($_SESSION['sessionToken']);
    return $httpClient;
}
//--------------------------------------------------

function getAndPrintVideoFeed($location = Zend_Gdata_YouTube::VIDEO_URI)
{
  global $yt;
  //$yt = new Zend_Gdata_YouTube();
  // set the version to 2 to receive a version 2 feed of entries
  $yt->setMajorProtocolVersion(2);
  $videoFeed = $yt->getVideoFeed($location);
  printVideoFeed($videoFeed);
}
 //--------------------------------------------------

function printVideoFeed($videoFeed)
{
  $count = 1;
  foreach ($videoFeed as $videoEntry) {
    echo "Entry # " . $count . "<br>";
    printVideoEntry($videoEntry);
    echo "<br>";
    $count++;
  }
}
//--------------------------------------------------

function printVideoEntry($videoEntry) 
{
  // the videoEntry object contains many helper functions
  // that access the underlying mediaGroup object
  echo 'Video: ' . $videoEntry->getVideoTitle() . "<br>";
  echo 'Video ID: ' . $videoEntry->getVideoId() . "<br>";
  echo 'Updated: ' . $videoEntry->getUpdated() . "<br>";
  echo 'Description: ' . $videoEntry->getVideoDescription() . "<br>";
  echo 'Category: ' . $videoEntry->getVideoCategory() . "<br>";
  echo 'Tags: ' . implode(", ", $videoEntry->getVideoTags()) . "<br>";
  echo 'Watch page: ' . $videoEntry->getVideoWatchPageUrl() . "<br>";
  echo 'Flash Player Url: ' . $videoEntry->getFlashPlayerUrl() . "<br>";
  echo 'Duration: ' . $videoEntry->getVideoDuration() . "<br>";
  echo 'View count: ' . $videoEntry->getVideoViewCount() . "<br>";
  echo 'Rating: ' . $videoEntry->getVideoRatingInfo() . "<br>";
  echo 'Geo Location: ' . $videoEntry->getVideoGeoLocation() . "<br>";
  echo 'Recorded on: ' . $videoEntry->getVideoRecorded() . "<br>";
  
  // see the paragraph above this function for more information on the 
  // 'mediaGroup' object. in the following code, we use the mediaGroup
  // object directly to retrieve its 'Mobile RSTP link' child
  foreach ($videoEntry->mediaGroup->content as $content) {
    if ($content->type === "video/3gpp") {
      echo 'Mobile RTSP link: ' . $content->url . "<br>";
    }
  }
  
  echo "Thumbnails:<br>";
  $videoThumbnails = $videoEntry->getVideoThumbnails();

  foreach($videoThumbnails as $videoThumbnail) {
    echo $videoThumbnail['time'] . ' - ' . $videoThumbnail['url'];
    echo ' height=' . $videoThumbnail['height'];
    echo ' width=' . $videoThumbnail['width'] . "<br>";
  }
}

//--------------------------------------------------

function getAndPrintPlaylistListFeed($showPlaylistContents)
{
  global $yt;
  //$yt = new Zend_Gdata_YouTube();
  // optionally set version to 2 to retrieve a version 2 feed
  $yt->setMajorProtocolVersion(2);
  $playlistListFeed = $yt->getPlaylistListFeed('default');
  printPlaylistListFeed($playlistListFeed, $showPlaylistContents);
}
//--------------------------------------------------

function printPlaylistListFeed($playlistListFeed, $showPlaylistContents)
{
  $count = 1;
  foreach ($playlistListFeed as $playlistListEntry) {
    echo 'Entry # ' . $count . "<br>";

    // This function is defined in the next section
    printPlaylistListEntry($playlistListEntry, $showPlaylistContents);

    echo "<br>";
    $count++;
  }
}
//--------------------------------------------------

// print the metadata of the playlist entry itself
function printPlaylistListEntry($playlistListEntry, $showPlaylistContents = false)
{
  global $yt, $PlaylistName, $Playlist;
  echo 'Title: ' . $playlistListEntry->title->text . "<br>";
  if($playlistListEntry->title->text == $PlaylistName)	$Playlist=$playlistListEntry;
  echo 'Description: ' . $playlistListEntry->getSummary()->text . "<br>";	//previously $playlistListEntry->description->text

  // assuming $yt is a fully authenticated service object, set the version to 2
  // to retrieve additional metadata such as yt:uploaded and media:credit
  $yt->setMajorProtocolVersion(2);

  if ($showPlaylistContents === true) {
    // Get the feed of videos in the playlist
    $playlistVideoFeed =
      $yt->getPlaylistVideoFeed($playlistListEntry->getPlaylistVideoFeedUrl());

    // Print out metadata for each video in the playlist
    foreach ($playlistVideoFeed as $playlistVideoEntry) {
      // Reuse the printVideoEntry function defined earlier to show video metadata
      printVideoEntry($playlistVideoEntry);
  
      // The following details are also available for playlist entries
      echo 'Video originally uploaded on: ' .
        $playlistVideoEntry->getMediaGroup->getUploaded()->text . "<br>";
  
      // Also check the <media:credit> element to see whether the video
      // was uploaded by a partner.
      $mediaCredit = $playlistVideoEntry->getMediaGroup()->getMediaCredit();
      if ($mediaCredit) {

        echo 'Video originally uploaded by ' . $mediaCredit->text . "<br>";
        echo 'Media credit role: ' . $mediaCredit->getRole() . "<br>";
    
        // if the yt:type attribute is present, then the video was uploaded
        // by a YouTube partner
        echo 'Media credit type: ' . $mediaCredit->getYTtype() . "<br>";
      }
    }
  }
}
//--------------------------------------------------



function grab_dump($var)
{
    ob_start();
    var_dump($var);
    return ob_get_clean();
}

function getPlayListLink($playlist) {
    $test = grab_dump($playlist);
    $test = strstr($test, "http://gdata.youtube.com/feeds/api/playlists/");
    return strstr($test, "' countHint='0'", TRUE);
}

function addVideosToPlaylist($video, $playlistEntry, $yt) {
    $feedUrl = getPlayListLink($playlistEntry); 
	//echo "feedUrl=$feedUrl<br>";
	
    //foreach($videos_arr as $video)
    //{
        $videoEntryToAdd = $yt->getVideoEntry($video);
        $newPlaylistListEntry = $yt->newPlaylistListEntry($videoEntryToAdd->getDOM());
        $yt->insertEntry($newPlaylistListEntry, $feedUrl);
    //}
}
?>