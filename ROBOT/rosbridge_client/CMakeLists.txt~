cmake_minimum_required(VERSION 2.8.3)
project(rosbridge_client)

find_package(catkin REQUIRED COMPONENTS rosbridge_library rosapi rospy)

catkin_python_setup()

catkin_package()

install(PROGRAMS
  scripts/client.py
  scripts/client
  DESTINATION ${CATKIN_PACKAGE_BIN_DESTINATION}
)

install(FILES
  launch/rosbridge_websocket.launch
  launch/rosbridge_tcp.launch
  DESTINATION ${CATKIN_PACKAGE_SHARE_DESTINATION}/launch
)

