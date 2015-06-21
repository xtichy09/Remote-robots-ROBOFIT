<?php

session_start();
echo "<label id='login' style='visibility:hidden;'>".$_SESSION["login"]."</label>";

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="http://code.jquery.com/jquery-2.1.1.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
  </head>

  <style>
	.table-hover>tbody>tr:hover>td, .table-hover>tbody>tr:hover>th {
  		background-color: #CFCFD0;
  		color: black;
	}
	body {
	  padding-top: 50px;
	}

	.sub-header {
	  padding-bottom: 10px;
	  border-bottom: 1px solid #eee;
	}

	.navbar-fixed-top {
	  border: 0;
	}

	.sidebar {
	  display: none;
	}
	@media (min-width: 768px) {
	  .sidebar {
	    position: fixed;
	    top: 51px;
	    bottom: 0;
	    left: 0;
	    z-index: 1000;
	    display: block;
	    padding: 20px;
	    overflow-x: hidden;
	    overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
	    background-color: #f5f5f5;
	    border-right: 1px solid #eee;
	  }
	}

	/* Sidebar navigation */
	.nav-sidebar {
	  margin-right: -21px; /* 20px padding + 1px border */
	  margin-bottom: 20px;
	  margin-left: -20px;
	}
	.nav-sidebar > li > a {
	  padding-right: 20px;
	  padding-left: 20px;
	}
	.nav-sidebar > .active > a,
	.nav-sidebar > .active > a:hover,
	.nav-sidebar > .active > a:focus {
	  color: #fff;
	  background-color: #428bca;
	}

	.main {
	  padding: 20px;
	}
	@media (min-width: 768px) {
	  .main {
	    padding-right: 40px;
	    padding-left: 40px;
	  }
	}
	.main .page-header {
	  margin-top: 0;
	}

	.placeholders {
	  margin-bottom: 30px;
	  text-align: center;
	}
	.placeholders h4 {
	  margin-bottom: 0;
	}
	.placeholder {
	  margin-bottom: 20px;
	}
	.placeholder img {
	  display: inline-block;
	  border-radius: 50%;
	}

  </style>

  <body>
  	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Robo@FIT</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul id="top-dash" class="nav navbar-nav navbar-right">
            <li><a href="#"></a></li>
            <li><a href="registration.php">Settings</a></li>
            <li><a href="#">Help</a></li>
            <li><a href="#">Log out</a></li>
          </ul>
          <form class="navbar-form navbar-right">
            <input type="text" class="form-control" placeholder="Search...">
          </form>
        </div>
      </div>
    </nav>



    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Login</th>
                <th>Access</th>
                <th>Work</th>
              </tr>
            </thead>
            <tbody id="table_body">
            </tbody>
          </table>
        </div>   
    </div> 
  </div>

	<script>

	$( document ).ready(function() {

		 document.getElementById("top-dash").getElementsByTagName("li")[0].getElementsByTagName("a")[0].innerHTML = document.getElementById('login').innerHTML;


		 var socket = null;
         var isopen = false;
         var comScoket = null;
         var comisopen = false;

         window.onload = function() {

            socket = new WebSocket("ws://37.205.11.196:9000");
            socket.binaryType = "arraybuffer";

            socket.onopen = function() {
               console.log("socket: Connected!");
               isopen = true;
               sendRegistration(document.getElementById('login').innerHTML);
            }

            socket.onmessage = function(e) {
               if (typeof e.data == "string") {
                  console.log("socket: Text message received: " + e.data);

                  var parseData = e.data.split('|');

                  if(parseData[0] == 'CONNECTION:PORT'){
                  	 //TODO access
                     redirectPage('xrobot00', 'xtichy09', 4, parseData[1]);
                  }else if(parseData[0] == 'REGISTRATION:OK'){
                  	sendOnline(document.getElementById('login').innerHTML);
                  }else if(parseData[0] == 'ONLINEROBOTS:OK'){
                  	 printTable(parseData[1]);
                  }else if(parseData[0] == 'STARTWORKING:OK'){
                  	//Kolecko
                  	console.log("CAKAM NA PROCESS...")
                  }
               } else {
                  var arr = new Uint8Array(e.data);
                  var hex = '';
                  for (var i = 0; i < arr.length; i++) {
                     hex += ('00' + arr[i].toString(16)).substr(-2);
                  }
                  console.log("Binary message received: " + hex);
               }
            }

            socket.onclose = function(e) {
               console.log("socket: Connection closed.");
               socket = null;
               isopen = false;
            }
         };

         function sendOnline(login) {
            if (isopen) {
               socket.send("ONLINEROBOTS:WEBCLIENT|" + login);
               console.log("socket: Text message sent.");               
            } else {
               console.log("socket: Connection not opened.")
            }
         };

	     function sendRegistration(login) {
            if (isopen) {
               socket.send("REGISTRATION:WEBCLIENT|" + login);
               console.log("socket: Text message sent.");               
            } else {
               console.log("socket: Connection not opened.")
            }
         };

         function sendProcess(robot, login, access) {
            if (isopen) {
               socket.send("STARTWORKING:WEBCLIENT|" + login + "|" + robot + "|" + access);
               console.log("socket: Text message sent.");               
            } else {
               console.log("socket: Connection not opened.")
            }
         };


        function printTable(data){
        	obj = JSON.parse(data);

			for(i = 0; i < obj.length; i++){
				window.robot = obj[i].robot;
				window.access = obj[i].access;
				var row = document.createElement("tr");
				var col = document.createElement("td");
				col.appendChild(document.createTextNode(obj[i].robotID));
				row.appendChild(col);
				var col = document.createElement("td");
				col.appendChild(document.createTextNode(obj[i].name));
				row.appendChild(col);
				var col = document.createElement("td");
				col.appendChild(document.createTextNode(obj[i].robot));
				row.appendChild(col);

				var col = document.createElement("td");
				var span = document.createElement("span");

				if(parseInt(obj[i].access) == 0){
					span.className = 'label label-danger';
					span.appendChild(document.createTextNode("Access denied"));
				}
				else if(parseInt(obj[i].access) > 0 && parseInt(obj[i].access) < 4){
					span.className = 'label label-warning';
					span.appendChild(document.createTextNode("Review only"));
				}
				else{
					span.className = 'label label-success';
					span.appendChild(document.createTextNode("Full access"));
				}

				col.appendChild(span);
				row.appendChild(col);

				var col = document.createElement("td");
				var span = document.createElement("span");

				if(obj[i].workflag == "False"){
					span.className = 'label label-success';
				}
				else{
					span.className = 'label label-danger';
				}

				if(obj[i].workflag == "False" && parseInt(obj[i].access) > 0){
					row.onclick = function() {sendProcess(window.robot, document.getElementById('login').innerHTML, window.access)};
				}

				span.appendChild(document.createTextNode(obj[i].workflag));
				col.appendChild(span);
				row.appendChild(col);
				document.getElementById("table_body").appendChild(row);
			}
         };

		function redirectPage(robot, login, access, port) {
    			var url = 'workdashboard.php/' + robot;
    			var robot_input = '<input type="text" name="robot" value="' + robot + '" />'
    			var login_input = '<input type="text" name="login" value="' + login + '" />'
    			var port_input = '<input type="text" name="port" value="' + port + '" />'
    			var access_input = '<input type="text" name="access" value="' + access + '" />'
				var form = $('<form style="visibility:hidden;" action="' + url + '" method="post">' + robot_input + login_input + port_input + access_input + '</form>');
				$('body').append(form);
				form.submit();
		};

	});
	</script>

   
  </body>
</html>

