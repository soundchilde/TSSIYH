<?
//TEST url: http://thatsongstuckinyourhead.com/index.php?showdebug=true		&showarchive=1
//$homesite = "thatsongstuckinyourhead.com" (specified in fbinit)
//header("Refresh: 3600;");
error_reporting(~E_ALL & ~E_NOTICE);
set_time_limit (0);

//========================================================================================
//App link: http://apps.facebook.com/tssiyh_indexer/
//Canvas URL: http://soundchilde.com/TSSIYH/
//App Profile: http://www.facebook.com/apps/application.php?id=194324133937765
$version = "3.0";

$formlink="index.php";
$_DATA=$_POST;
/*if(!$fb && !$accessToken)*/ include_once "fbgraphinit.php";
if($_REQUEST['showdebug']==true){
	$extradebug=false;
	$_DATA=$_REQUEST;
	$debugappend="?showdebug=true";
	//$formlink.= $debugappend;
}

$searchterm="";
if(isset($_DATA['searchbox'])) $searchterm=trim($_DATA['searchbox']);

$strictSelected="";
if($_DATA['strict']=="on"){
	$strictSelected='checked="checked"';
	$strictSearch=true;
}

//========================================================================================
function StopWatchStart()
{
  $time = microtime();
  $time = explode(" ", $time);
  $time = $time[1] + $time[0];
  return $time;
}
$stopwatchstart = StopWatchStart();

function StopWatchEnd($start)
{
  $time = microtime();
  $time = explode(" ", $time);
  $time = $time[1] + $time[0];
  $finish = $time;
  $totaltime = ($finish - $start);
  return $totaltime;
}

function unstrip($serialString){
	if(get_magic_quotes_gpc()) return stripslashes($serialString);
    else return $serialString;
}

function CreateWallArrayFromDB($mainsql,$db){
	$wall=array();
	$res = @mysql_query($mainsql,$db);
	for($i=0;$i<@mysql_num_rows($res);$i++){
		$row = @mysql_fetch_array($res);
		$wall[$i]['post_id'] = $row['MainPostID']; 
		$wall[$i]['permalink'] = $row['PostLink']; 
		$wall[$i]['created_time'] = $row['CreatedDate']; 
		$wall[$i]['updated_time'] = $row['UpdatedDate'];
		$wall[$i]['actor_id']=$row['PostedBy']; 
		$wall[$i]['attachment'] = unserialize(unstrip($row['AttachmentArray'])); //unstrip = approx reverse of mysql_real_escape_string	
		$wall[$i]['attachment']['name']= unstrip($row['SongName']);
		$wall[$i]['attachment']['href'] = unstrip($row['URL']);
		$wall[$i]['likes'] = unserialize(unstrip($row['LikesArray']));
		$wall[$i]['likes']['count']=$row['Likes']; 
		$wall[$i]['comments']['count']=$row['NumOfSubComments']; 
		$wall[$i]['message'] = unstrip($row['Message']); 
		$wall[$i]['comments'] = unserialize(unstrip($row['SubCommentArray']));
	}
	return $wall;
}


function CleanUpVideoHref($videolink){
//Samples of Facebook embedded youtube links to be cleaned:
//http://l.facebook.com/l.php?u=http%3A%2F%2Fyoutu.be%2FnWZMPxFdc1A&h=2AQH35hPm&s=1
//https://api-read.facebook.com/l.php?u=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DbKiUwQRtHQI&h=UAQFGwhys&s=1

  if(stripos($videolink,'.facebook.com/l.php?u=')!==false && stripos($videolink,'youtu')!==false){
	if(stripos($videolink,'https://api-read.facebook.com/')!==false)
		$videolink=str_replace('https://api-read.facebook.com/l.php?u=', '', $videolink);
	else
		$videolink=str_replace('http://l.facebook.com/l.php?u=', '', $videolink);
  }
  return(urldecode($videolink)); // see if urldecode is required?
}

//==============================================================================================================
ob_start(); //for output buffer

if($extradebug) print_r($_DATA);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml"> 
<head>
<title>Indexer for "That Song Stuck in your Head" v<?=$version;?></title>
<meta property="og:title" content="ThatSongStuckInYourHead" />
<meta property="og:type" content="website" />
<meta property="og:url" content="http://<?=$homesite;?>" />
<meta property="og:image" content="" />
<meta property="og:site_name" content="Indexer for That Song Stuck in your Head" />
<meta property="fb:admins" content="1661979403" />

<script src="http://static.ak.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
<!--<script type="text/javascript" src="http://www.ajaxdaddy.com/media/demos/play/1/table-sort/table-sort/javascript/tablesort.js"></script>-->
<link href="TSSIYH%20Indexer_files/demo.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="LightCyan">
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '194324133937765',
      xfbml      : true,
      version    : 'v2.6'
    });
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>
<center><h1><b><u>UNDER CONSTRUCTION!</u></b></h1><br><br>
<h2><font face="Comic Sans MS"><b><u><a href="http://<?=$homesite;?>/">Search / Indexer v<?=$version;?> for "That Song Stuck in your Head"</a></u></b></font>
</h2>
<?
//--------------------------------------------------------------------------------------------------------
$group_pic="http://soundchilde.com/img/bass_clef_heart0.jpg";
if(isset($TSSIYH)){
	d($TSSIYH['name'],"Group Name");	//Retrieving details from the Group
	$group_pic = $TSSIYH['cover']['source']; //['icon'] doesnt look good
	$Creator = $TSSIYH['owner'];
	$Admins = array('1661979403' /*Soundchilde*/, '886695726' /*Varghese*/, '1020290384' /*Prashanth*/, '592096254' /*RajKiran*/);
	$isAdmin=false;
	if($myId==$Creator || @in_array($myId,$Admins)){
		$isAdmin=true;
		$designation = "Admin ";
		if($_REQUEST['PostToGroup']=="true") $post2group="&PostToGroup=523844714333956";
		//if(!$debug) echo '<iframe src="http://'.$homesite.'/backup_TSSIYH.php?updatelatest=true'.$post2group.'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:0px; height:0px;"></iframe>';
	} else
		$debug=false; //switch off Debug if NOT an Admin
	echo "<h4><b>Welcome $designation$myName! :-) </b></h4>";
} //end of condition if group is successfully found
?>
<br><a href="https://www.facebook.com/groups/tssiyh"><img src="<?=$group_pic;?>" alt="ThatSongStuckInYourHead" height="75" width="100"></a>
<!--&nbsp;
<iframe src="//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2Fgroups%2F155148594536905&amp;send=false&amp;layout=standard&amp;width=100&amp;show_faces=true&amp;action=like&amp;colorscheme=light&amp;font&amp;height=75&amp;appId=194324133937765" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:175px; height:75px;" allowTransparency="true"></iframe>
-->
<?
//------------------------------------------- FORM STARTS HERE --------------------------------------------
?>
</center>
<br>
<form name="SearchForm" action="<?echo $formlink;?>" method="post">
<center>
	<input type="checkbox" name="strict" value="on" <?=$strictSelected;?> />Strict Search
	&nbsp;
	<input name="searchbox" type="text" value="<?echo htmlspecialchars($searchterm, ENT_QUOTES);?>" style='width: 150px'/></b></i></div>
	&nbsp;&nbsp;
	<input name="search" type="submit" value="Search" style='height: 25px; width: 100px'/>
	&nbsp;&nbsp;
	<input name="showrecent" type="submit" value="Show most recent posts from FB" style='height: 25px; width: 200px'/>
	&nbsp;&nbsp;
	<?if($isAdmin) echo'<input name="showarchive" type="submit" value="Show full Group Archive (Song List)" style="height: 25px; width: 250px"/>'; ?>
</center>
</form>

<?
$dateOffset = strtotime('tomorrow');	//strtotime('2100-1-1');
$prevdateOffset = strtotime("-7 days",$dateOffset);
$extracondition="";

$limit='500';
$maxlimit = 1;  //default loadup straight from DB or even from FB 
	
 if(isset($TSSIYH) && isset($_DATA['showrecent'])){ //to extract posts directly from FB group
	
	$tssiyhFeed = getFBobject("/$gid/feed/?fields=post_id,permalink,created_time,updated_time,actor_id,message,attachment,likes,comments WHERE ". $extracondition." AND updated_time>=$prevdateOffset, limit=$limit");
	$wall = $tssiyhFeed['data'];
	
 } else { //otherwise, grab posts from DB by default
	if(isset($_DATA['showarchive'])) $maxlimit = 1000;
	
	$LoadFromDB=true;
	include_once "dbconnect.php";
	
	$searchterm=mysql_real_escape_string(strtolower($searchterm));
	if(isset($_DATA['search']) && $searchterm!=""){
		if($strictSearch) 
			$extracondition="AND `AttachmentArray` LIKE '%$searchterm%' OR `SongName` LIKE '%$searchterm%' OR `URL` LIKE '%$searchterm%' OR `Message` LIKE '%$searchterm%' OR `SubCommentArray` LIKE '%$searchterm%'";	
		else { 
		  // Use FULLTEXT binary search for best flexible search. For eg., '+Massive +Attack' or '+Massive* +Attack*'
			$flexiSearchterm= "+" . str_replace(" ", " +", $searchterm); 
			$extracondition="AND MATCH(`AttachmentArray`, `SongName`, `Message`, `SubCommentArray`) AGAINST('$flexiSearchterm' IN BOOLEAN MODE) OR `URL` LIKE '%$searchterm%'" ;
		}
		
		$mainsql = "SELECT * FROM `ThatSongStuckInYourHead` WHERE `UpdatedDate`!='0' $extracondition ORDER BY `UpdatedDate` DESC LIMIT $limit";
		
	} else { //default call without searchterm

		$mainsql = "SELECT * FROM `ThatSongStuckInYourHead` WHERE `UpdatedDate`!='0' AND `UpdatedDate`>='$prevdateOffset' ORDER BY `UpdatedDate` DESC LIMIT $limit";
	}
	/*`MainPostID`, `SubPostID`, `PostLink`, `CreatedDate`, `UpdatedDate`, `PostedBy`, `AttachmentArray`, 
	`SongName`, `URL`, `Likes`, `LikesArray`, `NumOfSubComments`, `Message`, `SubCommentArray`*/
	$wall= CreateWallArrayFromDB($mainsql,$db);
	
}
  if($debug) d($mainsql, "Wall Count=".count($wall)."<br>Wall SQL"); 

  $dateOffset = $prevdateOffset;

  echo '
	<br>
	<form name="TSSIYHplaylist" action="Playlister/playlister2.php'.$debugappend.'" target="_blank" method="post"> 
	&nbsp;&nbsp;<input name="Add2Playlist" type="submit" value="Add Selected Songs to Youtube Playlist" style="height: 25px; width: 250px"/><br>
	&nbsp;&nbsp;<br>
	<table id="SongsTable" class="sortable-onload-0 rowstyle-alt no-arrow" border="1" cellspacing="1" cellpadding="0">
	<thead>
		<tr>
			<th width="50" style="-moz-user-select: none;" class="sortable-numeric fd-column-0"><center>#</center></th>
			<th width="50" style="-moz-user-select: none;" class="sortable-text fd-column-1"><center>Post Link</center></th>
			<th width="50" style="-moz-user-select: none;" class="sortable-text fd-column-2"><center>Created Date</center></th>
			<!--<th width="75" style="-moz-user-select: none;" class="sortable-text fd-column-3"><center>Posted By</center></th>-->
			<th width="50" style="-moz-user-select: none;" class="sortable-text fd-column-4"><center>Add to Playlist</center></th>
			<th width="100" style="-moz-user-select: none;" class="sortable-text fd-column-5"><center>Song Name & Link</center></th>
			<th width="50" style="-moz-user-select: none;" class="sortable-numeric fd-column-6"><center># of Likes</center></th>
			<th width="50" style="-moz-user-select: none;" class="sortable-numeric fd-column-7"><center># of Sub- Comms</center></th>
			<th width="100" style="-moz-user-select: none;" class="sortable-keep fd-column-8">Post</th>
		</tr>
	</thead>
	';
	flush();
	
	echo '<tbody>';
	$counter=0;
	$totalposts=0;
	$totalcomments=0;
	$PostIDs=array();
	$lastdate=strtotime("now");
	
	if($debug) $maxlimit = 1;
	while($dateOffset>strtotime('2010-12-31')  && $counter<$maxlimit){
		$subDateOffset=$dateOffset;
		$prevdateOffset = $dateOffset;
		
		if(isset($_DATA['showrecent'])) d(count($wall), "Iteration=$counter => FromDate: ".date('M d Y', $dateOffset)." -> Num. of Posts during this week");
		
		for($i=0;$i<count($wall);$i++){
		  $totalposts++;
		  $PostIDs[]=$wall[$i]['post_id'];
		  //FOR Facebook Names -> <fb:name uid="'.$wall[$i]['actor_id'].'"></fb:name>
		  
		  $videolink=CleanUpVideoHref($wall[$i]['attachment']['href']);
		  if($wall[$i]['attachment']['name']=="" || stripos($videolink,"youtu")===false) $checkbox = "";
		  else $checkbox = '<input type="checkbox" id="chk'.$totalposts.'" name="'.urlencode($videolink).'" />'; 

		  //<!--width="250"-->
		  //<td><center><a href="http://www.facebook.com/profile.php?id='.$wall[$i]['actor_id'].'"><fb:name uid="'.$wall[$i]['actor_id'].'"></fb:name> ...</a></center></td>
		  echo '<tr>
			 <td><center>'.$totalposts.'</center></td>
			 <td><center><a href="'.$wall[$i]['permalink'].'">'.str_replace($gid."_","",$wall[$i]['post_id']).'</a></center></td>
			  <td><center>'.date("Y-m-d H:i:s", $wall[$i]['created_time']).'</center></td>
			  <td><center>'.$checkbox.'</center></td>
			  <td><center><a href="'.$videolink.'">'.substr($wall[$i]['attachment']['name'],0,30).'</a></center></td>
			  <td><center>'.$wall[$i]['likes']['count'].'</center></td>
			  <td><center>'.$wall[$i]['comments']['count'].'</center></td>
			  <td align="left">'.$wall[$i]['message'].'</td>
		  </tr>';	
		  if($lastdate>$wall[$i]['updated_time']) $lastdate=$wall[$i]['updated_time']; //previously was created_time
		  
			//FOR SUB-COMMENTS
			//http://www.facebook.com/groups/155148594536905/?view=permalink&id=
			$comments = $wall[$i]['comments'];
			for($j=0;$j<$comments['count'];$j++){
			  $subcomment = $wall[$i]['comments']['comment_list'][$j];
			  $searchcomment="http://";
			  if(isset($_DATA['search'])) $searchcomment=$searchterm;
			  if($subcomment['text']!="" && stristr($subcomment['text'],$searchcomment)!==false){
				$PostIDs[]=$subcomment['id'];
				$totalcomments++;
				$pos1 = stripos($subcomment['text'],"http://");
				$pos2 = stripos($subcomment['text']," ", $pos1+7);
				if($pos2===false) $pos2= strlen($subcomment['text'])-1;
				if($pos1!==false && $pos2!==false) $extractedlink = substr($subcomment['text'], $pos1, $pos2-$pos1+1);
				else $extractedlink = "";
				
				$extractedlink=CleanUpVideoHref($extractedlink);
				if($extractedlink=="" || stripos($extractedlink,"youtu")===false) $subcheckbox = "";
				else $subcheckbox = '<input type="checkbox" id="chk'.$subcomment['id'].'" name="'.urlencode($extractedlink).'" />'; 
				
				//<td><center><a href="http://www.facebook.com/profile.php?id='.$subcomment['fromid'].'"><fb:name uid="'.$subcomment['fromid'].'"></fb:name> ...</a></center></td>
				echo '<tr>
					 <td><center>Sub-Comment #'.($j+1).'</center></td>
					 <td><center><a href="'.$wall[$i]['permalink'].'">'.str_replace($wall[$i]['post_id']."_","",$subcomment['id']).'</a></center></td>
					  <td><center>'.date("Y-m-d H:i:s", $subcomment['time']).'</center></td>
					  <td><center>'.$subcheckbox.'</center></td>
					  <td><center><a href="'.$extractedlink.'">'.substr($extractedlink,0,30).'</a></center></td>
					  <td><center>'.$subcomment['likes'].'</center></td>
					  <td><center></center></td>
					  <td align="left">'.$subcomment['text'].'</td>
				</tr>';
			  }
			  $subDateOffset = ($subDateOffset>$subcomment['time'] && $subcomment['time']>0) ? $subcomment['time'] : $subDateOffset;
		    }
		  
		  flush();
		}
	  
	  $previousWeek = strtotime("-7 days",$dateOffset);
	  $dateOffset = $previousWeek;

	  if($debug) echo "PrevDateOffset=".date('M d Y, H:i', $prevdateOffset)."($prevdateOffset), LastUpdatedDate = ".date('M d Y, H:i', $lastdate)."($lastdate), PreviousWeek = ".date('M d Y', $previousWeek)."($previousWeek), SubOffsetDate = ".date('M d Y, H:i', $subDateOffset)."($subDateOffset)<br>";
	  
	  if(isset($_DATA['showarchive'])){
		$loopsql = "SELECT * FROM `ThatSongStuckInYourHead` WHERE `UpdatedDate`!='0' AND `UpdatedDate`<'$prevdateOffset' AND `UpdatedDate`>='$dateOffset' ORDER BY `UpdatedDate` DESC LIMIT $limit";
		$wall= CreateWallArrayFromDB($loopsql,$db);
		//$wall = $facebook->api(array('method'=>'fql.query', 'query'=>"SELECT post_id, permalink, created_time, updated_time, actor_id, message, attachment, likes, comments FROM stream WHERE source_id=$gid ".$extracondition." AND updated_time<$prevdateOffset  AND updated_time>=$dateOffset ORDER BY updated_time DESC LIMIT 0,$limit"));
	  }
	  
	  $counter++;
	  flush();
	  //sleep(2);
	  if($prevdateOffset<strtotime("2011-1-7")) break;
	}
	d($counter, "<br>Total Iterations");
	
	$strTotalPosts = "Total Num of retrievable posts";
	if(isset($_DATA['search'])) $strTotalPosts = "Num of Posts found";
	d($totalposts, $strTotalPosts);  
	
	d($totalcomments.'<br>', "Num of Sub-Comments found");  
	if($totalposts+$totalcomments<1) echo "<br><center><h2><b>No relevant posts or comments were found! Please try another search term.</b></h2></center>"	;	
	
	echo '	</tbody>
	</table>
	<br>&nbsp;&nbsp;
	<br><input name="Add2Playlist" type="submit" value="Add Selected Songs to Youtube Playlist" style="height: 25px; width: 250px"/><br>
	</form>';
  //} //this closes the if(count($wall)<1)  condition
 //} end of condition if user clicks Show group Archive OR Search
 
	$timediff=StopWatchEnd($stopwatchstart);
	echo "<br><br>Query(s) executed in ".sprintf("%.4f secs", $timediff);

ob_end_flush(); 

?>

<p><br><center>
<iframe src="http://www.youtube.com/results?q=<?=str_replace(" ","+",$searchterm);?>" scrolling="yes" frameborder="0" style="width:960px; height:350px;"></iframe> <!--border:none; overflow:hidden;-->
</center></p>

<!--
</fb:wall>
</fb:fbml>
</script>
</fb:serverFbml>-->
<script type="text/javascript">
  window.onload = function() {
    FB_RequireFeatures(["XFBML"], function() {
      FB.init('8152a4b299d4911965f21dacc6678f41', 'xd_receiver.htm');   
    });   
  };  
</script>
</body>
</html>

