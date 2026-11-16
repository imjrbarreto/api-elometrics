# EloMetrics API

REST API for managing chess training through workspaces, members, and shared training sessions.

Supports workspace roles, student responses, and individual training session exports as PDF.

## Tech Stack

- PHP 8.5
- Laravel 13
- Laravel Passport
- Spatie Laravel Permission with Teams
- Pest
- L5-Swagger
- Laravel Dompdf
- MySQL for development
- SQLite for testing

## Installation

Clone the repository and enter the project directory:

```bash
git clone https://github.com/imjrbarreto/api-elometrics.git
cd api-elometrics
```

Install dependencies:

```bash
composer install
```

Create the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

### Database

Create a MySQL database and configure your credentials in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elometrics_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations and initial seeders:

```bash
php artisan migrate --seed
```

### Authentication

Generate the Passport keys:

```bash
php artisan passport:keys
```

Create a personal access client:

```bash
php artisan passport:client --personal --provider=users
```

These steps are required during initial setup. You do not need to create a new client every time you start the application.

### Start the API

```bash
php artisan serve
```

Local API base URL:

```text
http://127.0.0.1:8000/api/v1
```

This URL is the prefix for API endpoints and does not provide a standalone page. Use Swagger to explore and test the available endpoints.

## API Documentation

Generate the Swagger documentation:

```bash
php artisan l5-swagger:generate
```

With the application running, open:

```text
http://127.0.0.1:8000/api/documentation
```

To access protected endpoints:

1. Log in using the login endpoint.
2. Copy the `access_token` from the response.
3. Click **Authorize** and enter the token.
4. Execute requests as the authenticated user.

Replace the token in **Authorize** when switching users.

## Demo Data

Load the local demo scenario:

```bash
php artisan db:seed --class=DemoSeeder
```

The seeder creates a workspace named **Demo: grupo avanzado** and four accounts:

| Role | Email | Initial password |
| --- | --- | --- |
| Owner | owner@elometrics.test | password123 |
| Coach | coach@elometrics.test | password123 |
| Student | student@elometrics.test | password123 |
| Viewer | viewer@elometrics.test | password123 |

These credentials are intended for local testing.

The demo includes:

- **Finales de torres**, a training session created by the owner.
- **Cálculo: jugadas candidatas**, a training session created by the coach.
- Both sessions linked to the demo workspace.
- A student response to the calculation session.

The endgame session is available for testing a new student response.

Running the seeder again does not duplicate demo records. Existing account passwords are preserved.

### Manual Testing

1. Log in with a demo account.
2. Request `GET /api/v1/workspaces`.
3. Use the returned workspace ID to list its linked sessions.
4. Select a linked session and export it as PDF.

Use the actual IDs returned by the API.

## Workspace Roles

Roles are assigned per workspace. A user can have different roles in different workspaces.

| Action | Owner | Coach | Student | Viewer |
| --- | --- | --- | --- | --- |
| View members and linked sessions | Yes | Yes | Yes | Yes |
| Add or remove members, subject to membership rules | Yes | Yes | No | No |
| Link their own sessions | Yes | Yes | No | No |
| Unlink sessions | Yes | Yes | No | No |
| Respond to linked sessions | No | No | Yes | No |
| Export linked sessions | Yes | Yes | Yes | Yes |
| Update or delete the workspace | Yes | No | No | No |
| Leave the workspace | No | Yes | Yes | Yes |

The workspace owner cannot be removed.

## Main Endpoints

All endpoints use the `/api/v1` prefix.

### Authentication

| Method | Route | Action |
| --- | --- | --- |
| POST | `/register` | Register a user |
| POST | `/login` | Log in |
| GET | `/users/me` | Get the authenticated user |
| POST | `/logout` | Log out |

### Workspaces

| Method | Route | Action |
| --- | --- | --- |
| GET | `/workspaces` | List owned workspaces and workspace memberships |
| POST | `/workspaces` | Create a workspace |
| GET | `/workspaces/{workspace}` | Get workspace details |
| PATCH | `/workspaces/{workspace}` | Update a workspace |
| DELETE | `/workspaces/{workspace}` | Delete a workspace |

### Members

| Method | Route | Action |
| --- | --- | --- |
| GET | `/workspaces/{workspace}/members` | List members |
| POST | `/workspaces/{workspace}/members` | Add a member by email and role |
| DELETE | `/workspaces/{workspace}/members/{user}` | Remove a member or leave the workspace |

### Personal Training Sessions

| Method | Route | Action |
| --- | --- | --- |
| GET | `/training-sessions` | List personal sessions |
| POST | `/training-sessions` | Create a session |
| GET | `/training-sessions/{training_session}` | Get session details |
| PATCH | `/training-sessions/{training_session}` | Update a session |
| DELETE | `/training-sessions/{training_session}` | Delete a session |

### Workspace Training Sessions

| Method | Route | Action |
| --- | --- | --- |
| GET | `/workspaces/{workspace}/training-sessions` | List linked sessions |
| POST | `/workspaces/{workspace}/training-sessions` | Link an existing personal session |
| DELETE | `/workspaces/{workspace}/training-sessions/{training_session}` | Unlink a session |
| POST | `/workspaces/{workspace}/training-sessions/{training_session}/responses` | Submit a student response |
| GET | `/workspaces/{workspace}/training-sessions/{training_session}/export` | Download a session as PDF |

A session can be linked to multiple workspaces. Unlinking preserves the original session and its links to other workspaces.

Each student can submit one response per session and workspace.

PDF exports contain only the selected session and are available to all four workspace roles.

Request bodies and responses are documented in Swagger.

## Tests

The test suite uses Pest and an in-memory SQLite database, as configured in the project's testing environment.

Run all tests:

```bash
php artisan test
```

The suite covers authentication, workspaces, permissions, members, training sessions, responses, PDF exports, and seeders.