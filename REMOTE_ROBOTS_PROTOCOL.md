# Server protocol [server.py](server.py)
Protokol servru je rozdělený na tři části mezi klienty což jsou web, robot a podprocesy sloužící ke komunikaci. 
Základní strukturu zpráv tvoří trojice
```
WHAT:WHO|PARAMS
```
* **WHO** - rozlišuje od koho zpráva pochádzí, a která část protokolu se má použít
* **WHAT** - definuje požadavek klienta, na který reaguje server
* **PARAMS** - jsou specifické parametry pro jednotlivé požadavky
```
STARTWORKING:WEBCLIENT|xtichy09|xrobot00
```

## 1. [Server.py](server.py) protocol for robot

### 1.1 Registration
Slouží k registraci robota na serveru a aktualizaci do online stavu. Při registraci se ověří, jestli je daný robot registrovaný v databázi a následně se vytvoří objekt robot, který se uloží do pole robotů.

```
REGISTRATION:ROBOT|Login|Name
-----------------------------
REGISTRATION:ROBOT|xrobot00|Toad
```
* **Login** - představuje login robota zadaného při registraci, registraci robota provádí uživatel s dostatečnými oprávněními [TODO]
* **Name** - je jméno robota zadané při registraci

### 1.2 Unregistration
Zpráva, která se využívá při ukončení práce/vypnutí nebo dlouhodobém výpadku spojení s robotem. Při odhlašování robota se odstraní všechny metadata uložené v aktivních datových strukturách serveru. 

```
UNREGISTRATION:ROBOT|Login
-----------------------------
UNREGISTRATION:ROBOT|xrobot00
```

* **Login** - ID robota zadaného při registraci

### 1.3 Vigilance detection [EXPERIMENTAL/NOT IMPLEMENT]
Slouží k detekci bdělosti robota, což znamená, že v pravidelných intervalech testuje spojení robota a serveru. Při dlouhodobé neaktivitě předpokládá, že na robotovi nastala kritická chyba nebo chyba ve spojení, a proto server provádí podobné kroky jako při [Unregistration](TODO)

```
CHECK:ROBOT|Login
--------------------
CHECK:ROBOT|xrobot00
```
* **Login** - ID robota zadané při registraci


## 2. [Server.py](server.py) protocol for webclient
Protokol navržený na komunikaci webového klienta a serveru. 

### 2.1 Registration
Zpráva slouží na registraci uživatele na serveru, odesílá se při přihlašování do systému. Nastavují se oprávnění pro uživatele a GUI z databázi. Při registraci se vytvoří objekt client a uloží se do aktivních datových struktur serveru. 

```
REGISTRATION:WEBCLIENT|Login
-----------------------------
REGISTRATION:WEBCLIENT|xtichy09
```

* **Login** - ID uživatele, které umožňuje přihlašování do systému

### 2.2 Logout
Zpráva, která se využívá při ukončení práce nebo odhlášení webového klienta ze servru. Z aktívních datových struktur se odstraní metadata o webovém klientovi a ukončí se všechny procesy spojené s obsluhou klienta.

```
LOGOUT:WEBCLIENT|Login
-------------------------
LOGOUT:WEBCLIENT|xtichy09
```

* **Login** - ID uživatele, které umožňuje přihlašování do systému

### 2.3 Online robots
Zpráva se odesílá hned po úspěšném přihlášení uživatele do systému a registraci na serveru. Cílem požadavku je zjistit metadata o všech robotech, které jsou dostupné pro daného uživatele. [TODO](více o metadatech správa v JSON formáte)

```
ONLINEROBOTS:WEBCLIENT|Login
-----------------------------
ONLINEROBOTS:WEBCLIENT|xtichy09
```

* **Login** - ID užívatele, které umožňuje přihlašování do systému

### 2.4 Star working with robot
Požadavek uživatele, pokud chce zahájit komunikaci s robotem. Jako první sa ověřuje, jestli má daný uživatel právo s robotem pracovat. Následně se spustí komunikační vlákno [connectionmain.py](connectionmain.py) se vstupními parametry.

```
./connectionmain.py Login1 Login2 Port
----------------------------------------
STARTWORKING:WEBCLIENT|Login1|Login2
----------------------------------------
STARTWORKING:WEBCLIENT|xtichy09|xrobot00
```
* **Login1** - ID uživatele
* **Login2** - ID robota
* **Port** - je port, na kterém je spuštěný server

## 3. Server protocol for subprocess
Protokol navržený pro komunikaci serveru a podprocesů zajišťujících komunikaci mezi webovým klientem a robotem. 
### 3.1 Registration communication process
Když uživatel odešle požadavek startworking, [2.4]() tak hlavní proces spustí nové komunikační vlákno [connectionmain.py](connectionmain.py). Když je komunikační vlákno připraveno na zahájení komunikace, zaregistruje se na serveru a oznámí mu svoje PID a PORT, na kterém očekává požadavky od klienta. Při registraci se informace o podprocese uloží do aktivních datových struktur. 

```
REGISTRATION:PROCESS|PID|PORT
-----------------------------
REGISTRATION:PROCESS|359|7000
```

* **PID** - ID komunikačního podprocesu
* **PORT** - rezervovaný PORT, na kterém komunikační vlákno naslouchá

### 3.2 Unregistration communication process [EXPERIMENTAL]
Bude slúžit při kritických chybách v komunikační podprocesu. 
```
UNREGISTRATION:PROCESS|PID|MESSAGE
--------------------------
UNREGISTRATION:PROCESS|359|ERROR:404
```

* **PID** - ID komunikačního podprocesu
* **MESSAGE** - důvod odregistrování komunikačního podprocesu

### 3.3 Registration stream process
Slouží k registraci vlákna zajišťujícího přenos videa od robota k webovému klientovi. Podproces se spouští na základě požadavku webového klienta [7.1]() a spouší ho komunikační vlákno [connectionmain.py](connectionmain.py). Když je podprocess připravený na komunikaci, zaregistruje sa nejdřív u komunikačního vlákna zprávou [7.2]() a následně komunikační podproces zaregistruje stream podprocess u hlavního procesu [server.py](server.py).

```
REGISTRATION:STREAM|PID1|PID2|PORT
----------------------------------
REGISTRATION:STREAM|359|405|7001
```

* **PID1** - ID komunikačního podprocesu
* **PID2** - ID stream podprocesu
* **PORT** - port, na kterém naslouchá stream podproces

### 3.4 [Server.py](server.py) stop stream process
Používá se na zastavení/ukončení přenosu videa a ukončení stream podprocesu. 

```
STOP:STREAM|PID
---------------
STOP:STREAM|405
```

* **PID** - ID komunikačného podprocesu, které je spojené se stream podprocesem

## 4 Subprocess for communication protocol for webclient [connectionmain.py](connectionmain.py)

### 4.1 Registration
Po spuštění komunikačního podprocesu server odešle informace webovému klientovy(port), který se následně může zaregistrovat u komunikačního podprocesu. Při registraci se kontroluje, jestli uživatel má právo s daným robotem pracovat a vztah klienta k vytvořené session. 
```
REGISTRATION:WEBCLIENT|LOGIN
-------------------------------
REGISTRATION:WEBCLIENT|xtichy09
```
* **Login** - id uživatela pro identifikaci v systému

### 4.2 Message for robot
Tento požadavek se používá pokud webový klient chce poslat zprávu robotovi. Pokud je robot registrovaný v komunikačním podprocesu, tak se zpráva jenom přepošle robotovi. Samotná zpráva pro robota je formátovaná podle [rosbridge protokolu](TODO), který je založen na JSON formátě. Ke zprávě je přidaná hlavička [remote robots prokolu](TODO). 
```
MESSAGE:WEBCLIENT|[ROSBRIDGE PROTOCOL V2](TODO)
-------------------------------
MESSAGE:WEBCLIENT|(TODO)
```

* **ROSBRIDGE PROTOCOL V2** - zpráva založená na rosbridge protokolu

### 4.3 Test ping server and robot
Zpráva, která slouží k analýze a testování spojení mezi strukturami systému. Měří se několik spojení obr [PING](TODO)
Tato zpráva měří odezvu webového klienta ke komunikačnímu vláknu. 

```
PING:WEBCLIENT
--------------
PING:WEBCLIENT
```
 
### 4.4 Return message from ping
Zpráva, kterou odešle webový klient, když obdrží zprávu [4.3].

```
PONG:WEBCLIENT
-------------------------------
PONG:WEBCLIENT
```

### 4.5 Logout
Požadavek se používá při ukončení práce nebo odhlášení uživatele ze systému. Při požadavku se musí ukončit komunikační podproces a vyčistit aktívní datové struktury v hlavním procesu.

```
LOGOUT:WEBCLIENT|LOGIN
-------------------------------
LOGOUT:WEBCLIENT|xtichy09
```

* **Login** - ID uživatela v systému

## 5 Subprocess for communication protocol for robot [connectionmain.py](connectionmain.py)
Protokol navržený ke komunikaci s robotem v rámci komunikačního podprocesu.

### 5.1 Registration
Po spuštění komunikačního podprocesu server odešle informace robotovi, který sa následně může zaregistrovat u komunikačného podprocesu. 
```
REGISTRATION:ROBOT|LOGIN
-------------------------------
REGISTRATION:ROBOT|xrobot00
```
* **Login** - ID robota v systému, pod kterým se registruje do systému

### 5.2 Message from robot to webclient
Tento požadavek se používá pokud robot chce poslat zprávu robotovi. Samotná zpráva pro robota je formátovaná podle [rosbridge protokolu](TODO), který je založený na JSON formátě. Ke zprávě je přidaná hlavička [remote robots prokolu](TODO). 
```
MESSAGE:ROBOT|[ROSBRIDGE PROTOCOL V2](TODO)
-------------------------------------
MESSAGE:ROBOT|[TODO]
```

* **ROSBRIDGE PROTOCOL V2** - zpráva založená na rosbridge protokolu

### 5.3 Ping robot
Zpráva, která slouží k analýze a testování spojení mezi strukturami systému. Měří se několik spojení obr [PING](TODO)
Tato zpráva měří odezvu robota ke komunikačnímu vláknu. 

```
PING:ROBOT
----------
PING:ROBOT
```

### 5.4 Return message for ping
```
PONG:ROBOT
----------
PONG:ROBOT
```

## 6 Subprocess for communication protocol for server [connectionmain.py](connectionmain.py)
Zpráva, která slouží k analýze a testování spojení mezi strukturami systému. Měří se několik spojení obr [PING](TODO)
Tato zpráva měří odezvu webového serveru ke komunikačnímu vláknu. 

### 6.1 Ping
```
PING:SERVER
-----------
PING:SERVER
```

### 6.2 Return message from server
```
PONG:SERVER
-------------------------------
PONG:SERVER
```

### 6.3 End this subprocess [EXPERIMENTAL/NOT IMPLEMENT]
```
END:SERVER
----------
END:SERVER
```

## 7 Subprocess for communication protocol for stream subprocess [streamserver.py](streamserver.py)

### 7.1 Start message from webclient
Zpráva, kterou odesílá
```
START:STREAM
------------
START:STREAM
```

### 7.2 Registration stream subprocess

```
REGISTRATION:STREAM|PID|PORT
----------------------------
REGISTRATION:STREAM|305|7999
```

### 7.3 Stop stream subprocess

```
STOP:STREAM
-----------
STOP:STREAM
```

