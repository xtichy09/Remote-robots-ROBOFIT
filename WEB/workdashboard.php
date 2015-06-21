<?php

echo "WORKDASHBOARD";
echo "<br>";

echo $_POST["login"];
echo $_POST["robot"];
echo $_POST["access"];
echo $_POST["port"];
echo "<br>";

echo "<label id='login' style='visibility:hidden;'>".$_POST["login"]."</label>";
echo "<label id='robot' style='visibility:hidden;'>".$_POST["robot"]."</label>";
echo "<label id='access' style='visibility:hidden;'>".$_POST["access"]."</label>";
echo "<label id='port' style='visibility:hidden;'>".$_POST["port"]."</label>";


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <script src="http://code.jquery.com/jquery-2.1.1.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="http://cdn.robotwebtools.org/EventEmitter2/current/eventemitter2.js"></script>
    <!--<script src="http://cdn.robotwebtools.org/roslibjs/current/roslib.min.js"></script>-->
    <script src="/js/roslib.js" type="text/javascript"></script>
    <!--<script src="roslib.js" type="text/javascript"></script>-->
    <title>Webportal for ground robots</title>
  </head>
  <script type="text/javascript">


      function sendMessage(){

          window.cmdVel = new ROSLIB.Topic({
            ros : window.ROS,
            name : '/uinode/cmd_vel',
            messageType : 'uinode_pkg/Cmdvel'
          });


          var twist = new ROSLIB.Message({
            linear_x : 10,
            angular_z: 10,
          }); 
        
          console.log(twist);

          var message = window.cmdVel.publish(twist);
          console.log(message);

        }

        function getTopics() {
            var topicsClient = new ROSLIB.Service({
            ros : window.ROS,
            name : '/rosapi/topics',
            serviceType : 'rosapi/Topics'
            });

            var request = new ROSLIB.ServiceRequest();

            topicsClient.callService(request, function(result) {
            console.log("Getting topics...");
            console.log(result.topics[1]);

              for(i = 0; i < result.topics.length; i++){
                console.log("Getting topic!!!");
                var row = document.createElement("p");
                row.appendChild(document.createTextNode(result.topics[i]));
                document.getElementById("topics").appendChild(row);
              }
            });
        };

  </script>


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
            <li><a href="#">Setting</a></li>
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
        <div class="col-sm-9 blog-main show-grid">
          <row>
            <div class="col-sm-12 blog-post">
              <p><button onclick='getTopics();'>Topics</button></p>
              <p><button onclick='sendMessage();'>Message</button></p>
            </div>
              <div class="col-sm-12 input">
                <div class="input-group">
                  <input class="form-control" type="text">
                  <div class="input-group-btn">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Action <span class="caret"></span></button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                      <li><a href="#">Action</a></li>
                      <li><a href="#">Another action</a></li>
                      <li><a href="#">Something else here</a></li>
                      <li class="divider"></li>
                      <li><a href="#">Separated link</a></li>
                    </ul>
                  </div><!-- /btn-group -->
                </div><!-- /input-group -->
              </div>
          </row>
        </div>
        <div class="col-sm-3 blog-sidebar show-grid">
          <div class="info">Info</div>
          <p>Toad</p>
          <p>Hydro</p>
          <p>4 - Full access</p>
          <p>IP, PORT ...</p>
          <hr></hr>
          <div class="ping">Ping</div>
          <p>15 ms</p>
          <p>20 ms</p>
          <p>35 ms</p>
          <hr></hr>
          <p id="topics">Topics</p>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12 blog-message show-grid">
          <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <div class="panel panel-primary">
                        <div class="panel-body">
                            <ul id="chat" class="chat">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>  
      </div>
    </div>
  </body>


  <script>
    $( document ).ready(function() {
      //comSocket = new WebSocket('ws://localhost:' + document.getElementById('port').innerHTML);
      //comSocket.binaryType = "arraybuffer";

      document.getElementById("top-dash").getElementsByTagName("li")[0].getElementsByTagName("a")[0].innerHTML = document.getElementById('login').innerHTML;

 
      var comisopen = false;
      window.isRegistred = false;

        console.log("OK");

        var url = 'ws://37.205.11.196:' + document.getElementById('port').innerHTML;

        window.ROS = new ROSLIB.Ros({
            url : url
          });

        window.ROS.ping();
        

      /**

      comSocket.onopen = function() {
        console.log("comScoket: Connected!");
        comisopen = true;
        sendComRegistration(document.getElementById('login').innerHTML);
        ping();
      }

      comSocket.onmessage = function(m) {
        console.log("comSocket: Text message received: " + m.data);

          var parseData = m.data.split('|');

          if(parseData[0] == 'REGISTRATION:OK'){
            var message = '{"service": "/rosout/get_loggers", "op": "call_service"}';
            sendMessage(message);
          }
      }

      comSocket.onclose = function(m) {
        console.log("comSocket: Connection closed.");
        comSocket = null;
        comisopen = false;
      }

      function sendComRegistration(login) {
        if (comisopen) {
          comSocket.send("REGISTRATION:WEBCLIENT|" + login);
          console.log("comSocket: Text message sent.");               
        } else {
          console.log("comSocket: Connection not opened.")
        }
      };

      function ping() {
        if (comisopen) {
          comSocket.send("PING:WEBCLIENT");
          //comSocket.send("PING:SERVER");
          //comSocket.send("PING:ROBOT");
          console.log("comSocket: Text message sent.");               
        } else {
          console.log("comSocket: Connection not opened.")
        }
      };

      function sendMessage(message) {
        if (comisopen) {
          comSocket.send('MESSAGE:WEBCLIENT|' + message);
          console.log("comSocket: Text message sent.");               
        } else {
          console.log("comSocket: Connection not opened.")
        }
      };
      */
    });


  </script>

  <style type="text/css">

    .blog-sidebar{
      height: 570px;
    }

    .blog-message{
      height: 220px;
    }

    .blog-main{
      height: 570px;
    }

    .show-grid {
        border: 1px solid;
      }

    .row + .row {
      border-top:0 ;
     }
     .input {
        margin-bottom: 6px;
     };

     .blog-main + .blog-sidebar{
      border-left: 0;
     }
     .blog-post {
       margin-bottom: 6px;
       height: 500px;
      }
    .blog-main {
      font-size: 18px;
      line-height: 1.5;
      }
    @media (min-width: 1200px) {
     .container {
      width: 1150px;
    }
}

  .chat
{
    list-style: none;
    margin: 0;
    padding: 0;
}

.chat li
{
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px dotted #B3A9A9;
}



.chat li.left .chat-body
{
    margin-left: 25px;
}

.chat li.right .chat-body
{
    margin-right: 25px;
}


.chat li .chat-body p
{
    margin: 0;
    color: #777777;
}

.panel .slidedown .glyphicon, .chat .glyphicon
{
    margin-right: 5px;
}

.panel-body
{
    overflow-y: scroll;
    height: 215px;
}

::-webkit-scrollbar-track
{
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
    background-color: #F5F5F5;
}

::-webkit-scrollbar
{
    width: 12px;
    background-color: #F5F5F5;
}

::-webkit-scrollbar-thumb
{
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
    background-color: #555;
}
  </style>

</html>

