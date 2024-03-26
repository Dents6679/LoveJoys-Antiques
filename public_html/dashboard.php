<?php

//start php session to grab user info
session_start();



// echo '<pre>';
// var_dump($_SESSION);
// echo '</pre>';
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


include("../private/config.php");

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);


//if user is not logged in, kick to homepage. 
if($_SESSION["authenticated"] != true){
    echo"<p>You are not logged in.</p>";
    // unset($_SESSION['authenticated']);
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

$stmt = $conn->prepare("SELECT Name, 2FAEnabled, IsAdmin FROM userTable WHERE ID=? LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$name = $row['Name'];
$admin = $row['IsAdmin'];
$twoFA = $row['2FAEnabled'];

$stmt->close(); 






    echo "<h1>Lovejoy's Antiques - Dashboard</h1>";
    
    if($admin == 1){
        echo "<h3><u>Admin</u></h3>";
        echo "<a href=viewEvaluations.php><button>View Evaluations</button></a>";
    }
    else{
        echo "<p><a href=requestEvaluation.php><button>Request Evaluation</button></a></p>";
    }
    
    echo "<h3><u>Account</u></h3>";
    echo "<p>Logged in as $name </p>";
    
    if($twoFA == 1){
        echo "<a href=disable2FA.php><button>Disable 2 Factor Authentication (SMS)</button></a>";
    }
    else{
        echo "<a href=enable2FA.php><button>Enable 2 Factor Authentication (SMS)</button></a>";
    }
    
    echo"<br/><br><a href = logOut.php><button> Log Out </button></a>";





?>
