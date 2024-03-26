<?php

echo "<head>";


//script for checking password strength
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
                  // Initialize variables
                  var strength = 0;
                  var tips = 'Follow these tips for a more secure password:  ';
                
                  //check password isn't a common password
                 if(commonPasswords.includes(password.toLowerCase())){
                   strength -= 10;
                   tips += 'Your password is too common. ';
                 }
                
                  // Check password length
                  if (password.length < 6) {
                    strength -= 10;
                    tips += 'Your password is too short. ';
                  } 
                  
                  if (password.length < 12) {
                    tips += 'Make your password longer. ';
                  } else {
                    strength += 1;
                  }
                
                  // Check for mixed case
                  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                    strength += 1;
                  } else {
                    tips += 'Use both lowercase and uppercase letters. ';
                  }
                
                  // Check for numbers
                  if (password.match(/\d/)) {
                    strength += 1;
                  } else {
                    tips += 'Include at least one number. ';
                  }
                
                  // Check for special characters
                  if (password.match(/[^a-zA-Z\d]/)) {
                    strength += 1;
                  } else {
                    tips += 'Include at least one special character. ';
                  }
                
                  // Return results
                  
                  // Get the paragraph element
                  var strengthElement = document.getElementById('passwordStrength');
                  var tipsElement = document.getElementById('tips');
                  var registerButton = document.getElementById('regBtn');
                  
                
                  // Return results
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
echo "<h1> Reset Password </h1>";


//get email from link
if(isset($_GET['email'])){
    $emailInput = $_GET['email'];
}
//get email from link
if(isset($_GET['token'])){
    $tokenInput = $_GET['token'];
}

//Password reset form
echo "<form action='resetPassword.php' method='POST'";
echo "<br>";
echo "<input type='hidden' name='passwordToken' value ='" . $tokenInput . "'>";
echo "Email:";
echo "<br/>";
echo "<input name='email' readonly value='" . $emailInput . "' type='email' maxlength='48' required>";
echo "<br/><br/>";
echo "New Password:<br/>";
echo "<input name='password', type='password' oninput='checkPasswordStrength(this.value)' required><br/>";

echo "Password Strength: <span id=passwordStrength> -- </span>";
echo "<br/> <span id=tips></span><br/>";
echo "<br> Confirm New Password:<br/>";
echo "<input name='password2', type='password' required><br/>";
echo "<br>";

echo "<br> <input type='submit' id='regBtn' disabled='true' name='passwordUpdate' value='Update Password'>";
echo "</form>";
echo "</body>";
?>
