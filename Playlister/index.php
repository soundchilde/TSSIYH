<?php
session_start();

//WEB APP for logging into Facebook

// Load the Facebook PHP SDK
require_once __DIR__ . '../libraries/fbgraph-php-sdk-5.x/src/Facebook/autoload.php';

// Initialize the Facebook SDK
$facebook = new Facebook\Facebook([
    'app_id' => '943090770186027',
    'app_secret' => '8ccf2e2ddd0983effa2193b00f619a02',
    'default_graph_version' => 'v3.0',
]);

// Get the access token
$helper = $facebook->getJavaScriptHelper();
try {
    $accessToken = $helper->getAccessToken();
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

// If the access token is obtained, store it in a session
if (isset($accessToken)) {
    $_SESSION['facebook_access_token'] = (string) $accessToken;

    // Get the user's profile information
    try {
        $response = $facebook->get('/me', $accessToken);
        $user = $response->getGraphUser();
        $_SESSION['facebook_user'] = $user;
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Web App</title>

    <!-- Include a modern CSS framework for styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css">

    <!-- Include the Facebook JavaScript SDK -->
    <script src="https://connect.facebook.net/en_US/sdk.js"></script>
</head>
<body>
    <section class="section">
        <div class="container">
            <?php if (!isset($_SESSION['facebook_access_token'])): ?>
                <p>Please log in with Facebook to continue</p>
                <button id="login-button" class="button is-primary">Login with Facebook</button>
            <?php else: ?>
                <p>Welcome, <?php echo $_SESSION['facebook_user']['name']; ?>!</p>
                <a href="logout.php" class="button is-danger">Logout</a>
            <?php endif; ?>
        </div>
    </
