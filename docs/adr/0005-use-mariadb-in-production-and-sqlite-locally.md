# ADR 0005 — Use MariaDB in production and SQLite locally

Production uses MariaDB as its external transactional database, while native development, Docker development, and automated tests continue to use SQLite. This avoids tying production data to an application-container filesystem and allows the web, worker, and scheduler roles to share one database, at the cost of deliberate cross-database verification for schema constraints and queries that behave differently between SQLite and MariaDB.
