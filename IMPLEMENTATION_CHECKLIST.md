# NetConnect implementation checklist

Legend: [x] source written; [ ] unfinished. No runtime behavior is verified.

## A — Foundation (partial)
- [x] Modular PHP structure, environment configuration, versioned migrations.
- [x] Registration, login, password hashing, CSRF, role permissions and customer scoping.
- [x] CLI staff creation without default credentials; customer directory and package creation.
- [ ] Runtime verification and complete HTTP authorization tests.
- [ ] Staff MFA, reset tokens, staff management UI, granular permission administration, session expiry.
- [ ] Coverage circles/polygons, Leaflet/GPS, area packages and feasibility.

## B — Billing (partial)
- [x] Connection requests, invoices/items, manual payments and allocations.
- [x] Integer money, overpayment credits, historical invoice terms and subscription dates.
- [x] Customer portal, transaction uniqueness and transactional outbox.
- [ ] Verified financial workflows/concurrency tests.
- [ ] PDF invoices/receipts, statements, credit spending, refunds/approval, promotions, package changes, postpaid.

## C — Integrations (partial)
- [x] Network interface, labelled simulation, one-job worker, retry state, expiry scheduler.
- [ ] M-Pesa client, callback validation, reconciliation, unmatched payments and tests.
- [ ] MikroTik PPPoE/hotspot adapter and secure secret encryption.
- [ ] Real network reconciliation and failure/recovery verification.

## D — Operations (partial)
- [x] Tickets, replies/internal notes and status updates.
- [x] Scoped technician work orders, scheduling/completion and payment-dependent desired state.
- [x] Unique serial stock intake and transactionally exclusive assignment.
- [x] Customer-facing incident notices and local notification job logs.
- [ ] Photos/checklists/materials/test readings/acknowledgements; technician reassignment/history.
- [ ] Full inventory supplier/cost/warranty/return/rental/recovery lifecycle.
- [ ] Ticket attachments, assignment, targets/escalations and incident linkage.
- [ ] Actual device monitoring, affected-area mapping, maintenance schedules.
- [ ] Email/SMS providers, templates, preferences and reminder schedules.

## E — Hardening and delivery (partial)
- [x] Development README, backup/restore instructions, checkpoint, test source.
- [ ] Restore verification, deployment checks, security testing and all relevant runtime checks.
- [ ] Complete reports, CSV exports, date filters, ageing/churn/SLA metrics.
- [ ] Branding/settings UI, masked integration configuration, retention/account closure.
- [ ] Complete Africa/Nairobi timestamp formatting, broad pagination/filtering, Bootstrap integration.

Do not describe this checklist as completion of phases A–E. Implemented code is not tested functionality.
