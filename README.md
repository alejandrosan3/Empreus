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

### Enabling Search

The application uses **Laravel Scout** with **Meilisearch**.

Configure the connection in your `.env` file:

```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
```

After starting the containers, import the search indexes:

```
./docker/sail artisan scout:import "App\Models\Task"
./docker/sail artisan scout:import "App\Models\Lot"
./docker/sail artisan scout:import "App\Models\Builder"
./docker/sail artisan scout:import "App\Models\Supervisor"
```

Once imported, the search bar in the navigation filters tasks, lots, builders
and supervisors using Meilisearch.

### Queues via Laravel Horizon
Running queue workers via Laravel Horizon is optional but recommended.

If you want to use Laravel Horizon you only need to:
    
1. Make sure you are using Redis for queue connections
Set `QUEUE_CONNECTION=redis` in your .env file
2. Run `$ ./docker/sail artisan horizon`


