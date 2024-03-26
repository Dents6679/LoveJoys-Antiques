<?php

//start user session
session_start();
include("./utils.php");
include("../private/config.php");
//redirect user to dashboard if they're already logged in.
if (isset($_SESSION['authenticated'])) {
   header("Location: dashboard.php");
   exit();
} 

//generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];


//HTML Head
echo "<head>";
echo "<script src='https://www.google.com/recaptcha/api.js'></script>"; //reCAPTCHA script
//captcha submission functionality
echo "<script> 
        function onSubmit(token){
            document.getElementById('verifyForm').submit();
        } 
        </script>
        ";

echo "
<script>
  function addPrefix() {
    const input = document.getElementById('number');
    if (!input.value.startsWith('+44')) {
      input.value = '+44' + input.value;
    }
  }
</script>
";



//password check script
echo "<script>
        
const commonPasswords = [
  'password',
  'password1',
  'Password1!',
  '123456',
  '123456789',
  'guest',
  'qwerty',
  '12345678',
  '111111',
  '12345',
  'col123456',
  '123123',
  '1234567',
  '1234',
  '1234567890',
  '000000',
  '555555',
  '666666',
  '123321',
  '654321',
  '7777777',
  '123',
  'D1lakiss',
  '777777',
  '110110jp',
  '1111',
  '987654321',
  '121212',
  'Gizli',
  'abc123',
  '112233',
  'azerty',
  '159753',
  '1q2w3e4r',
  '54321',
  'pass@123',
  '222222',
  'qwertyuiop',
  'qwerty123',
  'qazwsx',
  'vip',
  'asdasd',
  '123qwe',
  '123654',
  'iloveyou',
  'a1b2c3',
  '999999',
  'Groupd2013',
  '1q2w3e',
  'usr',
  'Liman1000',
  '1111111',
  '333333',
  '123123123',
  '9136668099',
  '11111111',
  '1qaz2wsx',
  'password1',
  'mar20lt',
  '987654321',
  'gfhjkm',
  '159357',
  'abcd1234',
  '131313',
  '789456',
  'luzit2000',
  'aaaaaa',
  'zxcvbnm',
  'asdfghjkl',
  '1234qwer',
  '88888888',
  'dragon',
  '987654',
  '888888',
  'qwe123',
  'football',
  '3601',
  'asdfgh',
  'master',
  'samsung',
  '12345678910',
  'killer',
  '1237895',
  '1234561',
  '12344321',
  'daniel',
  '000000',
  '444444',
  '101010',
  'qazwsxedc',
  '789456123',
  'super123',
  'qwer1234',
  '123456789a',
  '823477aA',
  '147258369',
  'unknown',
  '98765',
  'q1w2e3r4',
  '232323',
  '102030',
  '12341234',
];
    
    function checkPasswordStrength(password) {
      //initialize variables
      var strength = 0;
      var tips = 'Follow these tips for a more secure password:  ';
    
      //check password isn't a common password
      if(commonPasswords.includes(password.toLowerCase())){
        strength -= 10;
        tips += 'Your password is too common. ';
      }
    
      //check password length
      if (password.length < 6) {
        strength -= 10;
        tips += 'Your password is too short. ';
      } 
      
      if (password.length < 12) {
        tips += 'Make your password longer. ';
      } else {
        strength += 1;
      }
    
      //check for mixed case
      if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
        strength += 1;
      } else {
        tips += 'Use both lowercase and uppercase letters. ';
      }
    
      //check for numbers
      if (password.match(/\d/)) {
        strength += 1;
      } else {
        tips += 'Include at least one number. ';
      }
    
      //check for special characters
      if (password.match(/[^a-zA-Z\d]/)) {
        strength += 1;
      } else {
        tips += 'Include at least one special character. ';
      }
    
      // Return results
      
      //get html elements to edit
      var strengthElement = document.getElementById('passwordStrength');
      var tipsElement = document.getElementById('tips');
      var registerButton = document.getElementById('captchaButton');
      
    
      //update elements
      
      if (strength < 2) {
        strengthElement.textContent = 'Bad.';
        tipsElement.textContent = tips;
        strengthElement.style.color = 'red';
        registerButton.disabled = true;
        registerButton.title = 'Your password is too unsecure, please follow some of our tips.';
        
      } else if (strength === 2) {
        strengthElement.textContent = 'Okay.';
        tipsElement.textContent = tips;
        strengthElement.style.color = 'orange';
        registerButton.disabled = false;
        
        
      } else if (strength === 3) {
        strengthElement.textContent = 'Good.';
        tipsElement.textContent = tips;
        strengthElement.style.color = 'DarkGreen';
        registerButton.disabled = false;
        
        
      } else {
        strengthElement.textContent = 'Great!';
        tipsElement.textContent = '';
        strengthElement.style.color = 'LimeGreen';
        registerButton.disabled = false;
        
      }
    }

    </script>";
    
echo "</head>";

//HTML body
echo "<body>";


//captcha processing


echo "<h1> Register </h1>";
//registration form
echo "<form action='registerCheck.php' id='verifyForm' method='POST'";

echo "<p>Email:";
echo "<br><input name='email', type='email' maxlength='48' required>";
echo "<br> Confirm Email:";
echo "<br><input name='emailConfirm', type='email' required></p>";

echo "<p>Password:";
echo "<br/><input name='password', type='password' oninput='checkPasswordStrength(this.value)' required>"; //Update strength upon input


echo "<br/>Password Strength: <span id=passwordStrength> -- </span>";
echo "<br/> <span id=tips></span></p>";

echo "<p>Confirm Password:";
echo "<br/><input name='passwordConfirm', type='password' required> </p>";

echo "<p>Name:";
echo "<br/><input name='name', type='text' maxlength='32' required></p>";

echo "<p>UK Phone Number:";
echo "<br><input name='number' id='number' type='tel' value='+44' maxlength='13' oninput='addPrefix()'' required></p>";
echo "<input type='hidden' name='csrf_token' value='$csrfToken'>";

echo "<p>Security Question:<br/>";
echo "<select name='securityQuestion' required>";
echo "<option value='0'>" . $SECURITY_QUESTIONS[0] . "</option>";
echo "<option value='1'>" . $SECURITY_QUESTIONS[1] . "</option>";
echo "<option value='2'>" . $SECURITY_QUESTIONS[2] . "</option>";
echo "<option value='3'>" . $SECURITY_QUESTIONS[3] . "</option>";
echo "<option value='4'>" . $SECURITY_QUESTIONS[4] . "</option>";
echo "<option value='5'>" . $SECURITY_QUESTIONS[5] . "</option>";
echo "<option value='6'>" . $SECURITY_QUESTIONS[6] . "</option>";
echo "<option value='7'>" . $SECURITY_QUESTIONS[7] . "</option>";
echo "</select></p>";

echo "<p>Security Question Answer:";
echo "<br/><input name='securityAnswer', type='text' required> </p>";

echo "<p>Confirm Security Question Answer:";
echo "<br/><input name='securityAnswer2', type='text' required> </p>";

echo"
<br><button id=captchaButton class='g-recaptcha'
data-sitekey=" . CAPTCHA_SITEKEY . "
data-callback='onSubmit'
disabled='true'
action='registercheck.php'
data-action='verify'>Register</button>
</form>";


echo "<p><a href='index.php'>Return to Homepage</a></p>"








?>