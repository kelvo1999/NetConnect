# NetConnect — development implementation

This is an **incomplete, unverified development build**, not a production-ready ISP system. It was created in an empty workspace from the supplied requirements. No existing project or customer data was present. No deployment or real router/payment action was attempted.

## Prerequisites and XAMPP installation

PHP 8.2+, MySQL 8+, PDO MySQL and mbstring are required. Composer metadata is included, but no third-party PHP dependencies are currently used. Bootstrap and Leaflet are not included yet; the current UI uses local CSS.

1. Extract the project to `C:/xampp/htdocs/netconnect`.
2. Create a new empty MySQL database named `netconnect` with `utf8mb4`, and create an application database user. Do not reuse or migrate a populated database without a backup.
3. Copy `.env.example` to `.env` and configure the database credentials and URL. Never commit `.env`.
4. In the project directory run `C:/xampp/php/php.exe bin/console.php migrate`.
5. Create an administrator with the PowerShell command below. There are no default passwords.
6. Optionally run `C:/xampp/php/php.exe bin/console.php seed-demo` to create a separately labelled demo package.
7. Browse to `http://localhost/netconnect/public/` for local development only. On a server, the document root **must be the `public` directory**, with all other project files outside the document root. Do not expose the XAMPP htdocs parent over a public network.

### First administrator and staff accounts on Windows

Run from PowerShell, replacing the non-secret fields. The password is prompted rather than included in command history or process arguments. Change `role` to `admin`, `finance`, `support`, `technician` or `network_operator` for additional staff. Staff management UI is not implemented.

```powershell
$secure = Read-Host 'New password (at least 12 characters)' -AsSecureString
$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    @{name='Your Name';email='you@example.com';phone='0712345678';role='super_admin';password=$plain} | ConvertTo-Json -Compress | & C:/xampp/php/php.exe bin/console.php create-user-stdin
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
    Remove-Variable plain -ErrorAction SilentlyContinue
}
```

On Unix, `php bin/console.php admin` interactively prompts with hidden password entry.

## Implemented source paths

- `app/bootstrap.php`: configuration, prepared queries, transactions, audit, queue, permissions.
- `app/Domain.php`: integer-cent money, Kenyan phone normalization, renewal dates, manual payment allocation, credit recording, transactional job outbox, simulation interface.
- `public/index.php`: login, registration, role checks, customer scoping, packages, connection requests, invoices, manual payments, tickets and comments, work orders, equipment, notices, audit views.
- `bin/console.php`: migrations, secure staff creation, separate demo seed, queue and expiry scheduler.
- `database/migrations`: versioned schema; migration DDL is restartable but not transactionally atomic in MySQL.

## Billing and state rules

All money is an integer number of KES cents. Parsing rejects excess decimal precision. There are no financial floating-point calculations. Invoices retain package name, price and duration snapshots. Packages are currently create-only.

An active prepaid subscription extends from its existing expiry. An expired or new subscription starts at payment processing time. An invoice is fulfilled only once after full allocation; partial payments do not renew it. Overpayments create a separate credit record. Applying credits to later invoices and refund approval are **not implemented**. A duplicate manual transaction reference is accepted only if invoice and amount match; database uniqueness prevents duplicate records.

Installation completion and payment clearance are independent. Payment can extend validity while desired access remains suspended until installation completion. Scheduler expires active connections and queues reconciliation. Simulation confirmations always begin with `simulated:` and never claim a real router state. Notifications are local database logs, not sent emails or SMS. Jobs are committed with payments and get bounded retries.

The queue has a ten-minute stale-lock recovery timeout. Only the local simulation adapter is implemented; this is **not a safe distributed real-router worker**. A real adapter needs fencing, request timeouts, credential protection, latest-state reconciliation and hardware failure tests before enablement.

## Worker and scheduler

```sh
php bin/console.php worker
php bin/console.php schedule
```

The worker consumes one eligible job per invocation. For local testing, repeat it until the queue is empty. Use a supervised loop for development or Windows Task Scheduler to invoke it frequently. Run the scheduler once a minute. Do not run multiple real-device workers; no real adapter exists.

## Verification

```sh
php tests/run.php
# Unix PHP syntax checks:
find app public bin tests -name '*.php' -exec php -l {} \;
# Disposable, empty MySQL database only:
NETCONNECT_TEST_DSN='mysql:host=127.0.0.1;dbname=netconnect_test;charset=utf8mb4' php tests/integration.php
```

Actual results in this environment: **PHP lint, domain tests, database migrations, integration tests, HTTP tests and browser checks were not run** because PHP, MySQL and Composer are absent. Static inspection found the expected source files, table references and explicit development labels. That inspection does not verify syntax or behavior. See `docs/VERIFICATION.md`.

## Integration status

M-Pesa: not implemented. No STK Push or callback route exists. Do not point a live Daraja callback at this build. Work remaining includes sandbox OAuth/STK/query clients, payment request records, callback verification and reconciliation, idempotent transactions, unmatched payments and duplicate/out-of-order callback tests. Credentials alone will not enable it.

MikroTik: not implemented. Only `NetworkProvider` and `SimulationProvider` exist. A RouterOS PPPoE/hotspot adapter, protected credentials, profile mappings, connectivity checks and hardware tests are still required. Any `NETWORK_DRIVER` other than `simulation` causes the worker to refuse execution.

## Production restriction

Do not expose this build as a production service. Staff MFA, password resets, full security tests, TLS setup, recovery validation, role administration, signed-in session timeout and full retention controls remain unfinished. User-generated attachments are not accepted; no insecure placeholder upload endpoint exists. Some lists are capped at 100 records; general pagination/export is incomplete. Record timestamps are labelled UTC; complete Africa/Nairobi display conversion is pending.

## Backup and restoration

See `docs/OPERATIONS.md`. No restoration was verified here. Preserve the source, `.env` securely, database dumps and private files separately. Never include secrets or live database exports in shared source archives.

## Progress

`IMPLEMENTATION_CHECKLIST.md` and `docs/CHECKPOINT.md` distinguish code present from tested behavior and list remaining requirements. The original specification is in `docs/REQUIREMENTS.txt`.
