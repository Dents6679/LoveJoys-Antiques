<?php

//start database connection
include("../private/config.php");

$SECURITY_QUESTIONS =  ['What is the name of the primary school you attended?',
                            'What was the name of your favourite stuffed animal?',
                            'Where did you have your first kiss?',
                            'Where did your parents meet?',
                            'What was your favourite subject in school?',
                            'What was the name of your favourite teacher in secondary school?',
                            'What was the street name of your secondary school',
                            'What was the name of your childhood pet.'];

function resetLoginAttempts($ip, $conn) {

    $stmt = $conn->prepare("DELETE FROM loginAttempts WHERE IP = ?");

    $stmt->bind_param("s", $ip);

    if ($stmt->execute()) {
       
    } else {
        echo "Error deleting row: " . $stmt->error;
    }
    
}

function insertNewLoginAttempt($ip, $conn) {
    
    //generate ID
    $id = uniqid();
    $sql = "INSERT INTO loginAttempts (ID, IP, Attempts) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $id, $ip);
    if (!$stmt->execute()) {
        echo "Error inserting row: " . $stmt->error;
    }
}

function incrementLoginAttempt($ip, $conn) {
    
    $updateSql = "UPDATE loginAttempts SET Attempts = Attempts + 1 WHERE IP = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("s", $ip);
    if ($updateStmt->execute()) {
    } else {
        echo "Error updating row: " . $stmt->error;
    }
}


function checkLoginAttempts($ip, $maxAttempts, $waitTime, $conn) {
    
    
    //query databse for user IP
    $sql = "SELECT Attempts, LastAttempt FROM loginAttempts WHERE IP = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->bind_result($attempts, $lastAttempt);
    $stmt->fetch();
    $stmt->close();
    //close prepared statement
  
    //if no attempts have been made, insert new attempt.
    if ($attempts == null) {
        // If no record exists, insert a new one
        insertNewLoginAttempt($ip, $conn);
        return true;
    }
    
     //get current date
    $currentTimeStamp = new DateTime('now');
    
    //DB timestamp to DateTime object
    $storedDateTime = new DateTime($lastAttempt);

    // calculate difference
    $difference = $currentTimeStamp->getTimeStamp() - $storedDateTime->getTimeStamp();
    

 
    
    
    if($difference > $waitTime){
       
        resetLoginAttempts($ip, $conn);
        insertNewLoginAttempt($ip, $conn);
        return true;
    }
    
//need to add reset functionality from time
    if ($attempts < $maxAttempts) {
        // under threshold, increment
        incrementLoginAttempt($ip, $conn);
        return true;
    } else {
        //beyond threshold, Return False.
        return false;
    }
} 
        


function purgeSessions($conn){
    
    $stmt = $conn->prepare("DELETE FROM sessionTable WHERE UserID=?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    
    
}


?>