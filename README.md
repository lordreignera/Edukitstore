# EduKit

EduKit is a Laravel application for school supplies, supplier products, uploaded shopping lists, payments, inventory, and delivery.

## Local setup

1. Run `composer install` and `npm install`.
2. Copy `.env.example` to `.env`, set the database and mail settings, then run `php artisan key:generate`.
3. Optionally set `EDUKIT_INITIAL_ADMIN_EMAIL` and `EDUKIT_INITIAL_ADMIN_PASSWORD` before seeding. The default seeded login remains `superadmin@edukit.test` / `password` for existing setup workflows. The account must change its password after signing in. Re-running the seeder does not reset an existing password.
4. Run `php artisan migrate --seed`, `php artisan storage:link`, and `npm run build`.
5. Run `php artisan test`.

The default seeded password is retained as requested. For production, set a unique password of at least 16 characters before the first seed, or change the password through the profile immediately after first sign-in. No migration changes existing admin passwords.

## Payments

For local testing before Flutterwave credentials are available, set `EDUKIT_PAYMENT_MODE=demo` in `.env` and run `php artisan config:clear`. The invoice then offers **Complete demo payment** after the customer reviews and confirms the itemized total. Demo payments collect no money, are labeled on the invoice, and are excluded from financial reports. Demo mode runs only when `APP_ENV` is `local` or `testing`; production cannot enable it. Demo orders consume test inventory, so use local test data.

When credentials arrive, set `EDUKIT_PAYMENT_MODE=flutterwave`, add the Flutterwave secret key and webhook hash, and run `php artisan config:clear`. Flutterwave also provides its own [test mode](https://developer.flutterwave.com/docs/testing) for testing the real checkout integration without real money.

Set `FLUTTERWAVE_SECRET_KEY` and `FLUTTERWAVE_SECRET_HASH`. Configure the Flutterwave dashboard webhook URL as `/api/payments/flutterwave/webhook` with the same secret hash. Flutterwave's [webhook guide](https://developer.flutterwave.com/docs/webhooks) describes the `verif-hash` header. The callback and webhook both re-query the transaction verification API before marking an invoice paid.

Each checkout has a separate payment-attempt record, so a late callback from an earlier attempt can still be reconciled. The verified amount and currency must match the checkout exactly. Stock failures, mismatches, duplicate charges, and payments arriving after an order closes are flagged in the admin invoice view. Do not dispatch orders with an unresolved payment exception. The webhook must be configured for payments to complete when the customer does not return to the site.

## Sessions and invoice access

Use the configured database session driver (`SESSION_DRIVER=database`). Signing out deletes that user's browser sessions, including sessions in other browsers, and revokes API tokens. Deactivating an account also rotates its remember-me token. Authenticated page responses use no-store headers.

Customers receive invoice access in the submitting browser session. To reopen an invoice later or in another browser, use **Track Order** with the reference and matching phone number or email. A reference alone cannot open an invoice.

## Operational flows

- Uploaded lists require itemized catalogue lines and prices before an admin can release a quote. Totals are calculated from the lines and convenience fee.
- Supplier restock submissions keep existing approved stock available while the new quantity is reviewed.
- Drivers can confirm only paid orders that are ready for delivery and have no payment exception.
- Paid orders cannot be cancelled through the ordinary status form. Refunds require a separate verified provider process.
- Archiving a product removes it from the public catalogue while retaining stock batches and invoice history. Admins can reactivate it by editing the product.
