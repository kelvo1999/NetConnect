# Verification record

Status: runtime checks blocked, not passed.

The authoring environment has Python and Node but no PHP interpreter, MySQL server/client or Composer, and no cached PHP packages. No existing app or database was available. No live financial or network connection was attempted.

## Available tests

- `tests/run.php`: executable checks for integer money parsing/formatting, Kenyan phone normalization, active and expired renewal dates, and simulation state labelling.
- `tests/integration.php`: disposable database checks for partial payments, overpayment credits, duplicate manual payment idempotency, invoice fulfillment and outbox rows.

Both remain UNRUN. Syntax lint and migration execution also remain UNRUN.

## Still required

HTTP tests for registration, CSRF, login throttling, staff authorization, cross-customer access, technician scope and internal-note visibility. Concurrent payment and worker recovery tests. Installation/payment ordering tests. Full ticket transitions and equipment assignment race tests. Daraja duplicate/out-of-order callback tests and router failure/recovery tests after those adapters exist. Upload validation/authorization after attachments are implemented. Browser testing on desktop and mobile. Restore rehearsal.

Static file/table-reference inspection is not a replacement for these tests.
