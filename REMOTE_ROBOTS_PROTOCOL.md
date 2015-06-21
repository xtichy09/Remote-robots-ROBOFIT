# Server protocol [server.py](server.py)
Protokol servru je rozdelený na tri časti medzi klienov čo sú web, robot a podprocesy slúžiace na komunikáciu. 
Základnú štruktúru správ tvorí trojice
```
WHAT:WHO|PARAMS
```
* **WHO** - rozlišuje od koho správa pochádza a která část protokolu se má použít
* **WHAT** - definuje požadavku klienta na ktorú reaguje server
* **PARAMS** - sú špecifické parametre pre jednotlivé požadavky
```
STARTWORKING:WEBCLIENT|xtichy09|xrobot00
```

## 1. [Server.py](server.py) protocol for robot

### 1.1 Registration
Slúži na registráciu robota na servri a update do online stavu. Pri registrácií sa overí jestli je daný robot registrovaný v databázi a následne se vytvorí objekt robot který se uloží do pole robotov.

```
REGISTRATION:ROBOT|Login|Name
-----------------------------
REGISTRATION:ROBOT|xrobot00|Toad
```
* **Login** - predstavuje login robota zadaného pri registrácií, registráciu robota provádí užívatel s dostatočnými oprávneniami [TODO]
* **Name** - je meno robota zadané pri registrácií

### 1.2 Unregistration
Správa která se využíva pri ukončení práce/vypnutí nebo dlhodobom výpadku spojenia s robotom. Pri odhlašovaní robota sa odstránia všetky metadata uložené v aktívnych dátových štruktúrach servra. 

```
UNREGISTRATION:ROBOT|Login
-----------------------------
UNREGISTRATION:ROBOT|xrobot00
```

* **Login** - ID robota zadaného pri registrácií

### 1.3 Vigilance detection [EXPERIMENTAL/NOT IMPLEMENT]
Slúži na detekciu bdelosti robota, čo znamená že v pravidelných intervalo testuje spojenie robota a servru. Pri dlhodobej neaktivite predpokladá, že na robotovi nastala kritická chyba nebo chyba v spojení a proto server provádí podobné kroky ako pri [Unregistration](TODO)

```
CHECK:ROBOT|Login
--------------------
CHECK:ROBOT|xrobot00
```
* **Login** - ID robota zadaného pri registrácií


## 2. [Server.py](server.py) protocol for webclient
Protokol navrhnutý na komunikáciu webového klienta a servru. 

### 2.1 Registration
Správa slúži na registráciu užívatela na servri, odosiela sa pri prihlasovaní do systému. Nastavují se oprávnenií pro užívatela a GUI z databázi. Pri registrácií sa vytvorí objekt client a uloží sa do aktívnych dátových štruktúr servru. 

```
REGISTRATION:WEBCLIENT|Login
-----------------------------
REGISTRATION:WEBCLIENT|xtichy09
```

* **Login** - ID užívatela které umožnuje prihlasovanie do systému

### 2.2 Logout
Správa která se využíva pri ukončení práce nebo odhlásení webového klienta zo servru. Z aktívnych dátových štruktúr sa odstrání metadata o webovom klientovi a ukončia sa všetky procesy spojené s obsluhov klienta.

```
LOGOUT:WEBCLIENT|Login
-------------------------
LOGOUT:WEBCLIENT|xtichy09
```

* **Login** - ID užívatela které umožnuje prihlasovanie do systému

### 2.3 Online robots
Správa sa odosiela hned po úspešnom prihlásení užívatela do systému a registrácii na servru. Cílem požadavku je zistit metadata o všetkých robotch ktoré su dostupné pre daného užívatela. [TODO](více o metadatech správa v JSON formáte)

```
ONLINEROBOTS:WEBCLIENT|Login
-----------------------------
ONLINEROBOTS:WEBCLIENT|xtichy09
```

* **Login** - ID užívatela které umožnuje prihlasovanie do systému

### 2.4 Star working with robot
Požadavek užívatela pokud chce zahájiť komunikáciu s robotom. Jako první sa overuje jestli daný užívatel má právo s robotem pracovať. Následne se spustí komunikační vlákno [connectionmain.py](connectionmain.py) so vstupními parametrami.

```
./connectionmain.py Login1 Login2 Port
----------------------------------------
STARTWORKING:WEBCLIENT|Login1|Login2
----------------------------------------
STARTWORKING:WEBCLIENT|xtichy09|xrobot00
```
* **Login1** - ID užívatela
* **Login2** - ID robota
* **Port** - je port na ktorom je spustený server

## 3. Server protocol for subprocess
Protokol navrhnutý na komunikáciu servru a podprocesov zajištujúcich komunikáciu medzi webovým klientom a robotom. 
### 3.1 Registration communication process
Když užívatel odošle požadavek startworking [2.4]() tak hlavní proces spustí nové komunikační vlákno [connectionmain.py](connectionmain.py). Když je komunikační vlákno pripraveno na zahájenie komunikácie, zaregistruje sa na servru a oznámi mu svoje PID a PORT na ktorom očekáva požadavky od klienta. Pri registrácií sa informácie o podprocese uložia do aktivních dátových štruktúr. 

```
REGISTRATION:PROCESS|PID|PORT
-----------------------------
REGISTRATION:PROCESS|359|7000
```

* **PID** - ID komunikačního podprocesu
* **PORT** - rezervovaný PORT na ktorom komunikačné vlákno naslouchá

### 3.2 Unregistration communication process [EXPERIMENTAL]
Bude slúžit pri kritických chybách v komunikačnom podprocese. 
```
UNREGISTRATION:PROCESS|PID|MESSAGE
--------------------------
UNREGISTRATION:PROCESS|359|ERROR:404
```

* **PID** - ID komunikačného podprocesu
* **MESSAGE** - důvod odregistrovania komunikačného podprocesu

### 3.3 Registration stream process
Slúži na registráciu vlákna zajistujúceho prenos videa od robota k webovému klientovi. Podproces se spúšta na základe požadavku webového klienta [7.1]() a spouší ho komunikačné vlákno [connectionmain.py](connectionmain.py). Když je podprocess pripravený na komunikáciu, zaregistruje sa najdřív u komunikačného vlákna správou [7.2]() a následne komunikační podprocess zaregistruje stream podprocess u hlavného procesu [server.py](server.py).

```
REGISTRATION:STREAM|PID1|PID2|PORT
----------------------------------
REGISTRATION:STREAM|359|405|7001
```

* **PID1** - ID komunikačného podprocesu
* **PID2** - ID stream podprocesu
* **PORT** - port na kterém naslouchá stream podproces

### 3.4 [Server.py](server.py) stop stream process
Používa sa na zastavenie/ukončenie prenosu videa a ukončenie stream podprocesu. 

```
STOP:STREAM|PID
---------------
STOP:STREAM|405
```

* **PID** - ID komunikačného podprocesu ktoré je spojené s stream podprocesom

## 4 Subprocess for communication protocol for webclient [connectionmain.py](connectionmain.py)

### 4.1 Registration
Po spušení komunikačného podprocesu server odošle informácie webovému klientovy(port) ktorý sa následne může zaregistrovať u komunikačného podprocesu. Pri registrácií sa kontroluje jestli užívatel má právo s daným robotem pracovať a vzťah klienta k vytvorenej session. 
```
REGISTRATION:WEBCLIENT|LOGIN
-------------------------------
REGISTRATION:WEBCLIENT|xtichy09
```
* **Login** - id užívatela pre identifikáciu v systéme

### 4.2 Message for robot
Tenhle požadavek se používa pokud webový klient chce poslať správu robotovi. Pokud je robot registrovaný v komunikačnom podprocese tak se správa jenom prepošle robotovi. Samotná správa pre robota je formatovaná podle [rosbridge protokolu](TODO) ktorý je založený na JSON formáte. K správe je pridaná hlavička [remote robots prokolu](TODO). 
```
MESSAGE:WEBCLIENT|[ROSBRIDGE PROTOCOL V2](TODO)
-------------------------------
MESSAGE:WEBCLIENT|(TODO)
```

* **ROSBRIDGE PROTOCOL V2** - správa založená na rosbridge protokole

### 4.3 Test ping server and robot
Správa která slúži k analýze a testovaniu spojenia medzi štruktúrami systému. Mněrí se nekolik spojení obr [PING](TODO)
Táto správa měrí odozvu webového klienta ku komunikačnému vláknu. 

```
PING:WEBCLIENT
--------------
PING:WEBCLIENT
```
 
### 4.4 Return message from ping
Správa kterú odošle webový klient když obdrží správu [4.3].

```
PONG:WEBCLIENT
-------------------------------
PONG:WEBCLIENT
```

### 4.5 Logout
Požadavek se používa pri ukončení práce nebo odhlásení užívatela zo systému. Pri požadavku se musí ukončit komunikační podprocess a vyčistit aktívne dátove štruktúry na hlavnom procese.

```
LOGOUT:WEBCLIENT|LOGIN
-------------------------------
LOGOUT:WEBCLIENT|xtichy09
```

* **Login** - ID užívatela v systéme

## 5 Subprocess for communication protocol for robot [connectionmain.py](connectionmain.py)
Protokol navrhnutý na komunikáciu s robotom v rámci komunikačného podprocesu.

### 5.1 Registration
Po spušení komunikačného podprocesu server odošle informácie robotovi ktorý sa následne může zaregistrovať u komunikačného podprocesu. 
```
REGISTRATION:ROBOT|LOGIN
-------------------------------
REGISTRATION:ROBOT|xrobot00
```
* **Login** - ID robota v systéme pod ktorým sa registruje do systému

### 5.2 Message from robot to webclient
Tenhle požadavek se používa pokud robot chce poslať správu robotovi. Samotná správa pre robota je formatovaná podle [rosbridge protokolu](TODO) ktorý je založený na JSON formáte. K správe je pridaná hlavička [remote robots prokolu](TODO). 
```
MESSAGE:ROBOT|[ROSBRIDGE PROTOCOL V2](TODO)
-------------------------------------
MESSAGE:ROBOT|[TODO]
```

* **ROSBRIDGE PROTOCOL V2** - správa založená na rosbridge protokole

### 5.3 Ping robot
Správa která slúži k analýze a testovaniu spojenia medzi štruktúrami systému. Mněrí se nekolik spojení obr [PING](TODO)
Táto správa měrí odozvu robota ku komunikačnému vláknu. 

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
Správa která slúži k analýze a testovaniu spojenia medzi štruktúrami systému. Mněrí se nekolik spojení obr [PING](TODO)
Táto správa měrí odozvu webového servra ku komunikačnému vláknu. 

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
Správa ktorú odosiela
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
