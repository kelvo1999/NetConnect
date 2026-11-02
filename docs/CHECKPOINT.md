# Continuation checkpoint

Current state: PHP/MySQL development source exists; no runtime available during creation. Nothing deployed. No original project source or customer database was available. The supplied requirement text is copied to REQUIREMENTS.txt.

Next actions, in order:
1. Provide PHP 8.2+ and isolated MySQL 8. Run PHP syntax checks first and correct failures.
2. Configure an empty database, run migrations, execute domain tests and disposable integration tests.
3. Run HTTP/browser tests, especially permissions and technician isolation. Fix before adding further phases.
4. Complete foundation MFA, reset and coverage requirements, then billing gaps. Follow IMPLEMENTATION_CHECKLIST.md.
5. Implement Daraja sandbox and real MikroTik adapter behind explicit configuration, retaining simulation default. Add payment callback and real-adapter failure tests isolated from production.
6. Complete operational/reporting modules and restore/security/deployment gates.

No completed phase has passed runtime verification. Worker and scheduler commands, config and installation instructions are in README.md. Do not reuse a live database for tests; integration tests refuse to run unless an explicit DSN containing netconnect_test is supplied and the database has no users. This is not a substitute for isolating the test server.

Known implementation limitations include no MFA/reset, no Daraja or MikroTik adapter, no coverage mapping, no attachment upload, local-only notifications, limited pagination/reporting, UTC labels on operational timestamps and no secure production deployment validation.
