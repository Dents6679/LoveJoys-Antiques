<?php
//get user info
session_start();

//start database connection
include("../private/config.php");

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

$sessionID = $_SESSION['authUser'];

$stmt = $conn->prepare("DELETE FROM sessionTable WHERE SessionID=?");
$stmt->bind_param("s", $sessionID);

$stmt->execute();

unset($_SESSION['authenticated']);
unset($_SESSION['authUser']);
$stmt->close();

//direct user to homepage.
header("location:index.php");



?>