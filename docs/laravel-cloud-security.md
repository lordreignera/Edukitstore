# Laravel Cloud production security

Use this checklist for every production environment and after every infrastructure change.

## Runtime and deployment

- Select the latest patched PHP 8.4 or 8.5 runtime available in Laravel Cloud.
- Build with `composer install --no-dev --prefer-dist --optimize-autoloader && npm ci && npm run build`.
- Deploy with `php artisan migrate --force && php artisan optimize`.
- Keep `/up` configured as the health check endpoint.
- Run `composer audit --locked` and `npm audit --omit=dev` in CI before deployment.

## Environment and secrets

Store sensitive values as Laravel Cloud secrets. Never commit a production `.env` file.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.example
LOG_LEVEL=warning

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

EDUKIT_PAYMENT_MODE=flutterwave
EDUKIT_INITIAL_ADMIN_EMAIL=private-owner-address@example.com
EDUKIT_INITIAL_ADMIN_PASSWORD=A-unique-random-password-of-16-or-more-characters-9!

FLUTTERWAVE_PUBLIC_KEY=...
FLUTTERWAVE_SECRET_KEY=...
FLUTTERWAVE_SECRET_HASH=...
```

Laravel Cloud should generate and retain `APP_KEY`. Rotate it only with a planned key-rotation procedure using `APP_PREVIOUS_KEYS`, because changing it invalidates encrypted data and sessions.

The initial administrator password is used only when the administrator account does not already exist. Sign in, replace it immediately, enable two-factor authentication or a passkey, and then remove `EDUKIT_INITIAL_ADMIN_PASSWORD` from the environment. Re-running the seeder does not reset an existing administrator password.

Use private object storage for uploaded shopping lists and partner documents. Set `DOCUMENTS_DISK` to the private Cloud storage disk. Product images may use the public disk.

## Operations

- Enable automatic database backups and test a restore regularly.
- Keep the custom domain on HTTPS and redirect HTTP traffic to HTTPS.
- Review Laravel Cloud application logs for repeated login failures, payment callback errors, and unexpected 4xx or 5xx spikes.
- Restrict Laravel Cloud project access to the smallest possible team and require multi-factor authentication for every Cloud account.
- Rotate Flutterwave, mail, database, and object-storage credentials when a staff member leaves or a secret may have been exposed.
