#!/usr/bin/python

from autobahn.twisted.websocket import WebSocketClientProtocol, \
									   WebSocketClientFactory

from subprocess import Popen

class MyClientProtocol(WebSocketClientProtocol):
	

	state = 0

	def onConnect(self, response):
		print("Server connected: {0}".format(response.peer))

	def onOpen(self):
		print("Start websocket connection to server.")
		
		self.Message = "REGISTRATION:ROBOT|" + str(sys.argv[1]) + "|" + sys.argv[2];
		self.sendMessage(self.Message.encode('utf8'))

	def onMessage(self, payload, isBinary):
		if isBinary:
			 print("Binary message received: {0} bytes".format(len(payload)))
		else:
			 print("Text message received: {0}".format(payload.decode('utf8')))
	
		###-> Protocol <- ###
		
		self.parseData = payload.split('|')
		
		if len(self.parseData) != 0:
			
			self.id = self.parseData[0].split(':')
			
			if self.id[0] == 'REGISTRATION':
				if self.id[1] == 'OK':
					print("REGISTRATION OK!!!")
				elif self.id[1] == 'FALSE':
					print("REGISTRATION FALSE!!!")

			if self.id[0] == 'CONNECTION':
				if self.id[1] == 'PORT':    
					self.process = Popen(['./rosbrwebsock.py', self.parseData[1], str(sys.argv[1]), 'shell=True'])


	def onClose(self, wasClean, code, reason):
			print("WebSocket connection closed: {0}".format(reason))


if __name__ == '__main__':

   import sys
   from twisted.python import log
   from twisted.internet import reactor

   log.startLogging(sys.stdout)

   factory = WebSocketClientFactory("ws://"+str(sys.argv[3])+":"+str(sys.argv[4]), debug = False)
   factory.protocol = MyClientProtocol

   reactor.connectTCP(str(sys.argv[3]), int(sys.argv[4]), factory)
   reactor.run()

