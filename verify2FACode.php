<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);



//start user session
session_start();
include("../private/config.php");
include("./2FA/report-verification.php");



$code = filter_var($_POST['code'], FILTER_SANITIZE_NUMBER_INT); 


$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);


//CSRF Token Checks, if CSRF Token isn't present, prevent user from accessing site.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || filter_var($_POST['csrf_token'], FILTER_SANITIZE_SPECIAL_CHARS) !== $_SESSION['csrf_token']) {
        echo "Invalid CSRF Token<br>";
        echo "<a href=index.php>Return to Homepage</a>";
        exit(0);
    }
}


// get session variables from Session ID
$sessionID = $_SESSION['authUser'];

$stmt = $conn->prepare("SELECT UserID FROM sessionTable WHERE SessionID=? LIMIT 1");
$stmt->bind_param("s", $sessionID);
$stmt->execute();
$result = $stmt->get_result();
//force user to log in again if session has expired.
if($result->num_rows == 0){
    echo "Your session has expired, please log in again.";
    echo "<p><a href='index.php'>Homepage.</a></p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    exit(0);
}
$row = $result->fetch_assoc();
$userID = $row['UserID'];

$stmt->close();

$stmt = $conn->prepare("SELECT 2FAEnabled, ContactNumber FROM userTable WHERE ID=? LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$tfa = $row['2FAEnabled'];
$phone = $row['ContactNumber'];

$stmt->close(); 

//end of getting session info



//if user is redirected here with 2FA disabled, log out and throw to homepage.
if($tfa != 1){
    
    //unset values stored in session
    unset($_SESSION['authenticated']);
    unset($_SESSION['authUser']);
    
    echo "<p>An error has occurred, Please try logging in again.</p>";
    echo "<p><a href='index.php'>Homepage</a></p>";
    exit(0);
    
}



//check code using API
$success = tryVerification($phone, $code);

//handle incorrect codes
if(!$success){
    
    unset($_SESSION['authenticated']);
    unset($_SESSION['authUser']);
    echo "<p>This code is incorrect, Please <a href='login.php'>try logging in again</a>.</p>";
    exit(0);
}

    $_SESSION['authenticated'] = TRUE;
    // Display a message before redirecting
    echo '<p>Code is correct! Redirecting...</p>';
    
    // Redirect using JavaScript
    echo '<script type="text/javascript">';
    echo 'setTimeout(function() { window.location.href = "index.php"; }, 2000);'; // Redirect after 2 seconds
    echo '</script>';

?>