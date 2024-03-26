<?php

//Start user session
session_start();
include("../private/config.php");

//if user is already logged in, move to Dashboard.
if (isset($_SESSION['authenticated'])) {
   header("Location: dashboard.php");
   exit();
  
} 
else {
    
    //generate and store CSRF Token
    if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];


   echo "<head>";
   
   
   echo "<script src='https://www.google.com/recaptcha/api.js'></script>"; //captcha API
   //captcha submit script
   echo "<script> 
            function onSubmit(token){
                document.getElementById('verifyForm').submit();
            } 
            </script>
            ";
   echo "</head>";
   
   echo "<body>";

    // login form.
   echo "<h1> Login </h1>";
   echo "<form action='loginCheck.php' id='verifyForm' method='POST'>";
   echo "<p>Email:";
   echo "<br><input name='email' type='email' maxlength='48'/></p>";
 
   echo "<p>Password:";
   echo "<br><input name='password' type='password'/></p>";
   echo "<input type='hidden' name='csrf_token' value='$csrfToken'>"; //pass csrf token to login checker to prevent CSRF attacks
   echo "<br>";
   echo "<button id=captchaButton class='g-recaptcha'
        data-sitekey=" . CAPTCHA_SITEKEY . "
        data-callback='onSubmit' 
        data-action='verify'>Log In</button>";
   echo "</form>";
   
   echo "<p><a href='register.php'>I don't have an account</a></p>";
   echo "<p><a href='resetPasswordPage.php'>Forgot Password</a></p>";
   echo "</body>";
           
        
        
    
    
   
   
   
   
}
?>
