<?php

//begin user session
session_start();

//if user is not logged in, kick to homepage. 
if($_SESSION["authenticated"] != true){
    echo"<p>You are not logged in.</p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    echo"<p><a href=index.php>HomePage</a></p>";
    exit(0);
}

include("../private/config.php");

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

//fetch user's session information

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

//end of getting session info


// Generate and store CSRF token in session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];





//set style for comment box to look nicer
echo "
<head>
    <style>
        .resizable-input {
            resize: both;
            overflow: auto;
            width: 40%; 
            height: 10%; 
        }
    </style>
    
    <script src='https://www.google.com/recaptcha/api.js'></script>"; //captcha API
    //captcha submit script
    echo "<script> 
            function onSubmit(token){
                document.getElementById('verifyForm').submit()
            } 
            </script>
</head>";


echo "<h2> Request Evaluation </h2>";
echo "<p>Fill out the form below to request an evaluation from our antique experts.</p>";

//evaluation form action='sendEvaluation.php'
echo "<form id=verifyForm method='POST' action='sendEvaluation.php' enctype='multipart/form-data'>";
echo "<p>Object Name*<br/>";
echo "<input type=text name='name' required maxlength='255'></p>";

echo "<p>Comments*<br/>";
echo "<textarea maxlength='1024' required name='comments' class='resizable-input' placeholder='Enter any comments about your antique, please give as much detail as possible.'></textarea></p>";
    
echo "<p>Contact Method*<br/>";
echo "<select name='contact_method' required>";
echo "<option value='Email'>Email</option>";
echo "<option value='Phone'>Phone</option>";

echo "</select></p>";

echo "<input type='hidden' name='csrf_token' value='$csrfToken'>"; //include csrf token 

echo "<p>Antique Image* (1 JPG/PNG image - Max 5MB )<br/>";
echo "<input required type='file' name='image' accept='image/png, image/jpeg' size='5000000' required>"; // 5 MB limit
echo "</p>";

echo "<button id=captchaButton class='g-recaptcha'
            data-sitekey=" . CAPTCHA_SITEKEY . "
            data-callback='onSubmit' 
            data-action='verify'>Submit</button>";


// echo "<input type='submit' value='Submit'>";
echo "</form>";




echo "<br/> <a href='dashboard.php'>Back to Dashboard</a>";

?>