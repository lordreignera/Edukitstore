# EduKit

EduKit is a Laravel application for school supplies, supplier products, uploaded shopping lists, payments, inventory, and delivery.

For the complete implementation map, including technology versions, role-to-route/controller/view alignment, customer/order/payment flows, data model, authentication security, and current gaps, see [the EduKit system overview](docs/edukit-system-overview.md).

## Local setup

1. Run `composer install` and `npm install`.
2. Copy `.env.example` to `.env`, set the database and mail settings, then run `php artisan key:generate`.
3. For local demo accounts, keep `EDUKIT_SEED_DEMO_ACCOUNTS=true`, then run `php artisan migrate --seed`, `php artisan storage:link`, and `npm run build`.
4. In local/testing environments, the seed creates demo accounts for each EduKit workspace. Every demo account is active, assigned its role, and shown a reminder to replace its temporary password. Production and staging skip these accounts by default. If the deployed site is intentionally using the same shared accounts, set both `EDUKIT_SEED_DEMO_ACCOUNTS=true` and `EDUKIT_ALLOW_DEMO_ACCOUNTS_ONLINE=true`, then run the seeder on the deployed database.
5. Run `php artisan test`.

The local demo credentials are:

| Workspace | Email | Temporary password |
| --- | --- | --- |
| Super administrator | `superadmin@edukit.test` | `Edukit@Super2026!Secure` |
| Administrator | `admin@edukit.test` | `Edukit@Admin2026!Secure` |
| Finance administrator | `finance@edukit.test` | `Edukit@Finance2026!Secure` |
| Supplier | `supplier@edukit.test` | `Edukit@Supplier2026!Secure` |
| Delivery partner | `driver@edukit.test` | `Edukit@Driver2026!Secure` |
| School administrator | `schooladmin@edukit.test` | `Edukit@School2026!Secure` |
| School receiver | `receiver@edukit.test` | `Edukit@Receiver2026!Secure` |
| Parent | `parent@edukit.test` | `Edukit@Parent2026!Secure` |

Re-running the seeder updates only accounts that still have `must_change_password=true`; it does not reset a password already changed by a user.

For an explicitly enabled deployed site, run the following after setting those two environment variables:

```powershell
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=RolesAndSuperAdminSeeder --force
php artisan config:cache
```

These are shared temporary credentials. Change them immediately after the first online login, or disable online demo access when they are no longer needed.

First login opens the assigned role workspace directly: admin, finance profit report, supplier, delivery partner, school staff, or parent. An account still using its temporary password sees a security reminder linking to the optional change-password form; changing the password is recommended but not required to enter the workspace. `/dashboard` remains a role-aware compatibility dispatcher for existing links and verification flows. Finance cannot open admin management pages. School and parent workspaces link to the existing catalogue, list-upload, and reference-based order-tracking flows; school and order records are not yet linked directly to those user accounts.

## Install EduKit as an app

EduKit includes a Progressive Web App (PWA) manifest and service worker. On a supported browser, open the site over HTTPS (or `http://127.0.0.1` during local development), then use the browser's **Install EduKit** or **Add to Home Screen** option. Android and desktop Chrome/Edge can install it directly; on iPhone/iPad Safari, use **Share → Add to Home Screen**. Private role pages are never cached by the service worker, so installation does not weaken login, logout, or session-expiry protections.

## Payments

For local testing before Flutterwave credentials are available, set `EDUKIT_PAYMENT_MODE=demo` in `.env` and run `php artisan config:clear`. The invoice then offers **Complete demo payment** after the customer reviews and confirms the itemized total. Demo payments collect no money, are labeled on the invoice, and are excluded from financial reports. Demo mode runs only when `APP_ENV` is `local` or `testing`; production cannot enable it. Demo orders consume test inventory, so use local test data.

When credentials arrive, set `EDUKIT_PAYMENT_MODE=flutterwave`, add the Flutterwave secret key and webhook hash, and run `php artisan config:clear`. Flutterwave also provides its own [test mode](https://developer.flutterwave.com/docs/testing) for testing the real checkout integration without real money.

Set `FLUTTERWAVE_SECRET_KEY` and `FLUTTERWAVE_SECRET_HASH`. Configure the Flutterwave dashboard webhook URL as `/api/payments/flutterwave/webhook` with the same secret hash. Flutterwave's [webhook guide](https://developer.flutterwave.com/docs/webhooks) describes the `verif-hash` header. The callback and webhook both re-query the transaction verification API before marking an invoice paid.

Each checkout has a separate payment-attempt record, so a late callback from an earlier attempt can still be reconciled. The verified amount and currency must match the checkout exactly. Stock failures, mismatches, duplicate charges, and payments arriving after an order closes are flagged in the admin invoice view. Do not dispatch orders with an unresolved payment exception. The webhook must be configured for payments to complete when the customer does not return to the site.

## Sessions and invoice access

Use the configured database session driver (`SESSION_DRIVER=database`). Successful logins create a row in `sessions` with the user ID and last activity; `last_login_at` is recorded only after authentication, including any two-factor challenge, succeeds. Sessions expire after `SESSION_LIFETIME` minutes of inactivity (120 in the supplied local configuration). Signing out invalidates the current session and remember-me cookie, deletes that user's other database browser sessions, and revokes API tokens. Deactivating an account also rotates its remember-me token and revokes sessions. Protected pages and rejected access responses use `no-store` and expired cache headers; private pages reload after browser Back/Forward restoration so the server checks access again. The public storefront remains accessible after logout.

Customers receive invoice access in the submitting browser session. To reopen an invoice later or in another browser, use **Track Order** with the reference and matching phone number or email. A reference alone cannot open an invoice.

## Operational flows

- Uploaded lists require itemized catalogue lines and prices before an admin can release a quote. Totals are calculated from the lines and convenience fee.
- Supplier restock submissions keep existing approved stock available while the new quantity is reviewed.
- Drivers can confirm only paid orders that are ready for delivery and have no payment exception.
- Paid orders cannot be cancelled through the ordinary status form. Refunds require a separate verified provider process.
- Archiving a product removes it from the public catalogue while retaining stock batches and invoice history. Admins can reactivate it by editing the product.
