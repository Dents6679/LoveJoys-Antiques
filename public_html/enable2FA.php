<?php
session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include("../private/config.php");
include("./2FA/initiate-verification.php");

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

//if user is not logged in, kick to homepage. 
if($_SESSION["authenticated"] != true){
    echo"<p>You are not logged in.</p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    echo"<p><a href=index.php>HomePage</a></p>";
    exit(0);
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

$stmt = $conn->prepare("SELECT ContactNumber FROM userTable WHERE ID=? LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$userNumber = $row['ContactNumber'];

$stmt->close(); 

//end of getting session info


// Obfuscate phone number
$digitsToHide = max(strlen($userNumber) - 4, 0);
$obfuscatedUserNumber = str_repeat('*', $digitsToHide) . substr($userNumber, -4);

//send code to user's phone number.
initiateVerification($userNumber);

echo "<p>To Activate 2 Factor Authentication, please enter the code which has been sent to $obfuscatedUserNumber below.</p>";
echo "<form method='POST' action='activate2FA.php'>";
echo "<input name='code' type='number' maxlength='4' required>";
echo "<br><br> <input type='submit' name='2faSubmit' value='Submit'>";
echo "</form>";


?>