# CodeChopper – Crew Training Matrix API

CodeChopper is a REST API that tracks which trainings crew members need and whether those trainings are still valid. Each crew member has one or more job titles (pilot, flight attendant, ground staff, …). Each job title requires a set of trainings. The API returns a **matrix of crew members × required trainings**, where every cell has a traffic-light status (`green`, `yellow` or `red`) and the number of days left.

Built with Laravel 13, Laravel Sanctum, Pest and Laravel Sail (MySQL 8.4).

## Requirements

- Git
- Docker (Docker Desktop, or Docker Engine with the Compose plugin)

You don't need PHP, Composer or Node installed locally. Everything runs inside the Sail containers.

## Setup

### 1. Clone the repository

```bash
git clone https://github.com/Moritz-Henzi/CodeChopper.git
cd CodeChopper
```

### 2. Install the Composer dependencies

Sail itself is a Composer package, so the first `composer install` runs in a throwaway container:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Create the environment file

```bash
cp .env.example .env
```

`.env.example` is already configured for Sail's MySQL container (`DB_HOST=mysql`, user `sail`, password `password`).

### 4. Start the containers

```bash
./vendor/bin/sail up -d
```

The first start builds the PHP 8.5 app image and takes a few minutes.

> **Tip:** add a shell alias so you can type `sail` instead of `./vendor/bin/sail`:
>
> ```bash
> alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
> ```

### 5. Generate the application key

```bash
./vendor/bin/sail artisan key:generate
```

### 6. Run the migrations and seed the sample data

```bash
./vendor/bin/sail artisan migrate --seed
```

This creates the tables and loads the sample data from the JSON files in the project root (see [Sample data](#sample-data)). All seeded users have the password `password`.

The seeder doesn't create any training completions, so **every status starts out `red`**.

To wipe the database and seed it again at any time:

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### 7. Build the frontend assets

The API doesn't need these, but the welcome page at `/` does:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### 8. Open the app

- App: <http://localhost>
- Health check: <http://localhost/up>

### 9. Create an API token

All API endpoints are protected by Sanctum, and there is no login endpoint. Create a token for a user with tinker:

```bash
./vendor/bin/sail artisan tinker --execute 'echo App\Models\User::where("email", "test@example.com")->first()->createToken("local")->plainTextToken;'
```

Copy the printed token. It is shown only once.

### 10. Call the API

```bash
curl http://localhost/api/v1/dashboard/matrix \
    -H "Authorization: Bearer <your-token>" \
    -H "Accept: application/json"
```

### Stopping

```bash
./vendor/bin/sail down      # stop the containers
./vendor/bin/sail down -v   # stop the containers and delete the MySQL volume
```

## API

All endpoints are prefixed with `/api/v1`, require a `Bearer` token (`auth:sanctum`) and are rate limited. Errors under `/api/*` are always returned as JSON.

| Method | Endpoint                       | Description                                                                                 |
|--------|--------------------------------|---------------------------------------------------------------------------------------------|
| GET    | `/api/v1/dashboard/matrix`     | Matrix of all crew members who have at least one job title × trainings (cached per day)     |
| GET    | `/api/v1/crew-members/{user}`  | Training status of a single crew member                                                     |
| GET    | `/api/v1/trainings`            | List of all trainings                                                                       |
| GET    | `/api/v1/trainings/{training}` | A single training                                                                           |

Each matrix row only contains cells for the trainings that the crew member's job titles require.

### How the status is calculated

The status of a cell is based on the crew member's **latest** completion of that training ([TrainingStatusService](app/Services/TrainingStatusService.php)):

| Situation                                       | Status   |
|-------------------------------------------------|----------|
| Training was never completed                    | `red`    |
| Completion never expires (`valid_until` empty)  | `green`  |
| Today is after `valid_until`                    | `red`    |
| Today is on or after `renewal_from`             | `yellow` |
| Otherwise                                       | `green`  |

`days_left` is the number of days until `valid_until` (negative once expired, `null` if the completion never expires).

The dashboard matrix is cached until the end of the day. Saving or deleting a related model clears the cache automatically ([FlushesTrainingMatrixCache](app/Models/Concerns/FlushesTrainingMatrixCache.php)).

## Domain model

| Model                | Description                                                                                        |
|----------------------|----------------------------------------------------------------------------------------------------|
| `User`               | A crew member. Can have several job titles.                                                        |
| `JobTitle`           | A role such as Pilot or Flugbegleiter. Requires a set of trainings.                                |
| `Training`           | A training with `validity_months` (`null` = never expires), `renewal_months` and `end_of_month_expiry`. |
| `TrainingCompletion` | A completed training of a user: `completed_at`, `valid_until`, `renewal_from`.                     |

Pivot tables: `job_title_user` (users ↔ job titles) and `job_title_training` (job titles ↔ trainings).

## Sample data

[TrainingDataSeeder](database/seeders/TrainingDataSeeder.php) reads these files from the project root:

| File              | Contents                                                     |
|-------------------|--------------------------------------------------------------|
| `job_title.json`  | Job titles                                                   |
| `training.json`   | Trainings and the job titles that require them               |
| `pilot.json`      | Crew members (seeded as users) and their job titles          |

The ids in the JSON files only link the files to each other. The database assigns its own primary keys. [DatabaseSeeder](database/seeders/DatabaseSeeder.php) also creates a `test@example.com` user.

## Testing and code style

Run the Pest test suite inside Sail:

```bash
./vendor/bin/sail pest
```

Run a subset of the tests:

```bash
./vendor/bin/sail pest --filter=DashboardMatrix
```

The tests use the separate `testing` database, which Sail's MySQL container creates automatically.

Format the code with Laravel Pint:

```bash
./vendor/bin/sail pint
```

## Project structure

```
app/
├── Enums/TrainingStatus.php          # green / yellow / red
├── Http/Controllers/Api/V1/          # API controllers
├── Http/Resources/                   # JSON response shapes
├── Models/                           # Eloquent models (+ Concerns/)
└── Services/TrainingStatusService.php  # matrix and status logic
database/
├── migrations/
├── factories/
└── seeders/                          # DatabaseSeeder, TrainingDataSeeder
routes/api.php                        # API routes (v1)
tests/Feature/                        # API and cache tests
job_title.json, training.json, pilot.json   # sample data
```
