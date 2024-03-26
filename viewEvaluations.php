<?php
session_start();

include("../private/config.php");
include("../private/uploads");


//if user is not logged in, kick to homepage. 
if($_SESSION["authenticated"] != true){
    echo"<p>You are not logged in.</p>";
    unset($_SESSION['authUser']);
    unset($_SESSION['authenticated']);
    echo"<p><a href=index.php>HomePage</a></p>";
    exit(0);
}


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

$stmt->close();

$stmt = $conn->prepare("SELECT IsAdmin FROM userTable WHERE ID=? LIMIT 1");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$admin = $row['IsAdmin'];

$stmt->close(); 

//end of getting session info

//if user is not logged in, kick to homepage. 
if($admin != 1)
{
    echo"<p>You do not have access to this page..</p>";
    echo"<p><a href=index.php>HomePage</a></p>";
    exit(0);
}


//start database connection
include("../private/config.php");
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

//check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//select statement
$sql = "SELECT evaluationRequests.RequestID, evaluationRequests.ObjectName, evaluationRequests.Details, evaluationRequests.Image, userTable.Name, evaluationRequests.ContactPreference, userTable.Email, userTable.ContactNumber, evaluationRequests.Timestamp 
        FROM evaluationRequests 
        INNER JOIN userTable ON evaluationRequests.UserID = userTable.ID 
        ORDER BY Timestamp DESC";

$stmt = $conn->prepare($sql);

if ($stmt->execute() === false) {
    echo "Error in executing SQL statement: " . $stmt->error;
}

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        table {
  border-collapse: collapse; 
  width: 100%; 
}

th, td {
  border: 1px solid #dddddd; 
  text-align: left; 
  padding: 8px; 
}

th {
  background-color: #f2f2f2; 
  padding: 12px; 
}

img {
  max-width: 200px; 
  max-height: 300px; 
}
        
        
    </style>
</head>
<body>

<h1>Evaluation requests</h1>
<p><a href=index.php><button>Return to Homepage</button></a></p>

<?php




if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Evaluation ID</th><th>Object Name</th><th>Object Details</th><th>Contact via...</th><th>Name</th><th>Time of Request</th><th>Image</th></tr>";




    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["RequestID"] . "</td>";
        echo "<td>" . $row["ObjectName"] . "</td>";
        echo "<td>" . $row["Details"] . "</td>";
        
        if($row["ContactPreference"] == "Email"){
            echo "<td>" . $row["Email"] . "</td>";
        }
        if($row["ContactPreference"] == "Phone"){
             echo "<td>" . $row["ContactNumber"] . "</td>";
        }
        echo "<td>" . $row["Name"] . "</td>";
        echo "<td>" . $row["Timestamp"] . "</td>";
        
        echo "<td> <a href =" . $row["Image"] . "><img src='" . $row["Image"] . "'></a></td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "No results found.";
}
?>

<p><a href=index.php><button>Return to Homepage</button></a></p>

</body>
</html>

<?php
//close connection
$conn->close();
?>