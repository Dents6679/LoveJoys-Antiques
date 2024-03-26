<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This file handles logic for both email sending (requesting a reset) and password resetting (backend side).

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

$exitMessage = "A Link to reset your password has been sent to your inbox, if an account exists with the supplied email. <br/> <a href=index.php> Homepage </a>";

//Function for sending reset email to user.
function sendResetEmail($email, $verToken){

    $mail = new PHPMailer;
    $mail->isSMTP(); 
    $mail->SMTPDebug = 0; // 0 = off (for production use) - 1 = client messages - 2 = client and server messages
    $mail->Host = "smtp.gmail.com"; // use $mail->Host = gethostbyname('smtp.gmail.com'); // if your network does not support SMTP over IPv6
    $mail->Port = 587; 
    $mail->SMTPSecure = 'tls'; 
    $mail->SMTPAuth = true;
    
    $mail->Username = "i2csemailsender@gmail.com"; // email
    $mail->Password = MAIL_PASSWORD; // moved password to config.php (reduced exposure)
    
    $mail->setFrom('I2CSEmailSender@gmail.com', 'Lovejoys Antiques'); // From email and name
    $mail->addAddress($email);
    
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset';
    
   $emailTemplate = "
   <h2>You have Requested to Reset your Password.</h2>
   <h5>Please reset your password using the link below.</h5>
  <a href='https://matriarchal-balls.000webhostapp.com/resetUserPassword.php?token=$verToken&email=$email'>Click Here.</a> 
   "; 
   
   $mail->Body = $emailTemplate;
   $mail->send();
}


//---------------------------------------------------

//if requesting a password reset. (displayed the first time the user visits the page)
if(isset($_POST['passwordRequest'])){
    
    //CSRF Token Checks, if CSRF Token isn't present, prevent user from accessing site.
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_POST['csrf_token']) || filter_var($_POST['csrf_token'], FILTER_SANITIZE_SPECIAL_CHARS) !== $_SESSION['csrf_token']) {
            echo $_SESSION['csrf_token'] . "|||||";
            echo $_POST['csrf_token'] . "||||";
            echo "Invalid CSRF Token";
            echo "<a href=index.php>Return to Homepage</a>";
            exit(0);
        }
    }

    
    
    $email = strtolower(filter_var($_POST['email'], FILTER_SANITIZE_SPECIAL_CHARS));
    $token = bin2hex(random_bytes(16));
    
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT ID FROM userTable WHERE Email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    //if email is not found, exit
    if ($result->num_rows == 0) {
        echo $exitMessage;
        exit(0);
    }
        
        
    $userRow = $result->fetch_assoc();
    $userID = $userRow['ID'];
    
    
    
    $stmt = $conn->prepare("INSERT INTO passwordResets (ID, ResetToken) VALUES (?, ?)");
    //bind parameters
    $stmt->bind_param("ss", $userID, $token);

    
    
        if ($stmt->execute()) {
            sendResetEmail( $email, $token);
        } else {
            echo "Something went wrong.";
            echo "<p><a href=index.html> Return to homepage</a></p>";
            exit(0);
        }
    
    echo $exitMessage;

    
}

//---------------------------------------------------------------

//if page has been accessed from an email.
if(isset($_POST['passwordUpdate'])){
    
    //get data from password reset form and filter them to prevent XSS
    $email = mysqli_real_escape_string($conn, strtolower(filter_var($_POST['email'], FILTER_SANITIZE_SPECIAL_CHARS)));
    $newPassword = mysqli_real_escape_string($conn, filter_var($_POST['password'], FILTER_SANITIZE_SPECIAL_CHARS));
    $newPassword2 = mysqli_real_escape_string($conn, filter_var($_POST['password2'], FILTER_SANITIZE_SPECIAL_CHARS));
    $token = mysqli_real_escape_string($conn, filter_var($_POST['passwordToken'], FILTER_SANITIZE_SPECIAL_CHARS));
    
    if(empty($token)){
        echo "A Token error has occurred. (No Token)";
        echo "<p><a href=index.html> Return to homepage</a></p>";
        exit(0);
    }
        
        

    if(empty($email) || empty($newPassword) || empty($newPassword2)){
        echo "A Field has been left blank.";
        echo "<p><a href=index.html> Return to homepage</a></p>";
        exit(0);
        
    }
    
    if($newPassword != $newPassword2){
        echo "Passwords do not match.";
        echo "<a href=resetPassword.php?token=$token&email=$email> Try again.</a>";
        exit(0);
    }
    
    //Check password isn't PWNd

    $hash = strtoupper(hash('sha1', $newPassword));
    $prefix = substr($hash, 0, 5);
    $context = stream_context_create(array(
       'http' => array(
           'method' => 'GET',
           'header' => 'User-Agent: PHP'
       )
    ));
    $response = file_get_contents('https://api.pwnedpasswords.com/range/' . $prefix, false, $context);
    if (strpos($response, substr($hash, 5)) !== false) {
       echo "The password you have inputted has been exposed in a data breach. Please use a different password.";
       echo "<a href=resetPassword.php?token=$token&email=$email> Try again.</a>";
       exit(0);
    }   
    
        
    // Use prepared statement to check the token
    $stmt = $conn->prepare("SELECT ID FROM passwordResets WHERE ResetToken=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    //if no results are found, exit.
    if($result->num_rows == 0){
        echo "A Token Error has occurred. (Invalid Token)<br><a href='index.php'>Homepage.</a>>";
        exit(0);
    }
    
    
    //CHECK TOKEN HASN'T EXPIRED USING DATABASE'S TIMESTAMP (set to 5 mins)
    
    
    $stmt = $conn->prepare("SELECT ID, CreationTime FROM passwordResets WHERE ResetToken=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $pwdRow = $result->fetch_assoc();
    $userID = $pwdRow['ID'];
    $currentTimeStamp = new DateTime('now');
    

    
    //DB timestamp to DateTime object
    $storedDateTime = new DateTime($pwdRow['CreationTime']);

    // calculate difference
    $difference = $currentTimeStamp->getTimeStamp() - $storedDateTime->getTimeStamp();
    
    $waitTime = 600;

    //
    if($difference > $waitTime){
       
       //delete token
        $deleteStmt = $conn->prepare("DELETE FROM passwordResets WHERE ResetToken=?");
        $deleteStmt->bind_param("s", $token);
        $deleteStmt->execute();
       
       
       
        echo "This Token has expired, please try <a href='resetPassword.php'>resetting your email again</a>.";
        exit(0);
    }
    
    

    

    $newSalt = bin2hex(random_bytes(16));
    $options = ['cost'=> 12];
    $newHashedPassword = password_hash($newPassword . $newSalt, PASSWORD_DEFAULT, $options);
    
    //update hash
    
    $stmtUpdateHash = $conn->prepare("UPDATE userTable SET Hash=? WHERE ID=? LIMIT 1");
    $stmtUpdateHash->bind_param("ss", $newHashedPassword, $userID);
    $runUpdateHashQuery = $stmtUpdateHash->execute();
    
    //update salt
    $stmtUpdateSalt = $conn->prepare("UPDATE userTable SET Salt=? WHERE ID=? LIMIT 1");
    $stmtUpdateSalt->bind_param("ss", $newSalt, $userID);
    $runUpdateSaltQuery = $stmtUpdateSalt->execute();
    
    //remove passwordReset entry.
    $stmtDeleteToken = $conn->prepare("DELETE FROM passwordResets WHERE ID=? LIMIT 1");
    $stmtDeleteToken->bind_param("s", $userID);
    $runDeleteTokenQuery = $stmtDeleteToken->execute();
    
    if($runUpdateHashQuery && $runDeleteTokenQuery && $runUpdateSaltQuery){
        echo "Your password has been successfully updated!<br/>";
        echo "You can now <a href=login.php>log in</a> using your new password.";
        exit(0);
    } else {
        echo "An error has occurred. (a value could not be updated in the Database)";
        echo "<p><a href=index.html> Return to homepage</a></p>";
    }
         
    
            
} 
     




?>