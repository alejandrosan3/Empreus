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

Run the following command the first time setting the project up to create the DB and populate it with dummy data

```
# Create the DB
$ ./docker/sail artisan migrate (:fresh)

# Populate database with Dummy Data (for local/testing)
$ ./docker/sail artisan db:seed
```

Lastly run `./docker/sail npm run dev`. The project should be running in: http://localhost

After the first configuration, the only command needed to run the project is:

`$ ./docker/sail up -d`

To stop the project run:

`$ ./docker/sail down`

### Queues via Laravel Horizon
Running queue workers via Laravel Horizon is optional but recommended.

If you want to use Laravel Horizon you only need to:

1. Make sure you are using Redis for queue connections
Set `QUEUE_CONNECTION=redis` in your .env file
2. Run `$ ./docker/sail artisan horizon`

