<?php

//PHPmailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $_SERVER['DOCUMENT_ROOT'] . '/mailer/Exception.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mailer/SMTP.php';

//start database connection
include("../private/config.php");
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

//begin user session
session_start();

$exitMessage = "A verification email has been sent to your inbox, if an unverified account exists with your email. 
<br/> <a href=index.php> Homepage </a>";

//function for resending verification email, send reverification email to provided email with provided verifiaction token.
function sendVerificationEmail($email,$verToken){
    
    $mail = new PHPMailer;
    $mail->isSMTP(); 
    $mail->SMTPDebug = 0; // 0 = off (for production use) - 1 = client messages - 2 = client and server messages
    $mail->Host = "smtp.gmail.com"; // use $mail->Host = gethostbyname('smtp.gmail.com'); // if your network does not support SMTP over IPv6
    $mail->Port = 587; // TLS only
    $mail->SMTPSecure = 'tls'; // ssl is deprecated
    $mail->SMTPAuth = true;
    
    $mail->Username = "i2csemailsender@gmail.com"; // email
    $mail->Password = MAIL_PASSWORD; // moved password to config.php (reduced exposure)
    
    $mail->setFrom('I2CSEmailSender@gmail.com', 'Lovejoys Antiques'); // From email and name
    $mail->addAddress("$email");
    
    $mail->isHTML(true);
    $mail->Subject = 'Verification Request';
    
   $emailTemplate = "
   <h2>You have Requested to verify your account.</h2>
   <h5>Verify your email address using the link below.</h5>
   <a href='https://matriarchal-balls.000webhostapp.com/verifyEmail.php?token=" . $verToken . "'>Click Here.</a>
   ";
   
   $mail->Body = $emailTemplate;
   $mail->send();
}

//CSRF Token Checks, if CSRF Token isn't present, prevent user from accessing site.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || filter_var($_POST['csrf_token'], FILTER_SANITIZE_SPECIAL_CHARS) !== $_SESSION['csrf_token']) {
        echo "Invalid CSRF Token";
        echo "<a href=index.php>Return to Homepage</a>";
        exit(0);
    }
}

if(empty($_POST['email'])){
    echo "You did not enter an email Address.";
    echo "<p><a href=ResendVerificationEmail.php>Try again.</a></p>";
}

//get email from form and sanitize to prevent XSS
$email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_SPECIAL_CHARS));

// Get user ID from Provided Email.
$stmt = $conn->prepare("SELECT ID FROM userTable WHERE Email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

//exit if no user exists.
if ($result->num_rows == 0) {
    //get user's row
    echo "Didn't send an email.";
    echo $exitMessage;
    exit(0);
}

$userRow = $result->fetch_assoc();
$userID = $userRow['ID'];


$stmt->close();

//get Verification info from User ID
$newstmt = $conn->prepare("SELECT VerificationToken, Verified FROM emailVerification WHERE ID=? LIMIT 1");
$newstmt->bind_param("s", $userID);
$newstmt->execute();

// Fetch the result into $verRow
$result = $newstmt->get_result();
$verRow = $result->fetch_assoc();

$isVerified = $verRow['Verified'];
$verificationToken = $verRow['VerificationToken'];



//if user isn't verified, send Email.
if ($isVerified == "0") {
        sendVerificationEmail($email, $verificationToken);
    }
    
echo $exitMessage;
?>