# SQL Mig Standalone Plan

This directory is the working root for the future `peterivan/sql-mig` repository.

Initial scope:

- Standalone Composer package with `bin/sql-mig`.
- CLI commands: `apply` and `validate`.
- PostgreSQL only.
- Migration files use sortable version tokens.
- Applied migrations are immutable and tracked with SHA-256 checksums.
- Migration execution uses `psql` with `ON_ERROR_STOP=1`.
- Audit rows are inserted in the same transaction as the migration.
- Tests are intentionally deferred during the first migration pass.
