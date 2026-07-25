# Local Setup

## Requirements

- PHP compatible with the Laravel version installed.
- Composer.
- Git.

## Setup Steps

1. Install Composer dependencies:

   ```bash
   composer install
   ```

2. Create `.env` if it does not exist:

   ```bash
   cp .env.example .env
   ```

3. Generate the application key if needed:

   ```bash
   php artisan key:generate
   ```

4. Start the local server:

   ```bash
   php artisan serve
   ```

5. Open the URL shown in the terminal.

## Database

Laravel's default SQLite configuration may be used for local setup. No custom Phase 0 migrations are required.
