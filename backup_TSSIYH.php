<?
/*test: http://soundchilde.com/TSSIYH/backup_TSSIYH.php?appwrite=true&showdebug=true&PostToGroup=true
//use with prevadded & date: http://soundchilde.com/TSSIYH/backup_TSSIYH.php?startFrom=2012-01-05&prevadded=776&showdebug=true&PostToGroup=true
http://soundchilde.com/TSSIYH/backup_TSSIYH.php?updatelatest=true&PostToGroup=523844714333956
*/
set_time_limit (0);

$formlink="backup_TSSIYH.php";
include_once "fbinit.php";
if($_REQUEST['showdebug']=="true"){
	error_reporting(E_ALL);
	//$formlink.="?showdebug=true";
}

$showmembers=false;
if($_REQUEST['showmembers']=="true") $showmembers=true;

@ob_start();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml"> 
<head>
<title>Backup Cron "That Song Stuck in your Head"</title>
<script src="http://static.ak.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
<link href="TSSIYH%20Indexer_files/demo.css" rel="stylesheet" type="text/css">

</head>

<body bgcolor="LightCyan">
<center><h1><font face="Comic Sans MS"><b><u><a href="http://soundchilde.com/TSSIYH/backup_TSSIYH.php">Backup script for "That Song Stuck in your Head"</a></u></b></font></h1>
<br><br><a href="<?echo $logoutUrl;?>">Logout</a></center>
<br>
<?

$gid='155148594536905';
$groups = $facebook->api(array(
    'method' => 'fql.query',
    'query' => "select gid, name, description, group_type, creator, update_time from group where gid = $gid;"
));
if($debug) {d($groups, "FULL GROUP OBJECT"); echo "<br>";}

$numoferrors=array("AddNewPost"=>0,"Add-UpdateSubCommentArray"=>0,"UpdatePost"=>0,"Update-UpdateSubCommentArray"=>0
			,"AddNewSubComment"=>0,"UpdateExistingSubComment"=>0);
?>
<br><br>

<?
//echo '<b>Current User: <fb:name uid="'.$loggedinuser.'" useyou="false"></fb:name><br><br>';
			
if(isset($groups[0])){
	d(count($groups), "Group found");
	$TSSIYH = $groups[0];
	d($TSSIYH['name'],"Group Name");	//Retrieving details from the Group
	
	include_once "dbconnect.php";

	
  //Retrieving current Members from the group
   $title="Info";
   if($showmembers){
	$title="Table";
	echo "<h4><b><u>Members $title</u></b></h4>";
	
	 $Members = $facebook->api(array(
    'method' => 'fql.query',
    'query' => "select uid, administrator from group_member where gid = $gid;"
	));
	 $MembersfromFB=array();
	 foreach($Members as $member) $MembersfromFB[]=$member['uid'];
	
	 if(count($Members)>0){
		$sql = 'SELECT `memberID`, `admin` FROM `Members` ORDER BY `memberID`';
		$res = mysql_query($sql,$db);
		$MembersfromDB = array();
		$MembersMissing=0;
		$sqlLeftGroup="UPDATE Members SET `leftGroup`=1 WHERE ";
		for($i=0;$i<mysql_num_rows($res);$i++){
			$row = mysql_fetch_array($res);
			$MembersfromDB[] = $row['memberID'];
			if(in_array($row['memberID'], $MembersfromFB)===false){
				$MembersMissing++;
				$OR="OR ";
				if($MembersMissing==1) $OR="";
				$sqlLeftGroup.= $OR."`memberID`='{$row['memberID']}' ";
			}
		}
		if($MembersMissing>0){
			$leftres = mysql_query($sqlLeftGroup,$db);
			echo $MembersMissing." Members have left<br><br>";	//: ".$sqlLeftGroup."
		}
		
		$membersAdded=0;
		foreach($Members as $member){
			if($member['administrator']!="1" || $member['administrator']=="") $member['administrator']="0";
			if($showmembers) echo "{$member['uid']},<fb:name uid='{$member['uid']}'>,</fb:name>,<fb:pronoun uid='{$member['uid']}' useyou='false' objective='true' usethey='false'></fb:pronoun>,{$member['administrator']}";
			
			if(in_array($member['uid'], $MembersfromDB)===false){
				$addsql="INSERT INTO `Members` (`memberID`, `admin`)  
				VALUES('{$member['uid']}', '{$member['administrator']}')";
				$res = @mysql_query($addsql,$db);
				if(!$res) echo "Could not add to Members: sql-> {$addsql}";
				else{
					$membersAdded++;
					if($showmembers) echo ",Newly Added";
				}
			}
			if($showmembers) echo "<br>";
		}
		echo "<br><h4>$membersAdded Members have been Added</h4><br>";
	}
   }
	echo "<br><h1><b>Commencing Backup procedure...</b></h1><br><br>";
	FullFlush();
	
	
	$sql1 = 'SELECT `MainPostID`, `SubPostID`, `CreatedDate`, `UpdatedDate`, `Likes`, `LikesArray`, `NumOfSubComments`, `SubCommentArray` FROM `ThatSongStuckInYourHead` ORDER BY `UpdatedDate` DESC';
	$res1 = mysql_query($sql1,$db) or die("Bad SQL1");
	$DataFromDB = array();
	$PrevNumOfPostIDs = mysql_num_rows($res1);
	for($i=0;$i<$PrevNumOfPostIDs ;$i++){
		$row1 = mysql_fetch_array($res1);
		if($row1['MainPostID']!=""){
		  if($row1['SubPostID']=="-"){
			$DataFromDB[$row1['MainPostID']]['UpdatedDate'] = $row1['UpdatedDate'];
			$DataFromDB[$row1['MainPostID']]['Likes'] = $row1['Likes'];
			$DataFromDB[$row1['MainPostID']]['LikesArray'] = $row1['LikesArray'];
			$DataFromDB[$row1['MainPostID']]['NumOfSubComments'] = $row1['NumOfSubComments'];
			$DataFromDB[$row1['MainPostID']]['SubCommentArray'] = $row1['SubCommentArray'];
		  } else {
			$DataFromDB[$row1['SubPostID']]['Date'] = $row1['CreatedDate'];
			$DataFromDB[$row1['SubPostID']]['Likes'] = $row1['Likes'];
			//$DataFromDB[$row1['SubPostID']]['Text'] = $row1['Message'];
		  }
		}
	}
	if($debug){
		d($PrevNumOfPostIDs , "Num of PostID Rows"); 
		//d($DataFromDB, "Post IDs & UpdatedTimes"); 
	}
	

	$limit='500'; 
	if($_REQUEST['updatelatest']=="true") $maxlimit=1;
	else $maxlimit=1000;
	$extracondition="";
	if(!isset($_REQUEST["startFrom"])){
		$dateOffset = strtotime('tomorrow');	//strtotime('2100-1-1');
		$prevdateOffset = strtotime("-7 days",$dateOffset);
		$mainsql = "SELECT post_id, permalink, created_time, updated_time, actor_id, message, attachment, likes, comments,action_links, privacy, tagged_ids, message_tags, description_tags FROM stream WHERE source_id=$gid ".$extracondition." AND updated_time>=$prevdateOffset LIMIT 0,$limit";
	} else { //this is if startFrom date is provided in dd-mm-yyyy format
		$dateOffset = strtotime($_REQUEST["startFrom"]);
		$prevdateOffset = strtotime("-7 days",$dateOffset);
		$mainsql = "SELECT post_id, permalink, created_time, updated_time, actor_id, message, attachment, likes, comments,action_links, privacy, tagged_ids, message_tags, description_tags FROM stream WHERE source_id=$gid ".$extracondition." AND updated_time<=$dateOffset  AND updated_time>=$prevdateOffset LIMIT 0,$limit";
	}
	
	$wall = $facebook->api(array('method'=>'fql.query', 'query'=>$mainsql));	
	//d(displayArray($wall), "Wall Count=".count($wall)."<br>Wall array"); //if($debug) 
	
	//if(count($wall)<$limit) d(count($wall), "Initial query rows returned");
	$dateOffset = $prevdateOffset;
	
	//if($debug) 
	echo '
	<br>
	<table id="SongsTable" border="1" cellspacing="1" cellpadding="0">
	<thead>
		<tr>
			<th><center>#</center></th>
			<th><center>Post Link</center></th>
			<th>Backup<br>Status</th>
			<th><center>Created Date</center></th>
			<th><center>Updated On</center></th>
			<th ><center>Posted By</center></th>
			<th><center>Song Name & Link</center></th>
			<th><center># of Likes</center></th>
			<th width="70"><center># of Sub- Comments</center></th>
			<th width="100">Post</th>
		</tr>
	</thead>
	';
	FullFlush();
	
	if($debug) echo '<tbody>';
	$counter=0;
	$totalposts=0;
	$totalcomments=0;
	$totalAdded=0;
	$totalUpdated=0;
	$subsAdded=0;
	$PostIDs=array();
	$lastdate=strtotime("now");
	
	while($dateOffset>strtotime('2011-1-1') && $counter<$maxlimit){	// //count($wall)>0 //$lastdate=strtotime("now");
		$subDateOffset=$dateOffset;
		$prevdateOffset = $dateOffset;
		
		for($i=0;$i<count($wall);$i++){
		 //if(!in_array($wall[$i]['post_id'],$PostIDs)){
		  $totalposts++;
		  $PostIDs[]=$wall[$i]['post_id'];
		  //FOR Facebook Names -> <fb:name uid="'.$wall[$i]['actor_id'].'"></fb:name>
		  
		  $MainPostID = $wall[$i]['post_id'];  // and $SubPostID
		  $PostLink= $wall[$i]['permalink'];
		  $CreatedDate = $wall[$i]['created_time'];
		  $UpdatedDate = $wall[$i]['updated_time'];
		  $PostedBy = $wall[$i]['actor_id'];
		  $AttachmentArray = mysql_real_escape_string(serialize($wall[$i]['attachment']));	//print_r($wall[$i]['attachment'], true));
		  $SongName = mysql_real_escape_string($wall[$i]['attachment']['name']);
		  $URL = mysql_real_escape_string($wall[$i]['attachment']['href']);
		  $LikesArray = mysql_real_escape_string(serialize($wall[$i]['likes']));
		  $Likes = $wall[$i]['likes']['count'];
		  $NumOfSubComments = $wall[$i]['comments']['count'];
		  $Message = mysql_real_escape_string($wall[$i]['message']);
		  $SubCommentArray = mysql_real_escape_string(serialize($wall[$i]['comments'])); //print_r($wall[$i]['comments'], true);	//mysql_real_escape_string(
		  $ActionLinksArray = mysql_real_escape_string(serialize($wall[$i]['action_links']));
		  $PrivacyArray = mysql_real_escape_string(serialize($wall[$i]['privacy']));
		  $TaggedIDsArray = mysql_real_escape_string(serialize($wall[$i]['tagged_ids']));
		  $MessageTagsArray = mysql_real_escape_string(serialize($wall[$i]['message_tags']));
		  $DescriptionTagsArray = mysql_real_escape_string(serialize($wall[$i]['description_tags']));
		  $BackupStatus = ""; 

		  if($debug) echo "<br><br>PostID exists in DB=".$NoYes[array_key_exists($MainPostID, $DataFromDB)];
		  echo "<br>$totalposts -> Post# {$MainPostID} Status: ";
		  FullFlush();
		  if(!in_array($MainPostID, array_keys($DataFromDB))){
			$BackupStatus = "Pending";
			
			$Addsql = "INSERT INTO `ThatSongStuckInYourHead` 
			         (`MainPostID`, `SubPostID`, `PostLink`, `CreatedDate`, `UpdatedDate`, `PostedBy`, `AttachmentArray`, `SongName`, `URL`, `Likes`, `LikesArray`, `NumOfSubComments`, `Message`, `ActionLinksArray`, `PrivacyArray`, `TaggedIDsArray`, `MessageTagsArray`, `DescriptionTagsArray`)  
			 VALUES('{$MainPostID}', '-', '{$PostLink}', '{$CreatedDate}', '{$UpdatedDate}', '{$PostedBy}', '{$AttachmentArray}', '{$SongName}', '{$URL}', '{$Likes}', '{$LikesArray}', '{$NumOfSubComments}', '{$Message}', '{$ActionLinksArray}', '{$PrivacyArray}', '{$TaggedIDsArray}', '{$MessageTagsArray}', '{$DescriptionTagsArray}')"; //, `SubCommentArray` -> , '{$SubCommentArray}'
			//if($debug) echo "<br>ADD SQL -> ".$Addsql."<br><br>";
			$res = @mysql_query($Addsql,$db);
			if(!$res){
				$numoferrors["AddNewPost"]++;
				echo "Bad Add SQL for Post# {$MainPostID} -> {$Addsql}<br>";
			} else{
				$totalAdded++;
				$BackupStatus="Added";
				echo "Added successfully<br>";
				$updatesql = "UPDATE `ThatSongStuckInYourHead` SET `SubCommentArray` = '{$SubCommentArray}'  WHERE `MainPostID` = '{$MainPostID}' AND `SubPostID` = '-'";
				if(!mysql_query($updatesql,$db)){
					$numoferrors["Add-UpdateSubCommentArray"]++;
					echo("Failed to update SubComment Array for Post# {$MainPostID} -> {$updatesql}<br>");
				} else {
					//echo "ADD: SubComment updated successfully<br>";
					$BackupStatus="Added&Updated";
				}
			}
			FullFlush();
			
		  } else {
			$BackupStatus = "Present";
			echo $BackupStatus;
				
				if($debug){
					echo "<br>Comparisons -> UpdatedDate=$UpdatedDate(fromDB={$DataFromDB[$MainPostID]['UpdatedDate']}), 
						Likes=$Likes(fromDB={$DataFromDB[$MainPostID]['Likes']}), 
						NumOfSubComments=$NumOfSubComments(fromDB={$DataFromDB[$MainPostID]['NumOfSubComments']}), 
						<br>Comparison of LikesArray: Equal=>".$NoYes[($LikesArray==mysql_real_escape_string($DataFromDB[$MainPostID]['LikesArray']))].",
						<br>Comparison of SubCommentArray: Equal=>".$NoYes[($SubCommentArray==mysql_real_escape_string($DataFromDB[$MainPostID]['SubCommentArray']))]."
						<br>";
						/*<br>LikesArray=$LikesArray ,
						<br>LikesArrayfromDB=".mysql_real_escape_string($DataFromDB[$MainPostID]['LikesArray'])." ,
						<br>SubCommentArray=$SubCommentArray ,
						<br>SubCommentArrayfromDB=".mysql_real_escape_string($DataFromDB[$MainPostID]['SubCommentArray']);*/
				}
				if($UpdatedDate!=$DataFromDB[$MainPostID]['UpdatedDate'] || $LikesArray!=mysql_real_escape_string($DataFromDB[$MainPostID]['LikesArray']) || $SubCommentArray!=mysql_real_escape_string($DataFromDB[$MainPostID]['SubCommentArray'])){
					$Updatesql = "UPDATE `ThatSongStuckInYourHead` SET `UpdatedDate` = '{$UpdatedDate}', `Likes` = '{$Likes}', `LikesArray` = '{$LikesArray}', `NumOfSubComments` = '{$NumOfSubComments}', `ActionLinksArray` = '{$ActionLinksArray}', `PrivacyArray` = '{$PrivacyArray}', `TaggedIDsArray` = '{$TaggedIDsArray}', `MessageTagsArray` = '{$MessageTagsArray}', `DescriptionTagsArray` = '{$DescriptionTagsArray}'  WHERE `MainPostID` = '{$MainPostID}' AND `SubPostID` = '-'"; 
					
					//if($debug) echo "<br><br>UPDATE SQL -> ".$Updatesql."<br><br>";
					$res = mysql_query($Updatesql,$db);
					if(!$res){
						$numoferrors["UpdatePost"]++;
						echo(" -> Bad Update SQL for Post# {$MainPostID} -> {$Updatesql}<br><br>");
					} else{
						$totalUpdated++;
						$BackupStatus = "Updated";
						echo " -> Updated successfully<br>";
					}
					
					//old comparison (wrong): mysql_real_escape_string(serialize($SubCommentArray))!=$DataFromDB[$MainPostID]['SubCommentArray']
					if($SubCommentArray!=mysql_real_escape_string($DataFromDB[$MainPostID]['SubCommentArray'])){	//mysql_real_escape_string(
						$updatesql = "UPDATE `ThatSongStuckInYourHead` SET `SubCommentArray` = '{$SubCommentArray}' WHERE `MainPostID` = '{$MainPostID}' AND `SubPostID` = '-'";
						if(!@mysql_query($updatesql,$db)){
							$numoferrors["Update-UpdateSubCommentArray"]++;
							echo("Failed to update SubComment Array for Post# {$MainPostID} -> {$updatesql}<br>");
						} else {
							$BackupStatus = "Updated2";
							echo "UPDATE: SubComment Array updated successfully<br>";
						}
					}
				}
				FullFlush();
		  }
		  
		  echo '<tr>
			 <td><center>'.$totalposts.'</center></td>
			 <td><center><a href="'.$wall[$i]['permalink'].'">'.str_replace($gid."_","",$wall[$i]['post_id']).'</a></center></td>
			 <td><center>'.$BackupStatus.'</center></td>
			 <td><center>'.date("Y-m-d H:i:s", $wall[$i]['created_time']).'</center></td>
			 <td><center>'.date("Y-m-d H:i:s", $wall[$i]['updated_time']).'</center></td>
			  <td><center><fb:name uid="'.$wall[$i]['actor_id'].'"></fb:name></center></td>
			  <td width="250"><center><a href="'.$wall[$i]['attachment']['href'].'">'.$wall[$i]['attachment']['name'].'</a></center></td>
			  <td><center>'.$wall[$i]['likes']['count'].'</center></td>
			  <td><center>'.$wall[$i]['comments']['count'].'</center></td>
			  <td align="left">'.$wall[$i]['message'].'</td>
		  </tr>';	//if($debug) 
		  //if($lastdate>$wall[$i]['created_time']) $lastdate=$wall[$i]['created_time']; //previously was updated_time
		  if($lastdate>$wall[$i]['updated_time']) $lastdate=$wall[$i]['updated_time']; //previously was created_time
		  
			
			//FOR SUB-COMMENTS
			//http://www.facebook.com/groups/155148594536905/?view=permalink&id=
			$comments = $wall[$i]['comments'];
			//d($comments, "<br>Comment object");
			/* EXAMPLE OF SUB-COMMENT OBJECT:
			Array ( [can_remove] => 1 [can_post] => 1 [count] => 6 [comment_list] => Array ( [0] => 
			Array ( [fromid] => 1661979403 [time] => 1319786590 [text] => lol, knew you'd be the first one to like it :D [id] => 155148594536905_255734577811639_255734741144956 [likes] => 1 [user_likes] => ) [1] => Array ( [fromid] => 1020290384 [time] => 1319786609 [text] => :P [id] => 155148594536905_255734577811639_255734794478284 [likes] => 0 [user_likes] => ) [2] => Array ( [fromid] => 712445048 [time] => 1319787302 [text] => Aren't you brothers? [id] => 155148594536905_255734577811639_255736674478096 [likes] => 2 [user_likes] => 1 ) [3] => Array ( [fromid] => 1661979403 [time] => 1319787420 [text] => ROFL!!! Yeah, brothers in ROCK, but no, not related otherwise... LOL! I love this... possibly the best question of the year! :D [id] => 155148594536905_255734577811639_255736997811397 [likes] => 2 [user_likes] => ) [4] => Array ( [fromid] => 1020290384 [time] => 1319787669 [text] => xD [id] => 155148594536905_255734577811639_255737657811331 [likes] => 0 [user_likes] => ) [5] => Array ( [fromid] => 712445048 [time] => 1319789129 [text] => Brothers In Arms is good enough for me! Awaiting my award for best question posted in TSSIMH! [id] => 155148594536905_255734577811639_255742287810868 [likes] => 0 [user_likes] => ) ) ) 
			*/
			
			for($j=0;$j<$comments['count'];$j++){
			  $subcomment = $wall[$i]['comments']['comment_list'][$j];
			  $searchcomment="http://";
			  if(isset($_REQUEST['search'])) $searchcomment=strtolower($searchterm);
			  if($subcomment['text']!="" && stristr($subcomment['text'],$searchcomment)!==false){
				$PostIDs[]=$subcomment['id'];
				$totalcomments++;
				$pos1 = stripos($subcomment['text'],"http://");
				$pos2 = stripos($subcomment['text']," ", $pos1+7);
				if($pos2===false) $pos2= strlen($subcomment['text'])-1;
				if($pos1!==false && $pos2!==false) $extractedlink = substr($subcomment['text'], $pos1, $pos2-$pos1+1);
				else $extractedlink = "";
				
				$subid = $subcomment['id'];
				$time = $subcomment['time'];
			    $fromid = $subcomment['fromid'];
			    $url = mysql_real_escape_string($extractedlink);
			    $likes = $subcomment['likes'];
			    $text = mysql_real_escape_string($subcomment['text']);

				if(!in_array($subid, array_keys($DataFromDB))){ //SubPostIDs have already been added to DataFromDB
					$BackupStatus = "Sub-Pending";
					$Addsql = "INSERT INTO `ThatSongStuckInYourHead` 
							 (`MainPostID`, `SubPostID`, `PostLink`, `CreatedDate`, `UpdatedDate`, `PostedBy`, `URL`, `Likes`, `Message`)  
					 VALUES('{$MainPostID}', '{$subid}', '{$url}', '{$time}', '', '{$fromid}', '{$url}', '{$likes}', '{$text}')";
					if($debug) echo "<br>ADD SQL -> ".$Addsql."<br>";
					$res = @mysql_query($Addsql,$db);
					if(!$res) echo "Bad Add SQL for Sub-Comment# {$subid} -> {$Addsql}<br><br>";
					else{
						$subsAdded++;
						$BackupStatus = "Sub-Added";
						echo "Sub-Comment# {$subid} added successfully<br>";
					}
					FullFlush();
				} 
				
				echo '<tr>
					 <td><center>Sub-Comment #'.($j+1).'</center></td>
					 <td><center><a href="'.$wall[$i]['permalink'].'">'.str_replace($wall[$i]['post_id']."_","",$subcomment['id']).'</a></center></td>
					  <td><center>'.$BackupStatus.'</center></td>
					  <td><center>'.date("Y-m-d H:i:s", $subcomment['time']).'</center></td>
					  <td><center></center></td>
					  <td><center><fb:name uid="'.$subcomment['fromid'].'"></fb:name></center></td>
					  <td width="250"><center><a href="'.$extractedlink.'">'.$extractedlink.'</a></center></td>
					  <td><center>'.$subcomment['likes'].'</center></td>
					  <td><center></center></td>
					  <td align="left">'.$subcomment['text'].'</td>
				</tr>';
			  }
			  $subDateOffset = ($subDateOffset>$subcomment['time'] && $subcomment['time']>0) ? $subcomment['time'] : $subDateOffset;
		    }
		  echo "NewPosts Added=$totalAdded, Updated=$totalUpdated, Sub-comments Added=$subsAdded<br>";
		  FullFlush();
		 //$dateOffset = $dateOffset < $wall[$i]['updated_time'] ? $dateOffset : $wall[$i]['updated_time'];
		}
		
	  //if(count($wall)<=0) $dateOffset = strtotime("-30 days",$dateOffset);
	  $previousWeek = strtotime("-7 days",$dateOffset);
	  //$dateOffset = $subDateOffset>$previousWeek ? $subDateOffset : $previousWeek;
	  $dateOffset = $previousWeek;
	  if($debug) echo "PrevDateOffset=".date('M d Y, H:i', $prevdateOffset)."($prevdateOffset), LastUpdatedDate = ".date('M d Y, H:i', $lastdate)."($lastdate), PreviousWeek = ".date('M d Y', $previousWeek)."($previousWeek), SubOffsetDate = ".date('M d Y, H:i', $subDateOffset)."($subDateOffset)<br>";
	  
	  sleep(1);
	  $wall = $facebook->api(array('method'=>'fql.query', 'query'=>"SELECT post_id, permalink, created_time, updated_time, actor_id, message, attachment, likes, comments,action_links, privacy, tagged_ids, message_tags, description_tags FROM stream WHERE source_id=$gid ".$extracondition." AND updated_time<$prevdateOffset  AND updated_time>=$dateOffset ORDER BY updated_time DESC LIMIT 0,$limit"));
	  d(count($wall), "Wall count");
	  $counter++;
	  FullFlush();
	  //sleep(1);
	  if($prevdateOffset<strtotime("2011-1-7")) break;
	}
	d($counter, "<br><br><Br>Total Iterations");
	
	$strTotalPosts = "Total Num of retrievable posts";
	if(isset($_REQUEST['search'])) $strTotalPosts = "Num of Posts found";
	d($totalposts, $strTotalPosts);  
	
	d($totalcomments.'<br>', "Num of Sub-Comments found");  
	if($totalposts+$totalcomments<1) echo "<br><center><h2><b>No relevant posts or comments were found! Please try another search term.</b></h2></center>"	;	//d("Please try another search term.</h2></center>", "<br>No relevant posts or comments were found!");
	
	echo '	</tbody>	
	</table>'; //if($debug) 
  //} //this closes the if(count($wall)<1)  condition
  
 
 d($numoferrors, "<br>Num Of Errors");

	$res1 = mysql_query('SELECT `MainPostID` FROM `ThatSongStuckInYourHead` ORDER BY `UpdatedDate` DESC',$db) or die("Bad SQL1");
	$CurrentNumOfPostIDs = mysql_num_rows($res1);
	$prevadded = $_REQUEST['prevadded'];
	$numofadded = $CurrentNumOfPostIDs - $PrevNumOfPostIDs + $prevadded;
	$stats = "Total Posts in archive = $CurrentNumOfPostIDs, Num of new Posts added = $numofadded";
	$strMsg = "Automated Message from Backup Bot: Full Backup of TSSIYH completed @ ".date("d M Y, H:i:s")." (server time)
	\r\n $stats";
	echo '<br><br>'.$strMsg.'<br><br>';
	
	$PostToGroup=trim($_REQUEST['PostToGroup']);
	if($PostToGroup!="" && $numofadded>10){
		try {
			if($PostToGroup=="true") 
				$statusUpdate = $facebook->api('/'.$gid.'/feed', 'post', array('message'=> $strMsg, 'cb' => ''));
			elseif(is_numeric($PostToGroup)) 
				$statusUpdate = $facebook->api("/$PostToGroup/comments", 'post', array('message'=> $strMsg));
			
		} catch (FacebookApiException $e) {
			d($e, "Failed to send update");
		}
	}
}

?>


<script type="text/javascript">
  window.onload = function() {
    FB_RequireFeatures(["XFBML"], function() {
      FB.init('8152a4b299d4911965f21dacc6678f41', 'xd_receiver.htm');   
    });   
  };  
</script>
</body>
</html>

