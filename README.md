# EPSS CRM — Credit and Health-Facility Management System

EPSS CRM is a browser-based PHP application for managing credit issued to customers and health facilities. It gives operations and finance teams one place to register facilities, record invoices or credit transactions, capture payments, follow up outstanding balances, and review collection performance.

The system is designed for an organisation that extends credit to a distributed network of facilities. It supports both the general **customer/transaction** workflow and the dedicated **health-facility** workflow found in this project.

## What the system does

At its core, EPSS CRM answers four practical questions:

1. Who has received credit?
2. How much is owed, paid, or overdue?
3. Which facilities need follow-up?
4. How is collection performance changing over time?

Users can create and maintain customer or facility records, attach credit activity to them, record one or more payments against a credit transaction, and use dashboards and reports to monitor the outstanding portfolio.

## Main capabilities

### Customer and health-facility records

- Register, search, edit, and view customer records.
- Manage health facilities separately, including their invoice totals, paid amounts, remaining balances, and payment status.
- Capture organisational and contact details such as region, zone, woreda, hub, level, address, contact person, phone number, facility type, and capacity where supported by the database.
- Import and export customer and facility data.
- Store notes, documents, and a change history for customer records.

### Credit and payment management

- Create credit transactions with an amount, credit date, due date, status, and notes.
- Record full or partial payments, including payment date, payment method, and reference number.
- Calculate remaining balances from the total credit less recorded payments.
- Identify paid, pending, active, partial, unpaid, and overdue items, depending on the workflow used.
- View transaction and payment history for a customer or facility.

### Follow-up and collection support

- Create and manage facility notes.
- Track payment reminders and credit reminders for facilities.
- Highlight overdue transactions and the outstanding amount connected to them.
- Surface high-value overdue customers on the dashboard for prioritised follow-up.

### Dashboard and reports

The dashboard provides a high-level view of:

- Total and active customers
- Total credit issued
- Total payments received
- Outstanding and overdue amounts
- Credit and payments created this month
- Recent transactions
- Customers with the highest overdue balances
- Recent monthly credit trends

Reports can be filtered by search text, transaction status, date range, amount range, region, zone, and level. The reporting pages also show recent activity, top customers, and monthly summaries.

### Users and preferences

- Login, logout, registration, password reset, and remembered email support.
- Session-based authentication and role-aware permissions.
- User profile and application settings.
- Light/dark theme preference and language support.

## How it is built

| Area | Implementation |
| --- | --- |
| Server-side application | PHP, using PDO for MySQL/MariaDB access |
| Database | MySQL 5.7+ or MariaDB-compatible schema |
| Front end | Server-rendered HTML, CSS, JavaScript, Bootstrap, and Font Awesome |
| Charts | Chart.js, loaded on the dashboard |
| Web server | Apache (XAMPP is suitable for local use) |
| Authentication | PHP sessions and password hashes created with `password_hash()` |

The codebase is organised as a traditional PHP application: each module owns its pages, while `includes/` contains shared configuration, authentication checks, language helpers, page layout, and common functions.

## Project structure

```text
EPSS CRM/
├── assets/              # Shared styles and front-end assets
├── auth/                # Login, logout, registration, and password reset
├── customers/           # Customer registration, search, imports, exports, notes, documents
├── dashboard/           # Portfolio summary and credit trends
├── database/            # Database setup, administration utilities, and SQL dump
├── health_facility/     # Facility registration, payments, reminders, notes, and reports
├── includes/            # Configuration, authentication, layout, language, and helper functions
├── profile/             # Current-user profile page
├── reports/             # Customer and transaction reporting
├── settings/            # Theme, language, and notification preferences
├── sql/                 # Supporting schema additions for notes and customer data
├── transactions/        # Credit transactions and payment recording
├── .htaccess            # Apache rules and security headers
└── index.php            # Application entry point
```

## Data model

The primary customer-credit workflow uses these relationships:

```text
users
  └── customer_notes.created_by

customers
  ├── transactions.customer_id
  │     └── payments.transaction_id
  ├── customer_notes.customer_id
  ├── customer_documents.customer_id
  └── customer_history.customer_id
```

The supplied database dump additionally contains dedicated health-facility tables and supporting data for facility payments, credit limits, and credit reminders.

## Requirements

- PHP 7.4 or later
- MySQL 5.7+ or MariaDB
- Apache with `mod_rewrite` enabled
- XAMPP, WAMP, or an equivalent PHP/Apache/MySQL environment

## Local installation

1. Place this folder inside your web server document root. With XAMPP, the current location is suitable:

   ```text
   C:\xampp\htdocs\EPSS-CRM\EPSS CRM
   ```

2. Start Apache and MySQL in the XAMPP Control Panel.

3. Create a database named `credit_management` with UTF-8 support.

   ```sql
   CREATE DATABASE credit_management
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```

4. Import the supplied database dump, `database/credit_management (2).sql`, using phpMyAdmin or the MySQL command line. The dump is the best starting point because it contains the full project schema.

5. Check the connection details in [includes/config.php](includes/config.php). The default local values are:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'credit_management');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

6. Open the application in a browser:

   ```text
   http://localhost/EPSS-CRM/EPSS%20CRM/
   ```

   The application detects its base URL, so its navigation and authentication redirects also work when it is hosted in this subfolder.

## First-use workflow

1. Sign in with an authorised user account.
2. Register or import customers/health facilities.
3. Add a credit transaction or facility invoice.
4. Record payments as money is received.
5. Review overdue balances, reminders, and dashboard totals.
6. Use filters and exports to prepare collection reports.

## Security and operational notes

- Passwords are stored as hashes; never store plain-text passwords in the database.
- Database operations generally use prepared PDO statements.
- Forms use a session CSRF token in the customer workflow.
- Apache rules disable directory listing and restrict direct access to some configuration and database utility files.
- Configure a non-empty production database password and turn off PHP error display before deploying to a public server.
- Database setup and reset utilities are administrative tools. Keep them inaccessible to ordinary users and never run reset/drop tools against production data without a backup.

## Important maintenance note

This project contains more than one schema source: the full database dump in `database/` and smaller supporting scripts in `database/` and `sql/`. Treat the full dump as the baseline for a fresh installation, then review any supporting migration before applying it to an existing database. Always back up the database first.

## License and support

No licence file or confirmed support contact is currently included in the project. Add the organisation’s preferred licence, owner, and support channel here before distributing the system externally.
