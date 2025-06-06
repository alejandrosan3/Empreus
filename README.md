## Empreus Admin

## Local setup

The only requirement is to have Docker installed and running.

Run the following commands to setup the project locally:

```
$ cp .env.example .env
$ ./docker/sail up -d
$ FLUX_USER=alejandrosan3@gmail.com \
  FLUX_PASS=fcc36e72-5f5d-4402-98f7-8c5d8f7264e2 \
  ./docker/sail composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"
$ ./docker/sail composer install
$ ./docker/sail npm install
```

`FLUX_USER` and `FLUX_PASS` are required to download private Flux packages. You
can export them as environment variables instead of inline if preferred.

Run the following command the first time setting the project up to create the DB and populate it with dummy data

```
# Create the DB
$ ./docker/sail artisan migrate (:fresh)

# Seed mandatory tables (mandatory)
$ ./docker/sail artisan db:seed TaskTypeSeeder
$ ./docker/sail artisan db:seed StatusTypeSeeder

# Populate database with Dummy Data (for local/testing)
$ ./docker/sail artisan db:seed
```
You may have to run `./docker/sail artisan key:generate`  
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

