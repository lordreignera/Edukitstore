# Laravel Cloud production security

Use this checklist for every production environment and after every infrastructure change.

## Runtime and deployment

- Select PHP 8.5.12 or newer (or PHP 8.4.27 or newer) in Laravel Cloud. Do not deploy on PHP 8.2.18 or another pre-8.2.35 runtime; PHP 8.2 is also approaching end of security support.
- Build with `composer install --no-dev --prefer-dist --optimize-autoloader && npm ci && npm run build`.
- Deploy with `php artisan migrate --force && php artisan optimize`.
- Keep `/up` configured as the health check endpoint.
- Run `composer audit --locked` and `npm audit --omit=dev` in CI before deployment.

The repository requires PHP `^8.4`. Upgrade local development PHP before running a fresh Composer install; an existing vendor directory may still run tests on an older local runtime, but production must use a patched supported PHP release.

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

FLUTTERWAVE_PUBLIC_KEY=...
FLUTTERWAVE_SECRET_KEY=...
FLUTTERWAVE_SECRET_HASH=...
```

Laravel Cloud should generate and retain `APP_KEY`. Rotate it only with a planned key-rotation procedure using `APP_PREVIOUS_KEYS`, because changing it invalidates encrypted data and sessions.

The demo account seeder is disabled in production. Provision a production super administrator separately before launch, using a unique password, then enable two-factor authentication or a passkey.

Use private object storage for uploaded shopping lists and partner documents. Set `DOCUMENTS_DISK` to the private Cloud storage disk. Product images may use the public disk.

## Operations

- Enable automatic database backups and test a restore regularly.
- Keep the custom domain on HTTPS and redirect HTTP traffic to HTTPS.
- Review Laravel Cloud application logs for repeated login failures, payment callback errors, and unexpected 4xx or 5xx spikes.
- Restrict Laravel Cloud project access to the smallest possible team and require multi-factor authentication for every Cloud account.
- Rotate Flutterwave, mail, database, and object-storage credentials when a staff member leaves or a secret may have been exposed.
