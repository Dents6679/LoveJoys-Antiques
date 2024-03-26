<?php

session_start();
// Generate and store CSRF token in session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

echo "<p>Please Enter your Email address to reset your password.</p>";

echo "<form action='resetPassword.php' method='POST'>";
echo "<input name='email', type='email' maxlength='48' required>";
echo "<br><br> <input type='submit' name='passwordRequest' value='Reset Password'>";
echo "<input type='hidden' name='csrf_token' value='$csrfToken'>"; //pass csrf token to login checker to prevent CSRF attacks
echo "</form>";
echo "<br/> <a href=index.php> Homepage </a>";
?>