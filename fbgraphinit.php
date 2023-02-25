<? 
session_start();

define('FACEBOOK_SDK_V4_SRC_DIR', $_SERVER['DOCUMENT_ROOT']. '/facebook-graph-v5/');
try{
	require_once FACEBOOK_SDK_V4_SRC_DIR . 'autoload.php';
} catch(Exception $o){
	//wb_message_box($window, "Error including facebook.php"); 
	die("Error including Facebook Graph API");
}

$homesite = "thatsongstuckinyourhead.com";
$gid='155148594536905'; // facebook.com/groups/tssiyh

$NoYes=array("No", "Yes");
global $debug; $debug=false;
if(isset($_REQUEST['showdebug'])) if($_REQUEST['showdebug']=="true" || $_REQUEST['showdebug']=="1"){
	$debug=true;
	$debugappend="?showdebug=true";
	if($formlink!="" && stristr($formlink,"showdebug=true")===false) $formlink.=$debugappend;
	error_reporting(E_ALL & ~E_NOTICE);
}
$callback = "fbgraphinit.php".$debugappend; //default points back to init script
if($formlink!="") $callback=$formlink; //if init has been called from another page, then point callback there
$callback = "http://$homesite/$callback";
if($debug) d($callback, "ReturnURL");

//TSSIYH app config details:
$fbconfig['appid']  = "194324133937765";	//"your application id";
$fbconfig['apikey']  = "8152a4b299d4911965f21dacc6678f41";	//"your application api key";
$fbconfig['secret']  = "0e06db021cc1de42c65bb678370b70c5";	//"your application secret key";
$fbconfig['client_token']  = "c46760475f9f9c073894bc6002946bea";

global $fb;
$fb = new Facebook\Facebook([
  'app_id' => $fbconfig['appid'], // Replace {app-id} with your app id
  'app_secret' => $fbconfig['secret'],
  'default_graph_version' => 'v2.5',
  ]);
  
// Check if the user is logged in.
$helper = $fb->getRedirectLoginHelper();
try {
 if (isset($_SESSION['tssiyh_fbtoken'])) {
   $accessToken = $_SESSION['tssiyh_fbtoken'];
 } else {
   $accessToken = $helper->getAccessToken();
 }
   //---------- IF USER IS ALREADY LOGGED IN, YOU HAVE A VALID ACCESS TOKEN -------------
  if(!isset($accessToken)){
	$permissions = ['public_profile', 'email', 'user_posts', 'user_managed_groups']; //'read_stream', 'user_groups' have been deprecated
	if($_REQUEST['PostToGroup']=="true") $permissions[] = "publish_actions"; //prev => "publish_stream"
	//$callback    = "http://$homesite/fbgraphinit.php".$debugappend;
	$loginUrl = $helper->getLoginUrl($callback, $permissions);
	//If the call came from another page, then redirect automatically otherwise show Login link
	if($formlink=="") echo '<a href="'.htmlspecialchars($loginUrl).'">Login to TSSIYH Indexer with Facebook!</a>';
	else if(!isset($_SESSION['tssiyh_fbtoken'])) echo "<script>window.top.location.href='".$loginUrl."';</script>"; //prev => htmlspecialchars($loginUrl)
	
  } else { // LOGIN SUCCESS! If successful, FB will redirect to the callback address with a hashed code
	//Now convert existing short-lived access code to a long-livde one
	//if(!isset($_SESSION['tssiyh_fbtoken']) ){ 
	$client = $fb->getOAuth2Client();
	try {
	  // Returns a long-lived access token
	  $accessToken = $client->getLongLivedAccessToken($accessToken);
	  //if($debug){"AccessToken details: "; print_r2($accessToken); echo "<br>";}
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
	  // There was an error communicating with Graph
	  echo "AccessToken Error"; if($debug) echo ": ".$e->getMessage(); 
	  exit;
	}
	//}
	$loginsuccess=true;
	$fb->setDefaultAccessToken($accessToken);
	// User authenticated your app! // Save the access token to a session and redirect
	$_SESSION['tssiyh_fbtoken'] = (string) $accessToken;
	// Returns expiration as a DateTime entity
	$expiresAt = $accessToken->getExpiresAt();
	$expired = $accessToken->isExpired();
	$isLong = $accessToken->isLongLived();

	// --------------------------------------------- GET USER -------------------------------------------------
	# Facebook PHP SDK v5: Retrieve User's Profile Information
	try {
		$userMe = getFBobject('/me?fields=id,name,birthday,email,gender,location,relationship_status,website');
		if($debug) d($userMe, "My Profile");
		/*getFBobject returns data array by default as follows: ["name"]=> string(9) "Alex John" ["id"]=> string(10) "1661979403" } My Profile : Facebook\GraphNodes\GraphNode Object ( [items:protected] => Array ( [name] => Alex John [id] => 1661979403 ) )  |  array(3) { ["data"]=> array(25) => followed by an array containing 25 friends */
		if (!$userMe || $_REQUEST['forcelogin']==true) {
			unset($_SESSION['tssiyh_fbtoken']);
			$loginUrl = $helper->getLoginUrl($callback, $permissions);
			echo "<p>Redirecting you to TSSIYH Indexer home...<br></p>";
			echo "<script type=\"text/javascript\">top.location.href = '$loginUrl';</script>";
		} else {
			// Proceed knowing you have a logged in user who's authenticated.
			$myId = $userMe['id']; //$userMe->getProperty('id');
			$myName = $userMe['name']; //$userMe->getProperty('name');
			$myEmail = $userMe['email'];
			$Friends = getFBobject('/me/friends?limit=500'); 
			$likes = getFBobject('/me/likes');
			$fanpageliked = false;
			$appliked = false;
			foreach($likes['data'] as $ilike)  if($ilike['id'] == $fbconfig['appid']) $appliked = true;

			if($debug){
				echo "<font color='white'><p>If you are reading this then you must be doing something right!</p><hr />";
				echo "<p>Your name is: $myName</p>";
				echo "<p>Your id is: $myId</p></font>";
				//echo "<p>Your Like App status is: ".($appliked==true?"Yes":"No")."</p>";
			}
		}//end of User check
		
		//------------------ NOW RETRIEVE THE GROUP THAT USER IS MEMBER OF ------------------------
		//Use "/$userid/groups/" to read the Facebook Groups a person belongs to 
		$TSSIYH = getFBobject("/$gid?fields=name,cover,description,icon,owner,updated_time"); 
		if($debug) d(isset($TSSIYH), "Group found?");
		if(isset($TSSIYH)){
			//$tssiyhAdmins = getFBobject("/$gid/admins/");
			$tssiyhFeed = getFBobject("/$gid/feed/");
			$tssiyhMembers = getFBobject("/$gid/members/");
		}
		
	} catch (Facebook\Exceptions\FacebookSDKException $e) {
		error_log($e);
		$userMe = null;
		echo "FB Graph User/Group error";
		if($debug) echo ": ".$e->getMessage()."<br>";
		exit;
	}
 
	// Finally, if logged in successfully, then create and store LogOut URL for later
	$logoutUrl = $helper->getLogoutUrl('{'.$accessToken.'}', 'http://www.facebook.com/groups/155148594536905/');
	//check to kill Cookies
	if($_REQUEST['killcookies']==true || $_REQUEST['logout']==true){
		_killFacebookCookies($fbconfig['apikey']);
	}
	if($_REQUEST['logout']==true){
		die("<script type=\"text/javascript\">top.location.href = '$logoutUrl';</script>");
	}

  } // end of accessToken check

} catch(Facebook\Exceptions\FacebookResponseException $e) {
 	// Error communicating with Graph or it returns an error
	error_log($e);
 	echo "<br>FB Graph Error: ". $e->getMessage();
  	exit;
} catch( Facebook\Exceptions\FacebookSDKException $e ) {
   // When validation fails or other local issues
  error_log($e);
  echo "<br>FB Login Error: ". $e->getMessage();
  if($debug && $helper->getError() ){
	var_dump( $helper->getError() );
	var_dump( $helper->getErrorCode() );
	var_dump( $helper->getErrorReason() );
	var_dump( $helper->getErrorDescription() );
	// You could display a message to the user being all like, "What? You don't like me?"
  }
  exit;
} //main try/catch for getRedirectLoginHelper

//-------------------------------------------------------------------------------------------------------
//	FACEBOOK RELATED FUNCTIONS
//-------------------------------------------------------------------------------------------------------
function getFBobject($path, $getype="array"){
  global $fb, $debug;
  
  $fbresponse = $fb->get($path);
  if($getype=="array") $node=$fbresponse->getDecodedBody();
  else{
	if($getype=="execute") $fbresponse = $fbresponse->execute();
	$node = $fbresponse->getGraphObject();
  }
  //if($debug) print_tree($node);
  return $node;
}

//------------------------------------------------------
function getPagingResults1($query, $maxiterations=1000){
	global $fb, $debug;

	$offset = 0;       // Initial offset
	$limit  = 500;     // Maximum number of records per chunk
	$querydata = array(); // Result array for friend records accumulation
	
	$joiner = stripos($query,"?")===false ? "?" : "&"; 
	
	$i=0;
	do{ //keep grabbing data until there are no more paging results or you hit the max iterations
		$chunk = getFBobject($query.$joiner."offset=$offset&limit=$limit");
		$querydata = array_merge( $querydata, $chunk['data'] );
		$offset += $limit;
		$i++;
	} while($chunk['data'] && $i<=$maxiterations ); // or use array_key_exists('paging', $data)
}


//------------------------------------------------------
function getPagingResults2($arr,$query)
{ //usage: initialize with an empty array and the function will call itself until pagination is done!
  //e.g. getPagingResults2(array(),"/457568434275922/posts?fields=message")
	global $fb;
	
    $chunk = $fb->getFBobject($query);
    if($chunk['data']==null) return $arr;
    else
    {
        foreach($chunk['data'] as $dato) $arr['data'][] = $dato;
        return getPagingResults2($arr,substr($chunk['paging']['next'],26));
    }
}
//------------------------------------------------------

function PostToGroup($strMsg, $post="257423157642781"){ //this postId points to Rise of the AutoBot thread
	global $fb, $gid;
	
	echo str_replace("\r\n", "<br>",$strMsg);
	if(isset($_REQUEST['PostToGroup'])) if($_REQUEST['PostToGroup']=="true"){
		try {
			$statusUpdate = $fb->api('/'.$post.'/comments', 'post', array('message'=> $strMsg));	//.$gid.'/'
		} catch (FacebookApiException $e) {
			d($e, "Failed to send update");
		}
	}
}

//------------------------------------------------------

function _killFacebookCookies($apiKey)
{
    $cookies = array('user', 'session_key', 'expires', 'ss');
    foreach ($cookies as $name) 
    {
        setcookie($apiKey . '_' . $name, false, time() - 3600);
        unset($_COOKIE[$apiKey . '_' . $name]);
    }
	
	$fb_key = 'fbs_'.$apiKey;
	setcookie($fb_key, '', '', '', '/', '');

    setcookie($apiKey, false, time() - 3600);
    unset($_COOKIE[$apiKey]);    

}

//-------------------------------------------------------------------------------------------------------
//	USEFUL FUNCTIONS
//-------------------------------------------------------------------------------------------------------
function SimplifyLink($url){
	$url=str_replace("http://", "", $url);
	$url=str_replace("https://", "", $url);
	$url=str_replace("www.", "", $url);
	return $url;
}

//------------------------------------------------------
function FullFlush(){
    echo(str_repeat(' ',256));
    // check that buffer is actually set before flushing
    if (ob_get_length()){           
        @ob_flush();
        @flush();
        @ob_end_flush();
    }   
    @ob_start();
}

//------------------------------------------------------
 function d($d, $desc=""){
	global $debug;
	$str = print_r($d, true);
	if($debug) echo "<font color='white'>";
	echo $desc." : ".$str."<br>";
	if($debug) echo "</font>";
}
//----------------------------
function print_r2($val){
        echo '<pre>';
        print_r($val);
        echo  '</pre>';
}
//----------------------------
function print_tree($data)
{
    // capture the output of print_r
    $out = print_r($data, true);

    // replace something like '[element] => <newline> (' with <a href="javascript:toggleDisplay('...');">...</a><div id="..." style="display: none;">
    $out = preg_replace('/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iUe',"'\\1<a href=\"javascript:toggleDisplay(\''.(\$id = substr(md5(rand().'\\0'), 0, 7)).'\');\">\\2</a><div id=\"'.\$id.'\" style=\"display: none;\">'", $out);

    // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>
    $out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);
	$out = "<pre>$out</pre>";
	
    // print the javascript function toggleDisplay() and then the transformed output
    echo '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "block") ? "none" : "block"; }</script>'."\n$out";
}
//----------------------------
function displayArray($arrayname,$tab="&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp",$indent=0){
 $curtab ="";
 $returnvalues = "";
 while(list($key, $value) = each($arrayname)) {
  for($i=0; $i<$indent; $i++) {
   $curtab .= $tab;
   }
  if (is_array($value)) {
   $returnvalues .= "$curtab$key : Array: <br />$curtab{<br />\n";
   $returnvalues .= displayArray($value,$tab,$indent+1)."$curtab}<br />\n";
   }
  else $returnvalues .= "$curtab$key => $value<br />\n";
  $curtab = NULL;
  }
 return $returnvalues;
}

//-------------------------------------------------------------------------------------------------------


?>