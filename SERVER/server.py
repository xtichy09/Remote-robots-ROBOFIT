#!/usr/bin/env python


from autobahn.twisted.websocket import WebSocketServerProtocol, \
					   WebSocketServerFactory
import sys
import os
from twisted.python import log
from twisted.internet import reactor

from autobahn.twisted.websocket import WebSocketClientProtocol, \
									   WebSocketClientFactory
from json import dumps
from ws4py.client.threadedclient import WebSocketClient

from threading import Thread
import socket
import webportal
import json
import MySQLdb

from subprocess import Popen


Sessions = []
Robots = []
Clients = []


def getPort():
		for port in range(7000,8000):  
			sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
			result = sock.connect_ex(('localhost', port))
			if result != 0:
				sock.close()
				return port
			sock.close()


class MyServerProtocol(WebSocketServerProtocol):
	
	global database

	def onConnect(self, request):
		print("SERVER: Client connecting: {0}".format(request.peer))	

	def onOpen(self):
		'''self.Message = {'op': 'call_service', 'service': '/rosout/get_loggers'}
		self.sendMessage(dumps(self.Message))'''
		print("SERVER: Connection opened")

	def onMessage(self, payload, isBinary):
		
		if isBinary:
			print("SERVER: Binary message received: {0} bytes".format(len(payload)))
		else:
			print("SERVER: Text message received: {0}".format(payload.decode('utf8')))

		self.parseData = payload.split('|')

		if len(self.parseData) != 0:
			
			self.id = self.parseData[0].split(':')
			
			if self.id[1] == 'ROBOT':
				#Create object robot -> append to list Robots -> send message
				if self.id[0] == 'REGISTRATION':
					self.rob = webportal.Robot(self.parseData[1], self.parseData[2], self, False)
					Robots.append(self.rob)
					self.Message = 'REGISTRATION:OK|' + self.parseData[1]					
					self.sendMessage(self.Message.encode('utf8'))

				#Check if exist robot -> try to remove robot -> send message
				elif self.id[0] == 'UNREGISTRATION':
					flag = False
					for robot in Robots:
						if robot.getID == self.parseData[1]:
							Robots.remove(robot)
							flag = True
					if flag:
						self.Message = 'UNREGISTRATION:OK|' + self.parseData[1]
						self.sendMessage(self.Message.encode('utf8'))
					else:
						self.Message = 'UNREGISTRATION:FAIL|I cant search this robot' + self.parseData[1]
						self.sendMessage(self.Message.encode('utf8'))



			elif self.id[1] == 'WEBCLIENT':
				
				#Create object client -> append to list Clients -> send message
				if self.id[0] == 'REGISTRATION':
					self.client = webportal.Client(self, self.parseData[1])
					Clients.append(self.client)
					self.Message = 'REGISTRATION:OK|' + self.parseData[1]
					self.sendMessage(self.Message.encode('utf8'))

				#Check if exist client -> try to remove client -> send message
				if self.id[0] == 'LOGOUT':
					for ses in Sessions:
						if str(ses.getBridgePID()) == str(self.parseData[1]):
							ses.getRobots()[0].setWork(False)



				if self.id[0] == 'ONLINEROBOTS':
					global database
					self.robotMessage = []
					self.cursor = database.cursor()
					self.sql = 'SELECT * FROM spojenie WHERE login_uzivatel = \"' + str(self.parseData[1]) + '\"'
					if self.cursor.execute(self.sql):
						for row in self.cursor.fetchall():
							if len(Robots):
								for robot in Robots:
									if str(robot.getID()) == str(row[4]):
										self.robotMessage.append({'online':str(True), 'robot':str(robot.getID()), 'workflag':str(robot.work()), 'access':str(row[5]), 'robotID':str(row[2]), 'name':str(row[6])})
									else:
										self.robotMessage.append({'online':str(False), 'robot':str(row[4]), 'workflag':str(False), 'access':str(row[5]), 'robotID':str(row[2]), 'name':str(row[6])})
							else:
								self.robotMessage.append({'online':str(False), 'robot':str(row[4]), 'workflag':str(False), 'access':str(row[5]), 'robotID':str(row[2]), 'name':str(row[6])})

					self.Message = 'ONLINEROBOTS:OK|' + json.dumps(self.robotMessage)
					self.sendMessage(self.Message.encode('utf8'))


				if self.id[0] == 'STARTWORKING':

					self.Message = 'STARTWORKING:FAIL|You dont have access!!!'
					self.cursor = database.cursor()
					self.sql = 'SELECT * FROM spojenie WHERE login_uzivatel = \"' + str(self.parseData[1]) + '\" and login_robot = \"' + str(self.parseData[2]) + '\"'
					if self.cursor.execute(self.sql):
						self.access = 0
						for row in self.cursor.fetchall():
							if int(self.access) < int(row[5]):
								self.access = int(row[5])
						if int(self.parseData[3]) <= self.access:
							self.Message = 'STARTWORKING:OK|' + str(self.parseData[1]) + '|' + str(self.parseData[2]) + '|' + str(self.access)
							global port
							self.bridgeProcess = Popen(['./connectionmain.py', self.parseData[1], self.parseData[2], str(port), 'shell=True'])
							for client in Clients:
								if client.getID() == self.parseData[1]:
									for robot in Robots:
										if robot.getID() == self.parseData[2]:
											robot.setWork(True)
											self.ses = webportal.Session(client, robot, self.bridgeProcess)
											Sessions.append(self.ses)
					self.sendMessage(self.Message.encode('utf8'))

			elif self.id[1] == 'PROCESS':
				if self.id[0] == 'REGISTRATION':
					print "JSEM TADY A SESSION:"
					print Sessions
					for ses in Sessions:
						if str(ses.getBridgePID()) == str(self.parseData[1]):
							ses.addBridgeGetway(self)
							ses.saveBridgePort(self.parseData[2])
							self.Message = 'CONNECTION:PORT|' + self.parseData[2]
							for robot in ses.getRobots():
								robot.getGetway().sendMessage(self.Message.encode('utf8'))
							for client in ses.getClients():
								client.getGetway().sendMessage(self.Message.encode('utf8'))
							self.Message = 'REGISTRATION:OK|' + self.parseData[1]
							self.sendMessage(self.Message.encode('utf8'))

			elif self.id[1] == 'STREAM':
				if self.id[0] == 'REGISTRATION':
					for ses in Sessions:
						if str(ses.getBridgePID()) == str(self.parseData[1]):
							ses.addStreamPID(self.parseData[2])
							ses.saveStramPort(self.parseData[3])

				elif self.id[0] == 'STOP':
					for ses in Sessions:
						if str(ses.getBridgePID()) == str(self.parseData[1]):
							ses.removeStream()				

			else:
				print("SERVERERROR: WRONG IDENTIFICATION CLIENT IN PROTOCOL")
			
									
	def onClose(self, wasClean, code, reason):
			print("SERVER: WebSocket connection closed: {0}".format(reason))


if __name__ == '__main__':

	print("SERVER START:" + str(sys.argv[1]))
	port = sys.argv[1]
	database = MySQLdb.connect(host='localhost', user='root', passwd='athlon', db='robots')
	factory = WebSocketServerFactory('ws://localhost:' + port, debug = False)
	factory.protocol = MyServerProtocol
	reactor.listenTCP(int(port), factory)
	reactor.run()













