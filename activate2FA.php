<?php
session_start();

//imports
include("../private/config.php");
include("./2FA/report-verification.php");

//if user is not logged in, kick to homepage. 
if($_SESSION["authenticated"] != true){
    echo"<p>You are not logged in.</p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    echo"<p><a href=index.php>HomePage</a></p>";
    exit(0);
}

//create db connection
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);



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

$stmt = $conn->prepare("SELECT 2FAEnabled,ContactNumber FROM userTable WHERE ID=? LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$twofa = $row['2FAEnabled'];
$phone = $row['ContactNumber'];

$stmt->close(); 

//end of getting session info


//check code using API
$code = filter_var($_POST['code'], FILTER_SANITIZE_NUMBER_INT); 
$success = tryVerification($phone, $code);

//handle incorrect codes
if(!$success){
    echo "<p>This code is incorrect, Please <a href='enable2fa.php'>try again.</a>.</p>";
    exit(0);
}



//set 2FA to true in database.

$enabled = 1;    

$update2faQuery = "UPDATE userTable SET 2FAEnabled=? WHERE ID=? LIMIT 1";
$stmtUpdate2fa = $conn->prepare($update2faQuery);
$stmtUpdate2fa->bind_param("is", $enabled, $userID);
$runUpdate2faQuery = $stmtUpdate2fa->execute();

if($runUpdate2faQuery){
    
     // Display a message before redirecting
    echo '<p>Code is correct! Redirecting to Dashboard....</p>';
    // Redirect using JavaScript
    echo '<script type="text/javascript">';
    echo 'setTimeout(function() { window.location.href = "index.php"; }, 1500);'; // Redirect after 2 seconds
    echo '</script>';
} else {
    echo "An error has occurred.";
    echo "<p><a href=index.html> Return to homepage</a></p>";
    exit(0);
}

?>
