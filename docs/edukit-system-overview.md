# EduKit system overview

This document describes the EduKit application as implemented in the codebase on 1 November 2026. It is intended as a shared reference for development, testing, handover, and future feature work.

## 1. What EduKit does

EduKit is a school-supplies marketplace and fulfilment system. It connects:

- Parents and other customers who need school supplies.
- Schools that receive school-supply deliveries.
- EduKit's own catalogue and inventory.
- Approved suppliers who can list and supply products.
- Approved delivery personnel who deliver paid orders.
- Administrators who review lists, manage stock, approve suppliers and drivers, and monitor profit.

The system supports two customer order sources:

1. A customer selects products from the public catalogue and submits a cart.
2. A customer uploads a school shopping list for EduKit staff to review and price.

Both sources become a `ShoppingList` record. The same record acts as the customer request, quotation/invoice, payment record, fulfilment record, and delivery record.

## 2. Technology and versions

| Area | Implementation |
| --- | --- |
| Main language | PHP |
| PHP version | Composer requires PHP `^8.4`; local CLI verified as PHP `8.4.27` |
| Application framework | Laravel `^12.0`; local Artisan verified as Laravel `12.70.1` |
| Authentication/UI platform | Laravel Jetstream `^5.5` with Fortify authentication |
| API authentication | Laravel Sanctum `^4.0` |
| Roles and permissions | Spatie Laravel Permission `^6.25` |
| Reactive UI dependency | Livewire `^3.6.4` |
| Server-rendered UI | Blade templates, with Jetstream components |
| Front-end build | Vite `^7.0.7`, Axios, Tailwind CSS, Tailwind Vite integration |
| Database | Local `.env` uses MySQL; `.env.example` defaults to SQLite |
| Session storage | Database sessions |
| Payment provider | Flutterwave in normal operation; demo payment mode for local/testing |
| File storage | Laravel filesystem; local disk by default, S3-compatible disk supported |
| Test framework | PHPUnit `^11.5.50` through Laravel's test runner |
| Runtime tooling verified | Node.js `22.14.0` |

The application is a conventional Laravel monolith. Routes call controllers, controllers call domain services, services update Eloquent models inside database transactions, and Blade views render the role-specific pages.

## 3. Main code areas

| Location | Responsibility |
| --- | --- |
| `routes/web.php` | Public storefront, authenticated workspaces, role restrictions, admin operations |
| `routes/api.php` | Flutterwave webhook and Sanctum user endpoint |
| `app/Http/Controllers/Website` | Public catalogue, cart, upload-list, payment, tracking, and onboarding flows |
| `app/Http/Controllers/Admin` | Admin dashboard, orders, catalogue, inventory, suppliers, drivers, schools, users, reports |
| `app/Http/Controllers/Supplier` | Supplier stock, offers, restocking, and earnings |
| `app/Http/Controllers/Driver` | Driver dashboard, availability, and delivery journey updates |
| `app/Http/Controllers/School` | School staff workspace landing page |
| `app/Http/Controllers/Parent` | Parent workspace landing page |
| `app/Services` | Payment, invoice, inventory, marketplace, onboarding, and supplier-sale business rules |
| `app/Models` | Eloquent entities and relationships |
| `app/Support` | Cross-cutting rules such as account revocation and invoice access |
| `database/migrations` | Users, roles, schools, catalogue, suppliers, inventory, orders, payments, and delivery fields |
| `resources/views/website` | Public storefront and customer pages |
| `resources/views/admin` | Admin management pages |
| `resources/views/supplier`, `driver`, `school`, `parent`, `finance` | Authenticated role workspaces |
| `resources/views/components` | Shared layouts, navigation, security reminder, and form components |

## 4. Login and role routing

After a successful login, Fortify sends the user directly to the role workspace. `/dashboard` remains a role-aware compatibility dispatcher for existing links and verification flows; it is not the actual workspace for most users.

| Role | Workspace URL | Controller | Main view | Access boundary |
| --- | --- | --- | --- | --- |
| `super-admin` | `/admin` | `Admin\DashboardController` | `admin.dashboard` | Full admin route group |
| `admin` | `/admin` | `Admin\DashboardController` | `admin.dashboard` | Full admin route group |
| `finance-admin` | `/finance` | `Admin\ProfitReportController` | `finance.dashboard` | Profit report only |
| `supplier` | `/supplier` | `Supplier\DashboardController` | `supplier.dashboard` | Own approved supplier record and supplier routes |
| `delivery-person` | `/driver` | `Driver\DashboardController` | `driver.dashboard` | Own approved driver record and assigned deliveries |
| `school-admin` | `/school` | `School\DashboardController` | `school.dashboard` | School workspace landing page |
| `school-receiver` | `/school` | `School\DashboardController` | `school.dashboard` | School workspace landing page |
| `parent` | `/parent` | `Parent\DashboardController` | `parent.dashboard` | Parent workspace landing page |

The legacy `driver` role is retained for compatibility with older records, but the current driver routes require `delivery-person`. New driver accounts are seeded or provisioned with `delivery-person`.

The `super-admin` and `admin` roles currently share the same route group. The role names are different, but there is not yet a separate permission policy that gives super-admin extra routes or restricts admin from a subset of admin routes.

The role landing pages are not `/user/profile`. The current application route list does not expose `/user/profile` as a workspace route. Jetstream profile-related view files still exist as framework assets, but the login flow sends users to their role workspace.

## 5. Public customer flow

### 5.1 Browse and select products

1. Visitor opens `/` or `/products`.
2. `Website\HomeController` and `Website\ProductController` load active products and categories.
3. A product can be fulfilled from EduKit stock or an approved supplier offer.
4. `MarketplaceSourceService` selects the source, price, and available quantity.
5. The selected quantity and source are stored in the browser session cart.

Relevant routes include:

- `website.home`
- `website.products.index`
- `website.products.show`
- `website.cart.index`
- `website.cart.store`
- `website.cart.update`
- `website.cart.destroy`

### 5.2 Submit a cart

1. The customer enters name, phone, optional email, district, school/delivery details, and notes.
2. `SchoolDeliveryService` validates the delivery destination and calculates the EduKit delivery fee.
3. Supplier-direct items must be delivered to a selected school.
4. Supplier delivery fees are added for supplier-sourced items.
5. A `ShoppingList` is created with `source=cart`, `status=quoted`, and `payment_status=unpaid`.
6. Immutable line-item snapshots are created in `ShoppingListItem` records.
7. The cart session is cleared.
8. Temporary invoice access is granted to the submitting browser session.
9. The customer is sent to the invoice/quote page.

### 5.3 Upload a shopping list

1. Customer opens `/upload-list`.
2. Customer submits contact, delivery, and school details with a JPG, PNG, PDF, DOC, DOCX, XLS, or XLSX file.
3. The document is stored through Laravel's configured document disk.
4. A `ShoppingList` is created with `source=upload`, `status=pending`, and `payment_status=unpaid`.
5. The customer receives a reference number and temporary invoice access.
6. Admin reviews the file, enters catalogue lines and prices, adds the delivery fee, and releases the quote.

### 5.4 View, track, and pay

The invoice is shown by `website.quote.show`. A reference alone is not enough to reopen an invoice later. The customer can use `/track-order` with the reference and matching phone number or email. A successful lookup grants access in the current session.

Payment states are separate from order states:

- `unpaid`
- `pending`
- `paid`
- `failed`
- `refunded`

For local/testing demo mode:

1. The customer confirms the itemized total.
2. `DemoPaymentService` records a demo payment attempt.
3. EduKit inventory and supplier-sale records are updated.
4. The invoice is marked paid with provider `demo`.
5. No money is collected.

For Flutterwave:

1. EduKit checks display stock and supplier stock before checkout.
2. A unique `PaymentAttempt` and transaction reference are created.
3. The customer is redirected to Flutterwave.
4. The callback and webhook both re-query Flutterwave's verification API.
5. The transaction reference, amount, and currency must match the local attempt.
6. A verified payment records stock consumption and supplier sale amounts.
7. Duplicate, late, amount-mismatch, currency-mismatch, or stock-allocation problems become payment exceptions for admin review.

The webhook endpoint is `POST /api/payments/flutterwave/webhook`.

## 6. Admin operations flow

The admin route group is protected by `auth:sanctum`, Jetstream's authenticated-session middleware, email verification, and `role:super-admin|admin`.

### 6.1 Dashboard and review queue

`/admin` loads operational counts and queues through `Admin\DashboardController`. The dashboard brings together pending customer lists, quote work, catalogue status, stock signals, supplier/driver approvals, and recent activity.

### 6.2 Invoice and shopping-list review

Admin pages are provided by:

- `Admin\InvoiceController`
- `Admin\ShoppingListController`
- `InvoiceWorkflow`

The normal uploaded-list path is:

`pending -> reviewing -> quoted -> paid -> fulfilment/delivery -> fulfilled`

An order may also be `rejected` or `cancelled`. Payment and delivery status are tracked independently. Admin can enter or replace itemized quote lines, assign a driver, update permitted statuses, inspect uploaded documents, and resolve paid-stock exceptions.

Paid orders cannot move through ordinary cancellation as if payment did not happen. Payment exceptions remain visible for investigation before dispatch.

### 6.3 Catalogue

Admin manages product categories and products through resource controllers:

- `Admin\ProductCategoryController`
- `Admin\ProductController`

Product archiving removes a product from the public active catalogue while retaining its inventory and historical invoice data. A product can later be reactivated by editing it.

### 6.4 Inventory

`Admin\InventoryController` and `InventoryService` manage:

- Opening stock.
- Supplier intake.
- Warehouse-to-display transfers.
- Paid-cart stock consumption.
- Inventory batches and cost allocation.
- Inventory movements and audit references.
- CSV import, export, and import template download.

Inventory changes are transaction-based. Stock is checked again during checkout and payment processing so that stale browser quantities cannot over-sell available stock.

### 6.5 Suppliers and supplier offers

Admins can create, edit, approve, suspend, reinstate, and download supplier records. Supplier offers are reviewed separately:

`supplier submits offer -> admin approves/rejects -> approved offer becomes marketplace stock -> customer order consumes offer quantity -> supplier sale is recorded`

Supplier accounts must be linked to an approved and active `Supplier` record before the supplier dashboard is usable.

### 6.6 Drivers and delivery assignment

Admins can create, edit, approve, set availability, and download driver records. Approval provisions or activates the linked user account. Only approved, active drivers should receive assignments.

Admin assigns a driver on the invoice/order review page. The driver then sees only their assigned deliveries.

### 6.7 Schools

Admins manage districts and schools. Schools provide delivery destinations, school-specific fees, contact data, and the relationship used by shopping lists.

### 6.8 Users and account approval

`Admin\UserController` allows the admin route group to:

- Create user accounts with a role.
- Edit user details and role.
- Activate or deactivate accounts.
- Send a password setup/reset link.

Supplier and driver public onboarding starts with an inactive pending user. Admin approval activates the account and the related supplier/driver record.

## 7. Supplier flow

The supplier route group is `/supplier` and requires the `supplier` role.

1. The supplier logs in.
2. `Supplier\DashboardController` verifies that the linked supplier is approved and active.
3. Supplier views approved products, submitted quantities, remaining quantities, recent batches, and non-demo purchase value.
4. Supplier submits a new product offer or restock request.
5. Admin reviews the offer.
6. Once approved, the offer can be selected as marketplace fulfilment stock.
7. Supplier views stock and earnings through `StockController` and `EarningsController`.

Supplier financial totals intentionally exclude demo payments.

## 8. Driver/delivery flow

The driver route group is `/driver` and requires the `delivery-person` role.

1. Driver logs in and must have a linked approved `Driver` record.
2. Driver sets availability for new assignments.
3. Driver sees only shopping lists assigned to that driver.
4. Driver can start a journey only after payment is verified, the order is quoted, no payment exception exists, and the order is not closed.
5. Driver marks arrival at the destination and can add delivery notes.
6. Customer confirms receipt using the invoice page.
7. The order becomes delivered/fulfilled after the receipt or delivery confirmation is recorded.

The delivery journey fields record who started and reached the destination, with timestamps. A driver cannot operate another driver's delivery by changing the URL.

## 9. School and parent workspaces

School staff (`school-admin`, `school-receiver`) land at `/school`. Parents (`parent`) land at `/parent`.

These are currently role-specific entry pages linking to the existing catalogue, upload-list, and reference-based order-tracking flows. They are not yet full account-owned dashboards.

Important current data limitation: `ShoppingList` stores school, learner, contact, and delivery information, but does not currently have a direct `user_id` or parent-account ownership relationship. Therefore:

- A school user cannot yet see all orders belonging to their school from a private school dashboard.
- A parent user cannot yet see a private history of all their orders by account login alone.
- Both roles use the public reference plus phone/email tracking flow for order access.

This is the main area to extend if the product requires account-based school and parent order history.

## 10. Authentication and account security flow

### 10.1 Login

Authentication is provided by Laravel Fortify/Jetstream:

1. User submits email and password.
2. Email is normalized to lowercase.
3. Inactive accounts are rejected.
4. Documented `@edukit.test` demo accounts are rejected outside local/testing environments.
5. Login attempts are rate-limited by email/IP and by account.
6. If enabled, two-factor authentication or passkey authentication completes before the login event is treated as successful.
7. `last_login_at` is recorded on the successful web login event.
8. The role-aware login response sends the user directly to the workspace described above, even if an old intended URL points to `/user/profile`.
9. `/dashboard` remains available as a compatibility dispatcher.

### 10.2 Temporary passwords

Local demo accounts and admin-provisioned accounts can have `must_change_password=true`. The role layout displays a security reminder with an optional change-password link. The user is not forced to stop at a password-change page before entering their assigned workspace.

Changing or resetting the password clears `must_change_password`.

### 10.3 Session recording and expiry

The configured session driver is `database`. The `sessions` table records the session ID, user ID, IP address, user agent, payload, and last activity. The supplied local configuration uses a 120-minute inactivity lifetime.

### 10.4 Logout, deactivation, and browser history

On logout:

- The current session is invalidated.
- The CSRF token is regenerated.
- The user's database browser sessions are deleted.
- API tokens are revoked.

On account deactivation, the remember-me token is rotated and sessions/API tokens are revoked. The account middleware also checks the current account on requests, so an account disabled in another session is forced back to login.

Authenticated responses use `no-store`, `no-cache`, and expired cache headers. Private pages also reload when restored from the browser Back/Forward cache so that the server checks the current session and role again. The public storefront remains available after logout.

## 11. Core data model

| Model/table | Purpose | Important relationships |
| --- | --- | --- |
| `User` / `users` | Login identity, active state, last login, temporary-password flag | Roles, supplier, driver, creator |
| Spatie role tables | Role assignment and permission infrastructure | User-to-role assignments |
| `District` | Delivery geography | Has many schools and shopping lists |
| `School` | School destination and fee data | District, shopping lists |
| `ProductCategory` | Catalogue grouping | Has many products |
| `Product` | EduKit catalogue item, pricing, stock summary | Category, batches, movements, order lines, supplier offers |
| `Supplier` | Supplier business and approval record | User, offers |
| `SupplierOffer` | Supplier's product/quantity/price offer | Supplier, product, approver |
| `Driver` | Delivery person approval, vehicle, availability | User, assigned shopping lists |
| `InventoryBatch` | Stock batch, source, location, cost, price | Product, supplier, movements |
| `InventoryMovement` | Auditable stock adjustment or sale movement | Product, batch, order, performer |
| `ShoppingList` | Customer request, quote/invoice, payment and delivery state | School, district, driver, line items, payment attempts |
| `ShoppingListItem` | Immutable itemized order/quote line | Shopping list, product, supplier offer, supplier |
| `PaymentAttempt` | One Flutterwave or demo payment attempt | Shopping list |

The main business record is therefore:

`User/role -> customer or operator action -> ShoppingList -> ShoppingListItem -> Product/SupplierOffer -> InventoryMovement -> PaymentAttempt -> Driver delivery -> customer receipt`

## 12. Route ownership summary

### Public routes

Owned by `Website` controllers:

- Home, product catalogue, product suggestions, cart, quote, upload-list, track-order.
- Supplier and driver onboarding.
- Flutterwave callback.
- Informational school/help/join pages.

### Authenticated role routes

Owned by role-specific controllers:

- `/admin/*` for super-admin/admin.
- `/finance` for finance-admin.
- `/supplier/*` for supplier.
- `/driver/*` for delivery-person.
- `/school` for school-admin/school-receiver.
- `/parent` for parent.

### API routes

- `POST /api/payments/flutterwave/webhook` for provider notifications.
- `GET /api/user` for an authenticated Sanctum user response.

## 13. Seeded roles and local accounts

`RolesAndSuperAdminSeeder` always ensures the application roles exist. Demo accounts are created only when both conditions are true:

- `EDUKIT_SEED_DEMO_ACCOUNTS=true`.
- The application environment is `local` or `testing`.

The local accounts cover super-admin, admin, finance-admin, supplier, delivery-person, school-admin, school-receiver, and parent workspaces. Their temporary credentials are documented in the project README and seeder for local testing only. Production does not receive these demo accounts.

The seeder preserves a password after the user changes it. It also creates the linked approved supplier and driver demo profiles required by those workspaces.

There is no configurable `EDUKIT_INITIAL_ADMIN_EMAIL/PASSWORD` bootstrap block in the current seeder. The seeder uses the explicit local demo-account switch described above.

## 14. Local setup and verification

Typical local setup:

```powershell
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

For local demo payment testing, use `EDUKIT_PAYMENT_MODE=demo`, then clear cached configuration:

```powershell
php artisan config:clear
```

For normal payment integration, use `EDUKIT_PAYMENT_MODE=flutterwave` and configure the Flutterwave secret key and webhook hash. Do not use demo credentials or demo payment mode in production.

The automated test suite currently verifies authentication, role routing, account activation, seeded-account behavior, catalogue, inventory, shopping-list, payment, supplier, driver, delivery, and security behavior. The last full run completed with 127 tests passing and 1,102 assertions.

### 14.1 Installable app support

EduKit is also packaged as a Progressive Web App. The shared Blade shells publish the orange brand theme, web-app manifest, Apple touch metadata, and a small install prompt. `public/sw.js` caches only public static assets and the offline page. Admin, finance, supplier, driver, school, parent, login, user, API, and Livewire paths are deliberately network-only; private HTML responses are not stored offline.

Users can install the app from a supported HTTPS browser using its install option. Android and desktop Chrome/Edge expose the native install flow; Safari on iPhone/iPad uses **Share → Add to Home Screen**. Local development on `127.0.0.1` is supported because browsers treat localhost origins as secure contexts.

## 15. Current gaps and next logical extensions

1. Give `school-admin` and `parent` accounts an explicit ownership relationship to schools, learners, or customer profiles.
2. Add account-scoped order history for school and parent dashboards instead of reference-based access only.
3. Define separate Spatie permissions if `super-admin` must have powers that ordinary `admin` users do not have.
4. Decide whether the legacy `driver` role should eventually be migrated and removed.
5. Add production account provisioning and email delivery configuration before deployment.
6. Add a scheduled cleanup policy for expired sessions, old payment attempts, and temporary uploaded documents where required by retention policy.
7. Perform a final manual browser test of login, logout, timeout, Back/Forward navigation, PWA installation, payment callback, and webhook behavior in the target deployment environment.
