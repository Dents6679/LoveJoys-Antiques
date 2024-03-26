<?php

$code = $_POST['code'];
$number = $_POST['number'];
include "report-verification.php";

echo tryVerification($number, $code);



?>