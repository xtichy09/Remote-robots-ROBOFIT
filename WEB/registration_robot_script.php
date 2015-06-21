<?php

session_start();
/*echo "<label id='login' style='visibility:hidden;'>".$_SESSION["login"]."</label>";*/


echo $_POST['inputName']."\n";
echo $_POST['inputLogin']."\n";
echo $_POST['inputLoginID1']."\n";
echo $_POST['inputPermissionID1']."\n";
echo $_POST['inputLoginID2']."\n";
echo $_POST['inputPermissionID2']."\n";


/* id_robot  jmeno   login   ros_v   ip  port_rosbridge  port_mjpeg_server   def_position_x  def_position_y */
$DB = mysqli_connect("127.0.0.1","root","athlon","robots");

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$sql = 'INSERT INTO robot '.
       '(jmeno, login, ros_v, ip, port_rosbridge, port_mjpeg_server, def_position_x, def_position_y) '.
       'VALUES ("'.$_POST[inputName].'", "'.$_POST[inputLogin].'", "Hydro", "192.168.0.0", 8080, 8080, 500, 500)';


if ($DB->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $DB->error;
}


$MySQL = 'SELECT id_robot FROM robot WHERE login="'.$_POST["inputLogin"].'"';
$result = mysqli_query($DB, $MySQL);
$row = mysqli_fetch_array($result);
$id_robot = $row['id_robot'];
echo $row['id_robot'].'\n';

/* id_spojenie   fk_id_uzivatel  fk_id_robot   login_uzivatel  login_robot   access  name */

$MySQL = 'SELECT id_uzivatel FROM uzivatel WHERE login="'.$_POST["inputLoginID1"].'"';
$result = mysqli_query($DB, $MySQL);
$row = mysqli_fetch_array($result);
$id_uzivatel = $row['id_uzivatel'];
echo $row['id_uzivatel'].'\n';

$sql = 'INSERT INTO spojenie '.
       '(fk_id_uzivatel, fk_id_robot, login_uzivatel, login_robot, access, name) '.
       'VALUES ("'.$id_uzivatel.'", "'.$id_robot.'", "'.$_POST['inputLoginID1'].'", "'.$_POST['inputLogin'].'",'.$_POST['inputPermissionID1'].', "'.$_POST['inputName'].'")';

if ($DB->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $DB->error;
}


$DB->close();

/* Redirect visitor to the thank you page */
/*header('Location: http://address-of-confirmation-page.html');*/
/*exit();*/

?>