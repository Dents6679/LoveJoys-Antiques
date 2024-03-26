<?php

//start database connection
include("../private/config.php");
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

session_start();

$sessionID = $_SESSION['authUser'];

$secqAnswer = filter_var($_POST['securityQuestionAnswer'], FILTER_SANITIZE_SPECIAL_CHARS); 


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

//end of getting session info


// Generate and store CSRF token in session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];


$stmt = $conn->prepare("SELECT Salt, Hash FROM securityQuestions WHERE UserID = ?");
$stmt->bind_param("s",$userID);
$stmt->execute();
$result = $stmt->get_result();
$secqRow = $result->fetch_assoc();

$secqSalt = $secqRow['Salt'];
$secqHash = $secqRow['Hash'];


if(password_verify($secqAnswer . $secqSalt, $secqHash)){
    
    //Authenticate User.
    $_SESSION['authenticated'] = TRUE;
    
    //redirect user to dashboard.
    echo "<script>window.location.replace('dashboard.php');</script>";
    
    exit(0);
}
else{
    echo "Your answer is incorrect, please try <a href=login.php>logging in</a> again.";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
}





?>