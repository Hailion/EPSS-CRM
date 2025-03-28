# Credit Management System

A comprehensive credit management system for tracking customer credits, transactions, and payments.

## Features

- User authentication with role-based access control
- Customer management
- Credit transaction tracking
- Payment recording
- Dashboard with key metrics
- Reports generation

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP (recommended for local development)

## Installation

1. Clone the repository to your web server directory:

   ```bash
   git clone https://github.com/yourusername/credit-management-system.git
   ```

2. Create a new MySQL database:

   ```bash
   mysql -u root -p
   CREATE DATABASE credit_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import the database schema:

   - Open your web browser and navigate to:
     ```
     http://localhost/database/create_db.php
     ```
   - This will create all necessary tables and the admin user.

4. Configure the database connection:

   - Open `includes/config.php`
   - Update the database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'credit_management');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

5. Set up your web server:
   - For XAMPP, place the files in the `htdocs` directory
   - Ensure the web server has write permissions for the `uploads` directory

## Default Admin Account

- Email: admin@example.com
- Password: admin123

**Important**: Change the admin password after first login!

## Directory Structure

```
creditmm/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── auth/
│   ├── login.php
│   ├── register.php
│   └── forgot_password.php
├── customers/
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   └── view.php
├── transactions/
│   ├── list.php
│   ├── add_credit.php
│   ├── add_payment.php
│   └── view.php
├── dashboard/
│   └── index.php
├── reports/
│   └── index.php
├── includes/
│   ├── config.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
└── database/
    ├── create_db.php
    ├── check_db.php
    └── reset_admin.php
```

## Usage

1. Access the system through your web browser:

   ```
   http://localhost/
   ```

2. Log in with the admin credentials:

   - Email: admin@example.com
   - Password: admin123

3. Start managing your customers and transactions!

## Security

- All passwords are hashed using PHP's `password_hash()` function
- SQL injection is prevented using prepared statements
- XSS attacks are prevented by sanitizing user input
- CSRF protection is implemented for forms

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@creditmm.com or create an issue in the repository.
