<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


//start database connection
include("../private/config.php");

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

//start user session
session_start();
include("./utils.php");
$ip = $_SERVER["REMOTE_ADDR"];


$maxAttempts = 5; //desired maximum login attempts
$timeFrame = 300; //Wait time for IP lockout in seconds (1hr)

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_response = $_POST['g-recaptcha-response'];

$recaptcha = file_get_contents($recaptcha_url . '?secret=' . CAPTCHA_SECRET . '&response=' . $recaptcha_response);
$recaptcha = json_decode($recaptcha, true);


//display login page if captcha is successful.
if($recaptcha['success'] != 1 || $recaptcha['score'] < 0.7 || $recaptcha['action'] != "verify")
{
    echo "Our site has detected suspicious activity from your browser. Please try again later or use another browser.";
    echo "<div/> <a href='index.php'>Homepage</a>";
    exit(0);
}


//CSRF Token Checks, if CSRF Token isn't present, prevent user from accessing site.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || filter_var($_POST['csrf_token'], FILTER_SANITIZE_SPECIAL_CHARS) !== $_SESSION['csrf_token']) {
        echo "Invalid CSRF Token";
        echo "<a href=index.php>Return to Homepage</a>";
        exit(0);
    }
}


// collect values from web form.
//sanitize special chars to prevent xss
$submittedEmail = strtolower(filter_var($_POST['email'], FILTER_SANITIZE_SPECIAL_CHARS)); 
$submittedPassword = filter_var($_POST['password'], FILTER_SANITIZE_SPECIAL_CHARS); 


//check if email is blank
if($submittedEmail == "" or $submittedPassword == ""){
    echo "a field has been left blank";
    echo "<div/> <a href='login.php'>try again</a>.";
    exit(0);
}


if ($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}



// Prepare and execute sql statement to get user's row from database
$userQuery = $conn->prepare("SELECT * FROM userTable WHERE Email = ?");
$userQuery->bind_param("s", $submittedEmail);
$userQuery->execute();
$result = $userQuery->get_result();

//store row in variable
$userRow = $result->fetch_assoc();

if ($result->num_rows > 0) {
    
    $userSalt = $userRow["Salt"]; //fetch stored salt
    $userHash = $userRow["Hash"]; //fetch stored hash
    $userID = $userRow["ID"]; //fetch stored hash
    
    $stmt = $conn->prepare("SELECT Verified FROM emailVerification WHERE ID = ?");
    $stmt->bind_param("s",$userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $verRow = $result->fetch_assoc();
    $isUserVerified = $verRow['Verified'];
    
    

    //verify password using submitted password with user salt appended.
    if (password_verify($submittedPassword . $userSalt, $userHash)) {
        
        //if user isn't verified, prompt to resend a verification email.
        if($isUserVerified == 0){
            echo "Your Email has not been verified. <br/>Please check your emails for a verification Email.";
            echo "<br> <a href=resendEmailVerification.php> Didn't receive a verification Email? </a>";
            exit(0);
        }
        
        $user2FA = $userRow["2FAEnabled"];
        $userNumber = $userRow['ContactNumber'];    
        
        
        //create session ID for user.
        $sessionID = uniqid("", true);
        $_SESSION['authUser'] = $sessionID;
        
        
        //insert new session into sessionTable
        $stmt = $conn->prepare("INSERT INTO sessionTable (SessionID, UserID) VALUES (?, ?)");
        $stmt->bind_param("ss", $sessionID, $userID);
        
        if(!$stmt->execute()){
            echo "There has been a session Problem";
            unset($_SESSION['authUser']);
            exit(0);
        }
        
        $underLoginThreshold = checkLoginAttempts($ip, $maxAttempts, $timeFrame, $conn);

        if (!$underLoginThreshold){
            echo "Login attempts exceeded. Try again later.";
            echo "<p><a href=index.php>Homepage.</a></p>";
            exit(0);
        }
        
        resetLoginAttempts($ip, $conn);
        
        if($user2FA == 0){
            
            
            //get user's security Question
            $stmt = $conn->prepare("SELECT QuestionID, Salt, Hash FROM securityQuestions WHERE UserID = ?");
            $stmt->bind_param("s",$userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $secqRow = $result->fetch_assoc();
            
            $secqQuestionID = $secqRow['QuestionID'];
            $csrfToken = $_SESSION['csrf_token'];

            
            echo "<p>Please enter the answer to your security question below:</p>";
            echo "<form method='POST' action='checkSecurityQuestion.php'>";
            echo "<br>" . $SECURITY_QUESTIONS[$secqQuestionID] . "<br>";
            echo "<input name='securityQuestionAnswer' type='text' autocomplete='off' required>";
            echo "<input type='hidden' name='csrf_token' value='$csrfToken'>"; // pass csrf token
            echo "<br><br> <input type='submit' name='secqSubmit' value='Submit'>";
            echo "</form>";
            
            exit(0);
        }
        
        $digitsToHide = max(strlen($userNumber) - 4, 0);

        // Obfuscate the phone number
        $obfuscatedUserNumber = str_repeat('*', $digitsToHide) . substr($userNumber, -4);
        
        //send code to user's phone number.
        include "./2FA/initiate-verification.php";
        
        initiateVerification($userNumber);
        $csrfToken = $_SESSION['csrf_token'];
        echo "<p>We've sent a verification code to $obfuscatedUserNumber to verify your login attempt.<br>Please enter it below.</p>";
        echo "<form method='POST' action='verify2FACode.php'>";
        echo "<input name='code' type='number' maxlength='4' required>";
          echo "<input type='hidden' name='csrf_token' value='$csrfToken'>"; //pass csrf token
        echo "<br><br> <input type='submit' name='2faSubmit' value='Submit'>";
        echo "</form>";
        
        
    }
    else{
        //check user is under password threshold
        $underLoginThreshold = checkLoginAttempts($ip, $maxAttempts, $timeFrame, $conn);

        if (!$underLoginThreshold){
            echo "Login attempts exceeded. Try again later.";
            echo "<p><a href=index.php>Homepage.</a></p>";
            exit(0);
        }
        
        
        //alert user password is incorrect.
        echo "Incorrect Password, Please <a href='login.php'>try again</a>.";
    }
} else{
    
    //check user is under password threshold
        $underLoginThreshold = checkLoginAttempts($ip, $maxAttempts, $timeFrame, $conn);
    
        if (!$underLoginThreshold) {
            echo "Login attempts exceeded. Try again later.";
            echo "<p><a href=index.php>Homepage.</a></p>";
            exit(0);
        }

    //alert user email is not registered
    echo "This email has not been registered, please <a href='register.php'>register an account</a>.";
}


//Close prepared statement and connection
$userQuery->close();
$conn->close();


?>