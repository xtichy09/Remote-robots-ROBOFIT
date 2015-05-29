#!/usr/bin/python

from autobahn.twisted.websocket import WebSocketServerProtocol, \
				       WebSocketServerFactory
import sys
from twisted.python import log
from twisted.internet import reactor

from autobahn.twisted.websocket import WebSocketClientProtocol, \
                                       WebSocketClientFactory
from json import dumps
from ws4py.client.threadedclient import WebSocketClient


#Trieda Client
#ID - login
#getway - webinterface for websockets
class Client:
	
	def __init__(self , getway, login):
		self.getway = getway
		self.ID = login

	def getGetway(self):
		return self.getway

	def getID(self):
		return self.ID
  

class Robot:
	
	def __init__(self, login, name, getway, workflag):
		self.name = name
		self.ID = login
		self.getway = getway
		self.workflag = workflag

	def setWork(self, flag):
		self.workflag = flag

	def getGetway(self):
		return self.getway

	def work(self):
		return self.workflag

	def getID(self):
		return self.ID

	def getName(self):
		return self.name	


class Session:
	
	robots = []
	clients = []
	bridgeGetway = []
	streamGetway = []
	streamPort = -1
	bridgePort = -1
	streamPID = -1

	def __init__(self, client, robot, bridgePID):
		self.robots.append(robot)
		self.clients.append(client)
		self.bridgePID = bridgePID

	def addRobot(self, robot):
		self.robots.append(robot)

	def removeRobot(self, robot):
		self.robots.remove(robot)

	def addClient(self, client):
		self.clients.append(client)

	def removeClient(self, client):
		self.clients.remove(client)

	def addBridgeGetway(self, getway):
		self.bridgeGetway.append(getway)

	def addStreamGetway(self, getway):
		self.streamGetway.append(getway)

	def saveStreamPort(self, port):
		self.streamPort = port

	def saveBridgePort(self, port):
		self.bridgePort = port

	def addStreamPID(self, PID):
		self.streamPID = PID

	def getBridgePort(self):
		return self.bridgePort

	def getStreamPort(self):
		return self.streamPort

	def getBridgeGetway(self):
		return self.bridgeGetway[0]

	def getStreamGetway(self):
		return self.streamGetway[0]

	def getRobots(self):
		return self.robots

	def getClients(self):	
		return self.clients
	
	def getBridgePID(self):
		return self.bridgePID.pid

	def getStreamPID(self):
		return self.streamPID.pid

	def removeStream(self):
		self.streamPID = -1
		self.streamPort = -1

class Online:
	
	def __init__(self, robot, session):
		self.robot = robot
		self.session = session

	def getSession(self):
		return self.session

	def getRobot(self):
		return self.robot


	













