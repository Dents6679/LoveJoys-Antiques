<?php
//start user session
session_start();

//move user to dashboard if already logged in.
if($_SESSION['authenticated'] == TRUE){
   header("location:dashboard.php") ;
}

//display login/signup page.
else {echo "
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
      
    <title>Lovejoy's Antiques</title>
    
</head>
<body>

    <h1>Lovejoy's Antiques</h1>
    <p><a href='login.php'><button>Login</button></a></p>
    <p><a href='register.php'><button>Register</button></a></p>

</body>
</html>
";
}
?>