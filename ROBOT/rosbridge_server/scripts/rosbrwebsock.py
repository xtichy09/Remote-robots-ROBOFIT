#!/usr/bin/env python

from subprocess import Popen

import rospy
import json

from rosauth.srv import Authentication

from signal import signal, SIGINT, SIG_DFL
from functools import partial

from tornado.ioloop import IOLoop
from tornado.web import Application
from tornado.websocket import WebSocketHandler

from rosbridge_library.rosbridge_protocol import RosbridgeProtocol
from twisted.internet import reactor

from autobahn.twisted.websocket import WebSocketClientProtocol, \
									   WebSocketClientFactory

import sys
from subprocess import Popen
import time


# Global ID seed for clients
client_id_seed = 0
clients_connected = 0
# if authentication should be used
authenticate = False
lgoin = 'x'
streamProcess = []

class MyClientProtocol(WebSocketClientProtocol):
	

	state = 0

	def onConnect(self, response):
		print("Server connected: {0}".format(response.peer))

	def onOpen(self):
		global client_id_seed, clients_connected, authenticate, login
		try:
			self.protocol = RosbridgeProtocol(client_id_seed)
			self.protocol.outgoing = self.send_message
			self.authenticated = True
			client_id_seed = client_id_seed + 1
			clients_connected = clients_connected + 1
		except Exception as exc:
			rospy.logerr("Unable to accept incoming connection.  Reason: %s", str(exc))
		rospy.loginfo("Client connected.  %d clients total.", clients_connected)
		print("ROBOTCLIENT: Start websocket connection to server.")
		self.Message = 'REGISTRATION:ROBOT|' + login
		self.sendMessage(self.Message.encode('utf8'))


	def onMessage(self, payload, isBinary):
		if isBinary:
			 print("Binary message received: {0} bytes".format(len(payload)))
		else:
			 print("Text message received: {0}".format(payload.decode('utf8')))


		self.parseData = payload.split('|')

		if len(self.parseData) != 0:
			
			self.id = self.parseData[0].split(':')
			
			if self.id[0] == 'STOP':
				if self.id[1] == 'STREAM':
					streamProcess[0].kill()

			if self.id[0] == 'REGISTRATION':
				print('REGISTRATION OK')

			if self.id[0] == 'STREAM':
				if self.id[1] == 'REGISTRATION':
					time.sleep(5)
					self.process = Popen(['./client', '37.205.11.196', self.parseData[1], 'shell=True'])
					streamProcess.append(self.process)


			#Send message to rosbridge
			if self.id[0] == 'MESSAGE':
				print('ROBOTCLIENT: Send message to rosbridge')
				self.protocol.incoming(self.parseData[1])

			if self.id[0] == 'PING':
				if self.id[1] == 'WEBCLIENT':
					self.Message = 'PONG:WEBCLIENT|' + self.parseData[1]
					self.sendMessage(self.Message.encode('utf8'))
				elif self.id[1] == 'ROBOT':
					self.Message = 'PONG:ROBOT|' + self.parseData[1]
					self.sendMessage(self.Message.encode('utf8'))

		else:
			self.protocol.incoming(payload)


	def onClose(self, wasClean, code, reason):
		global clients_connected
		clients_connected = clients_connected - 1
		self.protocol.finish()
		rospy.loginfo("Client disconnected. %d clients total.", clients_connected)

	def send_message(self, message):
		print("SERVER: ODOSIELAM SPRAVU:" + message)
		self.Message = "MESSAGE:ROBOT|" + message
		self.sendMessage(self.Message.encode('utf8'))


if __name__ == "__main__":
	global login
	login = sys.argv[2]

	rospy.init_node("rosbridge_websocket")
	signal(SIGINT, SIG_DFL)
	factory = WebSocketClientFactory("ws://37.205.11.196:" + str(sys.argv[1]), debug = False)
	factory.protocol = MyClientProtocol

	reactor.connectTCP("37.205.11.196", int(sys.argv[1]), factory)
	reactor.run()
