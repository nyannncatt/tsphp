# School Management System

A comprehensive school management system built with PHP and MySQL that handles administration, student, and parent interactions.

## Features

- Multi-user roles (Admin, Student, Parent)
- Course management
- Grade tracking
- Attendance monitoring
- Messaging system
- Announcement board
- Student-Parent relationship management

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository to your web server directory:
```bash
git clone https://github.com/yourusername/school-management.git
```

2. Create a MySQL database and import the schema:
```bash
mysql -u root -p
CREATE DATABASE school_management;
use school_management;
source database.sql;
```

3. Configure the database connection:
- Open `config/database.php`
- Update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'school_management');
```

4. Set up the web server:
- For Apache, ensure mod_rewrite is enabled
- Point the document root to the project directory
- Ensure the web server has write permissions for uploads and cache directories

5. Create an admin user:
```sql
INSERT INTO users (username, password, email, role) 
VALUES ('admin', '$2y$10$YOUR_HASHED_PASSWORD', 'admin@school.com', 'admin');
```

## Directory Structure

```
school-management/
├── admin/           # Admin interface files
├── auth/            # Authentication files
├── config/          # Configuration files
├── includes/        # Shared PHP files
├── parent/          # Parent interface files
├── student/         # Student interface files
└── database.sql     # Database schema
```

## Usage

1. Access the system through your web browser:
```
http://your-domain/school-management/
```

2. Log in with the appropriate credentials based on your role:
- Admin: Full system access
- Student: View courses, grades, attendance, and messages
- Parent: View children's progress and communicate with teachers

## Security

- All passwords are hashed using PHP's password_hash()
- SQL injection prevention using prepared statements
- XSS prevention using htmlspecialchars()
- CSRF protection implemented
- Session security measures in place

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 