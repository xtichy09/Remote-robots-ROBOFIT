#!/usr/bin/python


from autobahn.twisted.websocket import WebSocketServerProtocol, \
									   WebSocketServerFactory

from autobahn.twisted.websocket import WebSocketClientProtocol, \
									   WebSocketClientFactory

from multiprocessing import Process

import sys
import os
from twisted.python import log
from twisted.internet import reactor
from json import dumps
from ws4py.client.threadedclient import WebSocketClient
import socket
import webportal
from datetime import datetime
from subprocess import Popen



Robot = []
Clients = []
API = []
AccessLogins = []
streamPort = []
streamProcess = []



def getPort():
		for port in range(7000,8000):  
			sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
			result = sock.connect_ex(('localhost', port))
			if result != 0:
				sock.close()
				return port
			sock.close()


#Server for webclient
class MyServerProtocol(WebSocketServerProtocol):
	

	def onConnect(self, request):
		print("PROCESS: Client connecting: {0}".format(request.peer))
			
	def onOpen(self):
		print("PROCESS: WebSocket connection open.")

	def onMessage(self, payload, isBinary):
		global port
		if isBinary:
			print("PROCESS: Binary message received: {0} bytes".format(len(payload)))
		else:
			print("PROCESS: Text message received: {0}".format(payload.decode('utf8')))

		if(payload[0] == '"'):
			payload = payload[1:-1]
			print payload

		self.parseData = payload.split('|')

		#TODO - dodelat protokol
		if len(self.parseData) != 0:
			self.id = self.parseData[0].split(':')

			if(self.id[1] == 'WEBCLIENT'):

				if self.id[0] == 'REGISTRATION':
					self.ok = False
					for client in AccessLogins:
						if client == self.parseData[1]:
							self.ok = True
					if self.ok:
						print("PROCESS: ACCESS OK!!!")
						self.client = Client(self, self.parseData[1])
						Clients.append(self.client)
						self.Message = 'REGISTRATION:OK|' + self.parseData[1]
						self.sendMessage(self.Message.encode('utf8'))
					else:
						print("PROCESS: ACCESS FAIL!!!")
						self.Message = 'REGISTRATION:FAIL|' + self.parseData[1]
						self.sendMessage(self.Message.encode('utf8'))

				#TODO Dodelat odosielanie spravy
				elif self.id[0] == 'MESSAGE':
					Robot[0].sendMessage(payload.encode('utf8'))

				elif self.id[0] == 'PING':
					Robot[0].sendMessage(payload.encode('utf8'))

				elif self.id[0] == 'PONG':
					Clients[0].getGetway().sendMessage(payload.encode('utf8'))

				elif self.id[0] == 'LOGOUT':
					self.Message = 'LOGOUT:WEBCLIENT|' + str(os.getpid())
					API[0].sendMessage(self.Message.encode('utf8'))
					Robot[0].sendMessage(self.Message.encode('utf8'))

			elif(self.id[1] == 'ROBOT'):
				if(self.id[0] == 'REGISTRATION'):
					#CHECK ACCESS
					Robot.append(self)
					self.Message = 'REGISTRATION:OK|' + self.parseData[1]
					self.sendMessage(self.Message.encode('utf8'))

				elif(self.id[0] == 'MESSAGE'):
					#TODO FOR ALL CLIENTS
					Clients[0].getGetway().sendMessage(self.parseData[1].encode('utf8'))

				elif(self.id[0] == 'PONG'):
					self.dt = datetime.now()
					self.Message = 'PONG:ROBOT|' + str((self.dt.microsecond - int(self.parseData[1])) / 1000)
					Clients[0].getGetway().sendMessage(self.Message.encode('utf8'))

				elif(self.id[0] == 'PING'):
					self.dt = datetime.now()
					self.Message = 'PING:ROBOT|' + str(self.dt.microsecond)
					Robot[0].sendMessage(self.Message.encode('utf8'))

			elif(self.id[1] == 'SERVER'):
				if(self.id[0] == 'PING'):
					self.Message = 'PONG:SERVER|' + self.parseData[1]
					Clients[0].getGetway().sendMessage(self.Message.encode('utf8'))

			elif self.id[1] == 'STREAM':
				if self.id[0] == 'START':
					self.stream = Popen(['./streamserver.py', '37.205.11.196', str(port), 'shell=True'])
					streamProcess.append(self.stream)

				elif self.id[0] == 'REGISTRATION':
					#Uloz port, odosli spravu API

					#streamPort.append(self.parseData[2])
					self.Message = 'REGISTRATION:OK'
					self.sendMessage(self.Message.encode('utf8'))
					self.Message = 'STREAM:REGISTRATION|' + str(os.getpid()) + '|' + self.parseData[1] + '|' + self.parseData[2]
					API[0].sendMessage(self.Message.encode('utf8'))
					self.Message = 'STREAM:REGISTRATION|' + self.parseData[2]
					Clients[0].getGetway().sendMessage(self.Message.encode('utf8'))
					Robot[0].sendMessage(self.Message.encode('utf8'))

				elif self.id[0] == 'STOP':
					streamProcess[0].kill()
					self.Message = 'STOP:STREAM|' + str(os.getpid())
					API[0].sendMessage(self.Message.encode('utf8'))
					Robot[0].sendMessage(self.Message.encode('utf8'))
					Clients[0].getGetway().sendMessage(self.Message.encode('utf8'))




	def onClose(self, wasClean, code, reason):
		#TODO - odebrat ze seznamu
		print("WebSocket connection closed: {0}".format(reason))


#Client for API server
class MyClientProtocol(WebSocketClientProtocol):
	

	state = 0

	def onConnect(self, response):
		print("PROCESS: Server connected: {0}".format(response.peer))


	def onOpen(self):
		print("PROCESS: Start websocket connection to server.")

		global port
		API.append(self)
		AccessLogins.append(sys.argv[1])
		AccessLogins.append(sys.argv[2])
		self.Message = 'REGISTRATION:PROCESS|' + str(os.getpid()) + '|' + str(port)
		self.sendMessage(self.Message.encode('utf8'))


	def onMessage(self, payload, isBinary):
		if isBinary:
			 print("PROCESS: Binary message received: {0} bytes".format(len(payload)))
		else:
			 print("PROCESS: Text message received: {0}".format(payload.decode('utf8')))
	
		###-> Protocol <- ###
		self.parseData = payload.split('|')

		if len(self.parseData) != 0:
			
			self.id = self.parseData[0].split(':')
			
			if self.id[0] == 'ADD':
				AccessLogins.append(self.id[1])

			if self.id[0] == 'REGISTRATION':
				print('PROCESS: Registration ok')
			#elif self.id[0] == 'CUT':

				
	def onClose(self, wasClean, code, reason):
			print("PROCESS: WebSocket connection closed: {0}".format(reason))


class Client:
	
	def __init__(self , getway, login):
		self.getway = getway
		self.ID = login

	def getGetway(self):
		return self.getway

	def getID(self):
		return self.ID





if __name__ == '__main__':


	print 'Start new process -> '
	#Spracuj parametry
	print sys.argv[1] # -> login
	print sys.argv[2] # -> robot
	print sys.argv[3] # -> port
	print os.getpid()
	global port

	ApiFactory = WebSocketClientFactory('ws://localhost:' + str(sys.argv[3]), debug = False)
	ApiFactory.protocol = MyClientProtocol
	reactor.connectTCP("127.0.0.1", int(sys.argv[3]), ApiFactory)
		
	#Interface for Web client
	#TODO - IP|PORT
	port = getPort()
	WebFactory = WebSocketServerFactory('ws://localhost:'+str(port), debug = False)
	WebFactory.protocol = MyServerProtocol
	reactor.listenTCP(port, WebFactory)

	# START DEBUG
	AccessLogins.append(str(sys.argv[1]))
	# END DEBUG
	reactor.run()
	
	

