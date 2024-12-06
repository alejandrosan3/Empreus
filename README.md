## Empreus Admin

## Local setup

The only requirement is to have Docker installed and running.

Run the following commands to setup the project locally:

```
$ cp .env.example .env
$ ./docker/sail up -d
$ docker-compose exec -it empreus.app composer install
```

Run the following command if you want to add dummy data
```
$ ./docker/sail db:seed
```

After running these commands the project should be running in: http://localhost

After the first configuration, the only command needed to run the project is:

``$ ./docker/sail up``

To stop the project run:

``$ ./docker/sail down``
