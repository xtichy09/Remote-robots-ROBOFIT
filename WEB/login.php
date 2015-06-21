<?php

session_start();
// Create connection
$DB = mysqli_connect("127.0.0.1","root","athlon","robots");


// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$MySQL = 'SELECT heslo FROM uzivatel WHERE login="'.$_POST["login"].'"';
$result = mysqli_query($DB, $MySQL);

while($row = mysqli_fetch_array($result)) {
	if (!strcmp ( $_POST['password'] , $row['heslo'] )){
		$_SESSION["login"] = $_POST["login"];
		header("Location: table.php");
		die();	
	}
}

$DB->close();
//header("Location: login.html");
die();

?>
