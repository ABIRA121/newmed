# MedPortal - Medical Management System

A comprehensive full-stack medical portal built with PHP, JavaScript, and CSS featuring role-based access for Admin, Staff, and Patients.

## Features

### Authentication & Authorization
- Secure login for Admin, Staff, and Patients
- Patient self-registration
- Role-based access control
- Session management with timeout
- CSRF protection

### Admin Features
- User management (create/edit staff and patients)
- View system statistics
- Audit logs
- Export data to CSV

### Staff Features
- Manage assigned appointments
- Update appointment status
- Patient notes and records

### Patient Features
- Profile management
- Appointment scheduling
- View appointment history
- Medical records access

## Installation

### Prerequisites
- PHP 7.4+ or 8.2
- MySQL 5.7+ or MariaDB 10.3+
- Apache web server
- XAMPP (Windows) or LAMP (Linux)

### Setup Steps

1. **Extract the project** to your web server directory

2. **Configure database**
- Create a MySQL database named `medportal`
- Import the schema: `database/schema.sql`
- Import seed data: `database/seeds.sql`

3. **Configure application**
- Edit `config/config.php` with your database credentials
- Ensure `logs/` directory is writable

4. **Set document root** to `public/` directory
- XAMPP: Update httpd-vhosts.conf
- Apache: Update VirtualHost configuration

5. **Access the application**
- Open http://localhost/MedPortal/public/
- Or your configured domain

### Default Login Credentials

**Admin:**
- Email: admin@medportal.com
- Password: Admin123!

**Staff:**
- Email: dr.smith@medportal.com
- Password: Staff123!

**Patient:**
- Email: patient1@example.com
- Password: Patient123!

## Security Checklist for Production

1. **Change default passwords** for all seeded users
2. **Update database credentials** in config.php
3. **Enable HTTPS** and update `config.php`:
```php
ini_set('session.cookie_secure', 1);