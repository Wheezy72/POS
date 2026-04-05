# Duka-App POS

High-density Kenyan retail POS built on Laravel 13, Inertia.js + Vue 3, and Filament.

## What is included

- Scanner-first POS terminal at `/pos`
- Filament administrative backoffice at `/admin`
- M-PESA dual path support:
  - STK push initiation and polling
  - C2B live feed webhook intake and claim flow
- Margin-floor and expiry-markdown pricing engine
- Feature toggles shared to Inertia
- Credit ledger for pay-later sales
- Blind shift close workflow
- ESC/POS printer job scaffold
- Seeded Kenyan product catalog and historical analytics data

## Stack

- PHP 8.3+
- Laravel 13
- Inertia.js
- Vue 3 Composition API
- Filament 4
- SQLite by default

## Quick start

### 1) Install dependencies

```bash
composer install
npm install
```

### 2) Prepare environment

```bash
cp .env.example .env
php artisan key:generate
mkdir -p database
touch database/database.sqlite
```

Then confirm your `.env` uses SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/project/database/database.sqlite
```

If you keep the default Laravel relative path, Laravel will also resolve `database/database.sqlite` correctly.

### 3) Migrate and seed

```bash
php artisan migrate:fresh --seed
```

This seeds:

- 50+ Kenyan retail products
- demo customers
- 200+ historical sales
- default system feature toggles
- pending inbound M-PESA feed records

### 4) Start the app

Run Laravel:

```bash
php artisan serve
```

Run Vite:

```bash
npm run dev
```

Optional queue worker for background jobs like cloud sync and receipt printer scaffolding:

```bash
php artisan queue:work
```

## Access points

- POS terminal: `http://127.0.0.1:8000/pos`
- Filament admin: `http://127.0.0.1:8000/admin`

## Demo credentials

### POS PINs

- Cashier: `0000`
- Admin: `1234`
- Manager: `2468`

The POS endpoint only admits cashier accounts.

### Seeded backoffice users

The seeder creates admin and manager accounts. If your environment uses the seeded passwords directly, they are:

- `finance@duka.app`
- `manager@duka.app`
- `cashier@duka.app`

## Day 2 feature toggles

These are stored in the `system_settings` table and shared globally to Inertia as `page.props.settings`.

Default keys:

- `enable_credit_sales = true`
- `enable_etims = false`
- `enable_loyalty_points = true`
- `enable_hardware_printer = false`

You can update them from Tinker:

```bash
php artisan tinker
```

```php
\App\Models\SystemSetting::updateOrCreate(
    ['key' => 'enable_hardware_printer'],
    ['value' => 'true']
);
```

## POS keyboard map

- `F1` New sale
- `F2` Search
- `F4` Pay
- `F7` Pay later / credit tab when enabled
- `F8` Refocus barcode input
- `F10` Logout
- `Esc` Close modal instantly

## Credit sales

When `enable_credit_sales` is enabled:

- the POS shows the `[F7] Pay Later` tab
- the cashier must enter a customer phone number
- the backend resolves the customer via normalized Kenyan phone
- the sale is blocked if the resulting balance exceeds the customer's credit limit
- the debt is written to `customer_ledgers`

## Blind shift close

Open a shift:

```bash
curl -X POST http://127.0.0.1:8000/api/shifts/open \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: <token>" \
  -b cookie.txt -c cookie.txt \
  -d "opening_cash=200"
```

Close a shift:

```bash
curl -X POST http://127.0.0.1:8000/api/shifts/close \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: <token>" \
  -b cookie.txt -c cookie.txt \
  -d "counted_cash=470"
```

The cashier submits only the counted cash. The server calculates expected cash and variance privately.

## Hardware receipt printing

When `enable_hardware_printer` is enabled, checkout dispatches a queued job that calls `ReceiptPrinterService`.

Current behavior:

- builds an ESC/POS byte payload
- logs the raw hex payload
- leaves the final USB/network adapter implementation for the next iteration

## Tests

Run the relevant feature tests:

```bash
php artisan test --filter=PosAuthenticationTest
php artisan test --filter=PosCheckoutPricingTest
php artisan test --filter=AdminDashboardTest
php artisan test --filter=DayTwoOperationsTest
```

Or run the full suite:

```bash
php artisan test
```

## Production notes

- configure real M-PESA credentials in `.env`
- run a queue worker for STK follow-up and printer jobs
- replace SQLite with MySQL or PostgreSQL for multi-terminal shops
- point the receipt printer scaffold to the actual USB, serial, or network transport
- enable HTTPS so POS sessions and CSRF tokens are protected in transit
