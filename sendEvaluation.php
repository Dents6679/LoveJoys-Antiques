<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

//if user is not logged in, kick to homepage. 
if($_SESSION["authenticated"] != true){
    echo"<p>You are not logged in.</p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    echo"<p><a href=index.php>HomePage</a></p>";
    exit(0);
}


//start database connection
include("../private/config.php");
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);


// get session variables from Session ID
$sessionID = $_SESSION['authUser'];

$stmt = $conn->prepare("SELECT UserID FROM sessionTable WHERE SessionID=? LIMIT 1");
$stmt->bind_param("s", $sessionID);
$stmt->execute();
$result = $stmt->get_result();
//force user to log in again if session has expired.
if($result->num_rows == 0){
    echo "Your session has expired, please log in again.";
    echo "<p><a href='index.php'>Homepage.</a></p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    exit(0);
}
$row = $result->fetch_assoc();
$userID = $row['UserID'];



// Check CSRF Token
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || filter_var($_POST['csrf_token'], FILTER_SANITIZE_SPECIAL_CHARS) !== $_SESSION['csrf_token']) {
        // Invalid CSRF Token
        echo "Invalid CSRF Token";
        echo "<a href=index.php>Return to Homepage</a>";
        exit(0);
    }
}


//captcha stuff
$recaptchaSecretKey = CAPTCHA_SECRET;
$response = $_POST['g-recaptcha-response'];
$verifyRecaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecretKey}&response={$response}");
$recaptcha = json_decode($verifyRecaptcha, true);

//Captcha check, prevent form use if suspicious. 
if ($recaptcha['success'] != 1 || $recaptcha['score'] < 0.7 || $recaptcha['action'] != "verify") {
    
    echo "Our site has detected suspicious activity from your browser. Please try again later or use another browser.";
            echo "<div/> <a href='index.php'>Homepage</a>";
    exit(0);
}



//get veriables from web form, filter to prevent XSS
$itemName = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
$comments = filter_var($_POST['comments'], FILTER_SANITIZE_SPECIAL_CHARS);
$contactMethod = filter_var($_POST['contact_method'], FILTER_SANITIZE_SPECIAL_CHARS);
$image = $_FILES['image']; //need to do some sort of filtering here...




if($itemName == ""){
    echo "You have not provided an Item name.";
    echo "<p><a href=index.php>Return to dashboard</a></p>";
    exit(0);
}

if($comments == ""){
    echo "You have not provided an item description.";
    echo "<p><a href=index.php>Return to dashboard</a></p>";
    exit(0);
}


// store image info in variables
$imgName = $image['name'];
$imgSize = $image['size'];
$imgTmpName = $image['tmp_name'];
$imgError = $image['error'];

//if generic error occurs, ask for reupload
if($imgError != 0){
    echo "An error has occurred with your file upload. Please try again.";
    echo "<p><a href=index.php>Return to dashboard</a></p>";
    exit(0);
}

//prevent large files over 5MB from being uploaded
if($imgSize > 5000000){
    echo "Your file is too large, please use a file under 5MB";
    echo "<p><a href=index.php>Return to dashboard</a></p>";
    exit(0);
}


//checking uploaded file actually is an image (jpg jpeg png).
$imgExtension = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
$allowedExtensions = array("jpg", "jpeg", "png");
if(!in_array($imgExtension, $allowedExtensions)){
    echo "You have uploaded an unsupported file type. (Supported File Types: JPG, JPEG, PNG)";
    echo "<p><a href=index.php>Return to dashboard</a></p>";
    exit(0);
}


//statement to check current time vs time of user's last upload.

//prepare query
$stmt = $conn->prepare("SELECT * FROM evaluationRequests WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows > 0) {
    // Fetch the row
    $evalRow = $result->fetch_assoc();
    // Get the current date and time
    $currentTime = new DateTime('now');
    

    // Convert Timestamp to DateTime object
    $storedDateTime = new DateTime($evalRow['Timestamp']);

    // Calculate the difference between the two timestamps
    $difference = $currentTime->diff($storedDateTime);

    if ($difference->i < 5) {
    // Less than 5 minutes have passed
    echo "Please wait 5 minuts between submitting Evaluation Requests.";
    echo "<p><a href=index.php>Homepage.</a></p>";
    exit(0);
    } 
}



//give image new unique name and move it to uploads folder on DB
$newImageName = uniqid("IMG-",true). '.' .$imgExtension;
$imgUploadPath = "./uploads/" .$newImageName;
move_uploaded_file($imgTmpName, $imgUploadPath);

//Create Unique item ID (no incrementing!)
$uniqueEvalID = uniqid();

//Insert evaluation into evalRequests.
$stmt = $conn->prepare("INSERT INTO evaluationRequests (RequestID, UserID, ObjectName, Details, ContactPreference, Image) VALUES (?, ?, ?, ?, ?, ?)");
//bind parameters
$stmt->bind_param("ssssss", $uniqueEvalID, $userID, $itemName, $comments, $contactMethod, $imgUploadPath);

// execute statement
if ($stmt->execute()) {

    echo "Your Evaluation request has been sent to our experts.";
    echo "<br><a href=index.php>Dashboard</a>";
} else {
    echo "Error, Evaluation Request Failed.";
    echo "<br><a href=index.php>Dashboard</a>";
}

// Close statement
$stmt->close();


// Close database connection
$conn->close();

?>