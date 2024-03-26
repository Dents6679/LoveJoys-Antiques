<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Show error if token isn't set.
if (!isset($_GET['token'])) {
    // Handle case where 'token' is not set
    echo "A Token Error Occurred (token not set)";
    exit(0);
        
}

    
    include("../private/config.php");
    // Create new database connection
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection 
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get token value from URL
    $token = $_GET['token'];

    // Use prepared statement to prevent SQL injection
    $verifyQuery = $conn->prepare("SELECT VerificationToken, Verified FROM emailVerification WHERE VerificationToken=? LIMIT 1");
    $verifyQuery->bind_param("s", $token);
    $verifyQuery->execute();
    $verifyQuery->store_result();

    // Check if any rows returned by the query
    if ($verifyQuery->num_rows > 0) {
        
        $verifyQuery->bind_result($userToken, $verifyStatus);
        $verifyQuery->fetch();

        // Check if user's email is  unverified
        if ($verifyStatus == "0") {
            // Update using prepared statement to prevent SQL injection
            $one = 1;
            $updateQuery = $conn->prepare("UPDATE emailVerification SET Verified=? WHERE VerificationToken=? LIMIT 1");
            $updateQuery->bind_param("is", $one, $userToken);

            // Check if update was successful
            if ($updateQuery->execute()) {
                echo "Your account has been verified, please <a href='login.php'>log in</a>.";
                exit(0);
            } else {
                echo "Verification Failed (SQL Error)";
                exit(0);
            }
        } else {
            echo "Email Already Verified. Please <a href='login.php'>log in</a> here.";
            exit(0);
        }
    } else {
        echo "A Token Error Occurred (Token not found)";
    }
    
?>