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
    <link rel="stylesheet" type="text/css" href="/css/slider.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script src="/js/bootstrap-slider.js" type="text/javascript"></script>
  </head>

  <style>
	.table-hover>tbody>tr:hover>td, .table-hover>tbody>tr:hover>th {
  		background-color: #CFCFD0;
  		color: black;
	}
	body {
	  padding-top: 50px;
	}

  * {
  .border-radius(0) !important;
  }

  #field {
    margin-bottom:20px;
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

  #ex1Slider .slider-selection {
  background: #BABABA;
}

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
            <li><a href="#">DRUHA</a></li>
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
    <form name="contactform" method="post" action="registration_robot_script.php" class="form-horizontal" role="form">
      <div class="row">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-addon">Name</span>
            <input type="text" class="form-control" name="inputName" aria-label="s">
            <span class="input-group-addon">[TODO check/false]</span>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-addon">Login</span>
            <input type="text" class="form-control" name="inputLogin" aria-label="s">
            <span class="input-group-addon">[TODO check/false]</span>
          </div>
        </div>
      </div>

      <div id="field1">
        <div class="row">
          <div class="col-md-8">
            <div class="input-group">
              <span class="input-group-addon">User permissions</span>
              <input type="text" class="form-control" name="inputLoginID1" aria-label="s">
              <span class="input-group-addon">
              <input id="ex1" data-slider-id='ex1Slider' type="text" name="inputPermissionID1" data-slider-min="0" data-slider-max="4" data-slider-step="1" data-slider-value="-1"/></span>
            </div>
            <button id="add-user" class="btn" type="button">+</button>
          </div>
        </div>
      </div>
      <button type="submit" class="btn btn-default">Send Message</button>
    </form>
  </div>

	<script>

	$( document ).ready(function() {

		document.getElementById("top-dash").getElementsByTagName("li")[0].getElementsByTagName("a")[0].innerHTML = document.getElementById('login').innerHTML;


    $('#ex1').slider({
      tooltip: 'always',
      formatter: function(value) {
        return 'Current value: ' + value;
      }
    });


    var next = 1;
    $("#add-user").click(function(e){

      e.preventDefault();
      var addto = "#field" + next;
      var addRemove = "#field" + (next + 1);
      next = next + 1;
      var newIn = '<div id="field' + next + '"><div class="row"><div class="col-md-8"><div class="input-group"><span class="input-group-addon">User permissions</span>';
      newIn += '<input type="text" class="form-control" name="inputLoginID' + next + '" aria-label="s"><span class="input-group-addon">';
      newIn += '<input id="ex' + next + '" class="slider" data-slider-id="ex1Slider" type="text" name="inputPermissionID' + next + '" data-slider-min="0" data-slider-max="4" data-slider-step="1" data-slider-value="-1"/></span>';
      newIn += '<span class="input-group-addon"><button id="remove' + (next) + '" class="btn btn-xs btn-danger remove-me" >-</button></span></div></div></div></div>';
      var newInput = $(newIn);
      $(addto).after(newInput);
      $("#field" + next).attr('data-source',$(addto).attr('data-source')); 

      $('.remove-me').click(function(e){
        e.preventDefault();
        var fieldNum = this.id.charAt(this.id.length-1);
        var fieldID = "#field" + fieldNum;
        $(this).remove();
        $(fieldID).remove();
      });

      $('#ex' + next).slider({
        tooltip: 'always',
        formatter: function(value) {
        return 'Current value: ' + value;
      }
    });

    });

	});
	</script>
   
  </body>
</html>

