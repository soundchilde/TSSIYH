<?php
/* PHP SCRAPER TO CREATE YOUTUBE PLAYLIST FROM FACEBOOK GROUP POSTS EXTRACTED FROM ThatSongStuckInYourHead
Please note that this is just an example, you will need to replace the GROUP_ID with the actual group ID, YOUR_COOKIE with your cookie value, YOUR_PLAYLIST_ID with the actual playlist ID and YOUR_API_KEY with your YouTube API key. Also, this is just a skeleton code and you may need to add error handling, and other functionalities such as rate limiting to make it production ready.
*/
if($_REQUEST['showdebug']==true){
	$debug=true;
	print_r($_POST);
	echo "<br><br>";
}
//-------------------------------------------------
//Initiate the CURL process to extract posts from FB
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://www.facebook.com/groups/tssiyh",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "Cookie: YOUR_COOKIE"
  ),
));

$response = curl_exec($curl);
curl_close($curl);

// Use DOMDocument to parse the HTML response
$doc = new DOMDocument();
@$doc->loadHTML($response);

// Get all post elements
$xpath = new DOMXpath($doc);
$posts = $xpath->query("//div[contains(@class, '_1dwg')]");

foreach ($posts as $post) {
    // Extract post information
    $time = $xpath->query(".//abbr[contains(@class, 'livetimestamp')]", $post)->item(0)->getAttribute('data-utime');
    $text = $xpath->query(".//div[contains(@class, '_5pbx')]", $post)->item(0)->nodeValue;
    $youtubeLinks = extractYoutubeLinks($text);
    if (count($youtubeLinks) > 0) {
        // Add YouTube links to the playlist
        foreach ($youtubeLinks as $youtubeLink) {
            addToPlaylist($youtubeLink);
        }
    }
}

function extractYoutubeLinks($text) {
    preg_match_all('/https?:\/\/(www\.)?youtube\.com\/watch\?v=([A-Za-z0-9._%-]*)/i', $text, $matches);
    return $matches[0];
}

function addToPlaylist($youtubeLink) {
    // Get the video ID from the YouTube link
    parse_str(parse_url($youtubeLink, PHP_URL_QUERY), $queryParams);
    $videoId = $queryParams['v'];
    
    // Add the video to the playlist using the YouTube API
    $playlistId = 'YOUR_PLAYLIST_ID';
    $apiKey = 'YOUR_API_KEY';
    $url = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&key=' . $apiKey;
    $data = array(
        'snippet' => array(
            'playlistId' => $playlistId,
            'resourceId' => array(
                'kind' => 'youtube#video',
				'videoId' => $videoId
				)
			)
		);
	$options = array(
		'http' => array(
			'method' => 'POST',
			'header' => 'Content-type: application/json',
			'content' => json_encode($data)
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	$response = json_decode($result);
	if ($response->error) {
		// Handle the error
	}
}

