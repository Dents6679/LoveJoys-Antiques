<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "initiate-verification.php";

$number = $_POST['number'];

initiateVerification($number);

echo "<p>Please enter the code sent to " . $number . " into the box below.</p>";
echo "<form action='checkCode.php' method='POST'>";
echo "<input name='number' type='hidden' value='$number' required>";
echo "<input name='code' type='number' required>";
echo "<br><br> <input type='submit' name='2faSubmit' value='Send Passcode'>";
echo "</form>";
echo "<br/> <a href=index.php> Homepage </a>";





?>