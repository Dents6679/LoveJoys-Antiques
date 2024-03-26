<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

//import PHPMailer stuff
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $_SERVER['DOCUMENT_ROOT'] . '/mailer/Exception.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/mailer/SMTP.php';



//start database connection
include("../private/config.php");
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);


//CSRF Token checking. if no token is found, don't allow user to enter.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || filter_var($_POST['csrf_token'], FILTER_SANITIZE_SPECIAL_CHARS) !== $_SESSION['csrf_token']) {
        // Invalid CSRF Token
        echo "Invalid CSRF Token";
        echo "<a href=index.php>Return to Homepage</a>";
        exit(0);
    }
}

//captcha stuff
$recaptchaSecretKey = CAPTCHA_SECRET;
$response = $_POST['g-recaptcha-response'];
$verifyRecaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecretKey}&response={$response}");
$recaptcha = json_decode($verifyRecaptcha, true);

//Captcha check, prevent form use if suspicious. 
if ($recaptcha['success'] != 1 || $recaptcha['score'] < 0.7 || $recaptcha['action'] != "verify") {
    
    echo "Our site has detected suspicious activity from your browser. Please try again later or use another browser.";
            echo "<div/> <a href='index.php'>Homepage</a>";
    exit(0);
}


//define variables from registration form
//filter vairables to prevent XSS
//prevent signing up with same email with different cases
$email = strtolower(filter_var($_POST['email'], FILTER_SANITIZE_SPECIAL_CHARS)); 
$emailConfirm = strtolower(filter_var($_POST['emailConfirm'], FILTER_SANITIZE_SPECIAL_CHARS));
$password = filter_var($_POST['password'], FILTER_SANITIZE_SPECIAL_CHARS);
$passwordConfirm = filter_var($_POST['passwordConfirm'], FILTER_SANITIZE_SPECIAL_CHARS);
$name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
$number = filter_var($_POST['number'], FILTER_SANITIZE_SPECIAL_CHARS);
$securityAnswer = filter_var($_POST['securityAnswer'], FILTER_SANITIZE_SPECIAL_CHARS);
$securityAnswer2 = filter_var($_POST['securityAnswer2'], FILTER_SANITIZE_SPECIAL_CHARS);
$questionID = (int)filter_var($_POST['securityQuestion'], FILTER_SANITIZE_SPECIAL_CHARS);

//Create Unique User ID (no incrementing)
$userID = uniqid(bin2hex(random_bytes(4)), true);

//Create user's verification token
$verifyToken = bin2hex(random_bytes(16));


//Sends a verification Email to the provided email with the user's attached verification token.
function sendVerificationEmail($email,$verToken){
    
    $mail = new PHPMailer;
    $mail->isSMTP(); 
    $mail->SMTPDebug = 0; 
    $mail->Host = "smtp.gmail.com"; 
    $mail->Port = 587; // TLS only
    $mail->SMTPSecure = 'tls'; 
    $mail->SMTPAuth = true;
    
    $mail->Username = "i2csemailsender@gmail.com"; // email
    $mail->Password = MAIL_PASSWORD; // moved password to config.php (reduced exposure)
    
    $mail->setFrom('I2CSEmailSender@gmail.com', 'Lovejoys Antiques'); // From email and name
    $mail->addAddress("$email");
    
    $mail->isHTML(true);
    $mail->Subject = 'Verify your Email';
    
    //mail template.
  $emailTemplate = "
  <h2>You have registered with LoveJoy's Antiques</h2>
  <h5>Verify your email address to Login with the below given link</h5>
  <a href='https://matriarchal-balls.000webhostapp.com/verifyEmail.php?token=" . $verToken . "'>Click Here.</a>
  ";
   
  $mail->Body = $emailTemplate;
  $mail->send();
}



// Check if any fields are blank, HTML form should catch these but this is here to prevent any sql issues.
if ($email == "" || $emailConfirm == "" || $name == "" || $password == "" || $passwordConfirm == "" || $number == "") {
    echo "An input field has been left blank";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}


// Check if email already exists.
$stmt = $conn->prepare("SELECT * FROM userTable");
$stmt->execute();
$userResult = $stmt->get_result();

//Check to see if email is already registered in database.
while ($userRow = $userResult->fetch_assoc()) {
    if ($userRow['Email'] == $email) {
        echo "This Email has already been registered, please <a href='login.php'>log in</a> instead.";
        exit(0);
    }
}


// Check that emails match
if ($email != $emailConfirm) {
    echo "Emails do not match.";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}

// Check that passwords match
if ($password != $passwordConfirm) {
    echo "Passwords do not match.";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}


if(strlen($number) != 13){
    echo "You have Inputted an invalid phone number";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}

if($securityAnswer != $securityAnswer2){
    echo "Security Question Answers do not match!";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}

if($securityAnswer == ""){
    echo "Your Security Question Answer has been left blank.";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}


//Check password isn't PWNd
$hash = strtoupper(hash('sha1', $password));
$prefix = substr($hash, 0, 5);
$context = stream_context_create(array(
   'http' => array(
       'method' => 'GET',
       'header' => 'User-Agent: PHP'
   )
));
$response = file_get_contents('https://api.pwnedpasswords.com/range/' . $prefix, false, $context);

// Check if the full hash of the password is in the response
if (strpos($response, substr($hash, 5)) !== false) {
   echo "The password you have inputted has been exposed in a data breach. Please use a different password.";
   echo "<p><a href=index.php>Homepage</a></p>";
   exit(0);
} 


//if everything is okay, register user


//insert user into database with salt and hashing
$salt = bin2hex(random_bytes(16));
$options = ['cost' => 12];
$hashedPassword = password_hash($password . $salt, PASSWORD_DEFAULT, $options);
$stmt = $conn->prepare("INSERT INTO userTable (ID, Email, Salt, Hash, Name, ContactNumber) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $userID, $email, $salt, $hashedPassword, $name, $number);


//insert security question with salt & hashing
$salt2 = bin2hex(random_bytes(16));
$options2 = ['cost' => 7];
$hashedSecurityQuestion = password_hash($securityAnswer . $salt2, PASSWORD_DEFAULT, $options2);
$stmt2 = $conn->prepare("INSERT INTO securityQuestions (UserID, QuestionID, Salt, Hash) VALUES (?, ?, ?, ?)");
//bind parameters
$stmt2->bind_param("siss", $userID, $questionID, $salt2, $hashedSecurityQuestion);

$stmt3 = $conn->prepare("INSERT INTO emailVerification (ID, VerificationToken) VALUES (?, ?)");
//bind parameters
$stmt3->bind_param("ss", $userID, $verifyToken);

//execute statements to register user to database.
if ($stmt->execute() && $stmt2->execute() && $stmt3->execute()) {

    
    //upon success, send user a verification email and alert user to check emails.
    sendVerificationEmail($email, $verifyToken);
    
    echo "Your account has been registered. <br/>Before logging in, please verify your email using the link sent to your email address. It may be in your spam folder.";
    echo "<p><a href=login.php>Log In</a></p>";
} else {
    echo "Registration Failed";
    echo "<p><a href=register.php>Try again.</a></p>";
    exit(0);
}







// Close database connection
$conn->close();

?>