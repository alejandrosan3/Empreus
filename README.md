## Empreus Admin

## Local setup

The only requirement is to have Docker installed and running.

Run the following commands to setup the project locally:

```
$ cp .env.example .env
$ ./docker/sail up -d
$ ./docker/sail composer install
$ ./docker/sail npm install
```

Run the following command thefirst time setting the project up to create the DB and populate it with dummy data

```
# Create the DB
$ ./docker/sail artisan migrate (:fresh)

# Populate database with Dummy Data (for local/testing)
$ ./docker/sail artisan db:seed
```
You may have to run `./docker/sail artisan key:generate`  
Lastly run `./docker/sail npm run dev`. The project should be running in: http://localhost

After the first configuration, the only command needed to run the project is:

`$ ./docker/sail up -d`

To stop the project run:

`$ ./docker/sail down`
