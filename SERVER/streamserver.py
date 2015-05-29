#!/usr/bin/env python

from socket import *
import thread
import sys

from autobahn.twisted.websocket import WebSocketServerProtocol, \
									   WebSocketServerFactory

from autobahn.twisted.websocket import WebSocketClientProtocol, \
									   WebSocketClientFactory

from twisted.python import log
from twisted.internet import reactor
import os

global port


#Client for API server
class MyClientProtocol(WebSocketClientProtocol):
	

	def onConnect(self, response):
		print("STREAMPROCESS: Server connected: {0}".format(response.peer))


	def onOpen(self):
		print("STREAMPROCESS: Start websocket connection to server.")

		global port
		self.Message = 'REGISTRATION:STREAM|' + str(os.getpid()) + '|' + str(port)
		self.sendMessage(self.Message.encode('utf8'))
		
	def onMessage(self, payload, isBinary):
		
		if isBinary:
			print("STREAMPROCESS: Binary message received: {0} bytes".format(len(payload)))
		else:
			print("STREAMPROCESS: Text message received: {0}".format(payload.decode('utf8')))

		if(payload[0] == '"'):
			payload = payload[1:-1]
			print payload

		self.parseData = payload.split('|')

		if(self.parseData[0] == 'REGISTRATION:OK'):
			print "STREAMPROCESS: STOPING REACTOR"
			reactor.callFromThread(reactor.stop)



	def onClose(self, wasClean, code, reason):
			print("STREAMPROCESS: WebSocket connection closed: {0}".format(reason))



def handler(robSocket, webSocket, header):

	print "SUBPROCESS: Starting streaming from robot to webclient:"

	robSocket.send(header)
	countframe = 0

	while 1:
		countframe += 1
		data = robSocket.recv(140000)
		if not data:
			break
		else:
			webSocket.send(data)

	robSocket.close()
	webSocket.close()

if __name__ == "__main__":

	global port
	print "STREAM: Starting stream server"
	print sys.argv[1] # -> port
	print sys.argv[2] # -> BRIDGE SERVER PORT
	host = sys.argv[1]

	ApiFactory = WebSocketClientFactory('ws://localhost:' + str(sys.argv[2]), debug = False)
	ApiFactory.protocol = MyClientProtocol
	reactor.connectTCP("127.0.0.1", int(sys.argv[2]), ApiFactory)
	addr = (host, 0)
	serversocket = socket(AF_INET, SOCK_STREAM)
	serversocket.bind(addr)
	port = serversocket.getsockname()[1]
	reactor.run()

	serversocket.listen(2)
	port = serversocket.getsockname()[1]
	print "PORT A JEDU DAAALE:"
	print port
	print "PID" + str(os.getpid())

	webSocket = socket(AF_INET, SOCK_STREAM)
	robSocket = socket(AF_INET, SOCK_STREAM)
	sockets = 0
	getHeader = ""
	word = 'GET /' 

	while 1:
		print "STREAM: Server is listening for connections\n"

		clientsocket, clientaddr = serversocket.accept()
		header = clientsocket.recv(2048)

		if word in header:
			webSocket = clientsocket
			getHeader = header
			sockets += 1
		else:
			robSocket = clientsocket
			sockets += 1

		if sockets == 2:
			thread.start_new_thread(handler, (robSocket, webSocket, getHeader))
			sockets = 0

	serversocket.close()
	webSocket.close()
	robSocket.close()
	sys.exit()
