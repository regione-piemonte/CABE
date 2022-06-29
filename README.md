# Prodotto

Mèmora Open Source

# Descrizione del prodotto

L'obiettivo principale di **MÈMORA** è quello di permettere la ricerca, la consultazione e la completa catalogazione del patrimonio culturale e dei beni archivistici, entrambi in formato digitale, appartenenti agli enti di diversa natura che partecipano al progetto, quali ad esempio: musei, archivi storici, enti della pubblica amministrazione. Il progetto è stato realizzato attraverso l’adozione di **Collective Access** (software con licenza Open Source GPL per la gestione digitale del patrimonio archivistico, bibliografico e museale) per rispondere alle esigenze di innovazione tecnologica nella catalogazione e nella successiva fruizione dei dati.

# Configurazioni iniziali

Il progetto è basato su **PHP 7.2** e **MySQL 5.7**. Per funzionare correttamente è essenziale che le variabili del database indicate siano impostate come segue:

- **sql-mode** = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"
- **character-set-server** = utf8
- **collation-server** = utf8_general_ci
- **init-connect** = 'SET NAMES utf8'
- **innodb_buffer_pool_instances** = 8
- **character_set_database** = utf8
- **collation_database** = utf8_general_ci

# Prerequisiti di sistema

Il prodotto *Mèmora* è un progetto che sfrutta una soluzione open source chiamata **Collective Access Providence** che è scaricabile e fruibile senza oneri di spesa. Il codice sorgente presente nella componente **cabew** del progetto include una versione personalizzata ed estesa di **Collective Access Providence v1.7.8**.

Per l'adozione della soluzione e i relativi requisiti di sistema si rimanda alla **documentazione ufficiale di Collective Access** ed in particolare alle seguenti url:

- **Prerequisiti di sistema:** https://manual.collectiveaccess.org/setup/systemReq.html

# Installazione

La componente **cabew** è la componente *Collective Access Providence* contenente specifiche personalizzazioni composte da **widget** e **plugin** per la gestione di funzionalità prettamente archivistiche (ad esempio l'assegnazione della *segnatura definitiva* e/o il *calcolo della consistenza* di un archivio e altre funzionalità massive o puntuali).

## Per l'installazione eseguire i seguenti passi

### Database

- scaricare il dump in formato sql del database **cabemysqldb.sql** presente nella directory **/cabemysqldb**;
- eseguire il dump sul proprio database  MySQL.

### Applicazione

 1. scaricare il sorgente contenuto nella directory **cabew/**;
 2. copiare il contenuto della cartella /cabew (index.html e /cola) nel percorso principale (root) di installazione dell'applicativo su Apache, ad esempio: /data/www/html/
 3. modificare i seguenti parametri del file **cola/setup.php** indicando i parametri corretti di accesso al proprio database Mysql e impostare la mail di amministrazione:
    - define("__CA_DB_HOST__", 'dbhost');
    - define("__CA_DB_USER__", 'dbuser');
    - define("__CA_DB_PASSWORD__", 'dbpassword');
    - define("__CA_DB_DATABASE__", 'dbname');
    - define("__CA_ADMIN_EMAIL__", 'adminemail');

 4. verificare che il file **promemoriaTreeObjectAttr.json** posizionato sotto il path *cola/app/widgets/promemoriaTreeObject/conf/* abbia i **permessi di scrittura** (es. -*rw-rw-rw- 1 root apache*) ;
 5. verificare che le cartelle cola/media, cola/import, cola/immagini, cola/app/tmp e cola/app/log abbiano i permessi di scrittura.

#### Verifiche finali

Per eventuali altre configurazioni (quali ad esempio l'elenco dei permessi di scrittura dei file) si rimanda alla url della **documentazione** ufficiale di *Collective Access*: https://manual.collectiveaccess.org/setup/Installation.html

#### Credenziali applicativo

Una volta installato l'applicativo, si potrà accedere all'area di amministrazione tramite le credenziali:

 - **username**: administrator
 - **password**: Admin1234!

# Esecuzione dei test

I test di vulnerabilità svolti non hanno evidenziato problemi di severità elevata.

# Versioning

Per il versionamento del software si usa la tecnica *Semantic Versioning* (http://semver.org).

# Authors

Fare riferimento al file **AUTHORS.txt** per ulteriori dettagli.

# Copyrights

© Copyright Regione Piemonte – 2022

# License

SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
SPDX-License-Identifier: GPL-3.0

Fare riferiferimento al file **LICENSE.txt** per ulteriori dettagli.

# Community site

 - Community del software Open Source **Collective Access**: https://collectiveaccess.org/support/index.php?p=/discussions