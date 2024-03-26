<?php
session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

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

$stmt = $conn->prepare("SELECT 2FAEnabled FROM userTable WHERE ID=? LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$twofa = $row['2FAEnabled'];

$stmt->close(); 

//end of getting session info



if($twofa == 0){
    echo"<p>2 Factor Authentication has already been disabled on your account.</p>";
    echo"<p><a href=index.php>Homepage</a></p>";
}


//set 2fa off in Database for user.

$disabled = 0;    

$update2faQuery = "UPDATE userTable SET 2FAEnabled=? WHERE ID=? LIMIT 1";
$stmtUpdate2fa = $conn->prepare($update2faQuery);
$stmtUpdate2fa->bind_param("is", $disabled, $userID);
$runUpdate2faQuery = $stmtUpdate2fa->execute();

if($runUpdate2faQuery){
    
     // Display a message before redirecting
    echo '<p>2 Factor Authentication has been disabled on your account.</p>';
    // Redirect using JavaScript
    echo "<p><a href=index.php>Homepage.</a></p>";
} else {
    echo "An error has occurred.";
    echo "<p><a href=index.html> Return to homepage</a></p>";
    exit(0);
}

?>
