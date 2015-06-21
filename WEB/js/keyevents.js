

function setKeys(){
  var listener = new keypress.Listener();  
  var my_scope = this;
  var my_combos = listener.register_many([
    {
        "keys"          : "w",
        "is_exclusive"  : true,
        "prevent_repeat": true,
        "on_keydown"    : function up(){handleKey(3, true);},
        "on_keyup"      : function up_up(){handleKey(5, true);},
        "this"          : my_scope
    },
    {
        "keys"          : "a",
        "is_exclusive"  : true,
        "prevent_repeat": true,
        "on_keydown"    : function leeft(){handleKey(1, true);},
        "on_keyup"      : function leeft_up(){handleKey(5, true);},
        "this"          : my_scope
    },
        {
        "keys"          : "d",
        "is_exclusive"  : true,
        "prevent_repeat": true,
        "on_keydown"    : function riight(){handleKey(2, true);},
        "on_keyup"      : function riight_up(){handleKey(5, true);},
        "this"          : my_scope
    },
        {
        "keys"          : "x",
        "is_exclusive"  : true,
        "prevent_repeat": true,
        "on_keydown"    : function down(){handleKey(4, true);},
        "on_keyup"      : function down_up(){handleKey(5, true);},
        "this"          : my_scope
    },
        {
        "keys"          : "s",
        "is_exclusive"  : true,
        "prevent_repeat": true,
        "on_keydown"    : function stopp(){handleKey(5, true);},
        "on_keyup"      : function stopp_up(){handleKey(5, true);},
        "this"          : my_scope
    },
]);
  
}


function handleKey(code, down) {
  
  var x = 0;
  var z = 0;
  var pi = 3.14159265359;
  
  if(code == 1){
    var angular = $( "#slider-radian" ).slider( "value" );
    var radians = angular * (pi/180);
    z = radians;
  }
  else if(code == 2){
    var angular = $( "#slider-radian" ).slider( "value" );
    var radians = angular * (pi/180);
    z = -radians;
  } 
  else if(code == 3){
    var linear = $( "#slider-fast" ).slider( "value" );
    x = linear;
  }
  else if(code == 4){
    var linear = $( "#slider-fast" ).slider( "value" );
    x = -linear;
  }
  else if(code == 5){
    x = 0;
  }
  
  console.log("Linear:" + x); 
  console.log("Angular:" + z);
  
  
  var twist = new ROSLIB.Message({
    linear_x : x,
    angular_z: z,
    }); 
    window.cmdVel.publish(twist);
};

function setSliders(){
  $(function() {
  $( "#slider-radian" ).slider({
    range: "min",
    value: 30,
    min: 1,
    max: 90,
    slide: function( event, ui ) {
      $( "#radian" ).val( ui.value + " [°/s]");
      }
    }); 
  $( "#radian" ).val( $( "#slider-radian" ).slider( "value" ) + " [°/s]");
  });
    
  $(function() {
    $( "#slider-fast" ).slider({
      range: "min",
      value: 0.10,
      min: 0.01,
      max: 1,
      step: 0.01,
      slide: function( event, ui ) {
        $( "#fast" ).val( ui.value + " [m/s]");
        }
      });
    $( "#fast" ).val($( "#slider-fast" ).slider( "value" ) + " [m/s]");
  }); 
}


  function init() {

    setKeys();

    window.cmdVel = new ROSLIB.Topic({
           ros : window.ROS,
           name : '/uinode/cmd_vel',
           messageType : 'uinode_pkg/Cmdvel'
    });

  }