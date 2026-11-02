# Operations — deployment gate still closed

## Backup

Before any migration against existing data, stop writes, take a database snapshot and securely back up private files plus configuration. This project was built without access to any existing database.

Use a MySQL login path or interactive password prompt, not a password in command arguments:

```sh
mysqldump --single-transaction --routines --triggers -u backup_user -p netconnect > netconnect_backup.sql
```

Encrypt the dump and store it outside the web root. Keep restore credentials and encryption keys separately. Do not upload production backups into this source project.

## Restoration rehearsal (required, NOT performed here)

1. Create an isolated restoration database with no network or payment integrations.
2. Restore a copied dump using `mysql -u restore_user -p netconnect_restore < netconnect_backup.sql`.
3. Point a separate application copy at that database, with NETWORK_DRIVER=simulation.
4. Check migration names, record counts, invoice balances, total payments, allocations and credits against recorded source values.
5. Check a sample customer can see only their own records. Replay a matching payment reference and confirm record counts and subscription expiry do not change.
6. Process a simulation job and verify simulated state and local notification logs. Verify private files separately when uploads are implemented.
7. Record actual commands, counts, timestamps and outcomes before calling restoration verified. No such result is available yet.

## Troubleshooting

- Database unavailable: verify PDO MySQL, DB_DSN, credentials, MySQL service and migrations.
- Access denied: review server-side role_permissions and account status. Technicians cannot see the customer directory or billing.
- Queue empty: pending jobs may be delayed after failures. Inspect attempts/status/available_at. Jobs fail permanently after five attempts; manual audited retry controls are pending.
- Unchanged router: expected; only a local simulation exists.
- Paid but suspended: inspect installation_status and expiry separately; payment is not installation clearance.
- Staff login: MFA is not implemented, so keep development access private.

## Deployment prerequisites

Complete remaining requirements; run all security and integration tests on supported PHP/MySQL; rehearse restoration; configure a least-privilege database user; use public/ as the document root; enforce TLS and SESSION_SECURE=1; disable PHP display_errors; restrict configuration file access; protect backups; configure monitoring and supervision. This checklist does not constitute a deployment verification.
