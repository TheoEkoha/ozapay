# **OZAPAY**

### Steps to install and config the app

#### Pre-requisites
- php8.2 version: > 8.2
- Database: PostgreSQL version >= 15

#### Then
- clone from repository
- execute the command: 

```bash
composer install
```
- Update database schema and structures
```bash
 php8.2 bin/console doctrine:migrations:migrate
 
```

- Launch assets
```bash
 php8.2 bin/console assets:install
 
```

### DOCKER documentation 
- https://towardsdatascience.com/how-to-run-postgresql-and-pgadmin-using-docker-3a6a8ae918b5

### Build du docker
- winpty docker-compose build

### Lancement du docker
- winpty docker-compose up -d

### Accés au serveur linux/bash du container
- winpty docker exec -it <container_name> bash

- To check the Database IP address
	```bash
	docker inspect ozapay_postgres_container | grep IPAddress

### Information base des données
- POSTGRES_USER=root      
- POSTGRES_PASSWORD=root
- POSTGRES_DB=monamphi
- Information connexion pgAdmin
- PGADMIN_DEFAULT_EMAIL=user@domain.com
- PGADMIN_DEFAULT_PASSWORD=root

### URL connexion PgAdmin 
- http://ozapay.so:94
	
### Recuperation de l'adresse IP du contenaire de la base des données PostgreSQL
- docker ps
- docker inspect <CONTAINER_ID_POSTGRES> | grep IPAddress

## Command
### Create admin command
Params : username, password, pin
```bash
php8.2 bin/console app:create-admin <username> <password> <pin>
```
## Import user command
Params : username, password, pin
```bash
php8.2 bin/console app:add-users <-d>(optional)
```
You add option -d if you wanna delete the file after import# ozapay
# ozapay
