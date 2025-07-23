# 🔄 Database Backup Manager

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](http://makeapullrequest.com)
[![Open Source Love](https://badges.frapsoft.com/os/v1/open-source.svg?v=103)](https://github.com/ellerbrock/open-source-badges/)

A powerful and user-friendly web-based tool to manage database backups for multiple databases with an intuitive interface.

## 📋 Table of Contents

- [Features](#-features)
- [Quick Start](#-quick-start)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Setting Up Cron Job](#-setting-up-cron-job-automatic-backups)
- [Project Structure](#-project-structure)
- [Contributing](#-contributing)
- [License](#-license)
- [Connect](#-connect)

## 🌟 Features

- Backup multiple databases with a single click
- Real-time progress tracking
- Cron job support for Automatic Backup
- Support for multiple database types:
  - ✅ MySQL / MariaDB
  - ✅ PostgreSQL
  - ✅ SQL Server / MS SQL
  - ✅ SQLite
- Secure and easy to set up
- Responsive design works on all devices

## 🚀 Quick Start

### 🔄 Setting Up Cron Job (Automatic Backups)

To set up automatic backups using cron job, follow these steps:

1. **Make the cron script executable**:
   ```bash
   chmod +x cron_backup.php
   ```

2. **Open crontab editor**:
   ```bash
   crontab -e
   ```

3. **Add a cron job** (example runs every 6 hours):
   ```
   0 */6 * * * php /path/to/your/project/cron_backup.php >> /path/to/your/project/cron.log 2>&1
   ```

4. **For Windows Task Scheduler**:
   - Open Task Scheduler
   - Create a new task
   - Set trigger to run daily/hourly as needed
   - Set action to: `php.exe c:\path\to\your\project\cron_backup.php`
   - Save the task

5. **Verify the cron job**:
   - Check the cron.log file for any errors
   - Verify backups are being created in the specified backup directory

> 💡 **Note**: Make sure the PHP executable is in your system's PATH or use the full path to PHP in the cron command.

---

### Prerequisites
- PHP 7.4, 8.1 or higher
- Web Server (Apache/Nginx) or PHP Built-in Server
- One or more of these database systems:
  - MySQL 5.7+ / MariaDB 10.3+
  - PostgreSQL 9.5+
  - SQL Server 2012+
  - SQLite 3.0+
- Git (for version control)
- Run in CLI: `php -S localhost:8000`

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/MICS-Shwetank/project-bd-backup.git
   cd project-bd-backup

2. **Important Configuration Step**:
   - Rename `config_example.php` to `config.php`
   - Open `config.php` in a text editor
   - Configure your database connections in the 'clients' array
   - Example configuration:
     ```php
     'clients' => [
         'client1' => [
             'client_name'    => 'My Database',
             'driver'         => 'mysql',    // mysql, pgsql, sqlsrv, sqlite
             'port'           => '3306',     // Optional (default ports will be used if empty)
             'hostname'       => 'localhost',
             'username'       => 'your_username',
             'password'       => 'your_password',
             'database'       => 'your_database',
             'interval_hours' => 6
         ]
     ]
     ```
   - Save the file after making changes
   ```

2. Configure your databases in `config.php`:
   ```php
   return [
       'clients' => [
           'client1' => [
               'client_name'    => 'MySQL Database',
               'driver'         => 'pgsql',     // or 'mariadb', 'pgsql', 'sqlsrv', 'sqlite'
               'hostname'       => 'localhost',
               'username'       => 'db_user',
               'password'       => 'db_password',
               'database'       => 'database_name',
               'port'           => '5432',      // Default: 3306 (MySQL), 5432 (PostgreSQL), 1433 (SQL Server)
               'interval_hours' => 6            // Backup interval in hours
           ],
           // Add more database connections as needed
       ]
   ];
   ```

4. Set proper permissions:
   ```bash
   chmod -R 755 /path/to/backup/directory
   ```

5. Access the application in your browser:
   ```
   http://your-domain.com/path/to/database-backup-manager
   ```

## 📁 Project Structure

```
database-backup-manager/
├── assets/                 # CSS, JS, and other static files
├── backups/                # Directory where backups are stored
├── templates/              # PHP view templates
│   ├── backup_modal.php    # Backup modal template
│   └── ...
├── uploads/                # For any file uploads
├── vendor/                 # Composer dependencies
├── .gitignore
├── backup.php              # Handles backup operations
├── config.php              # Configuration file
├── download_backup.php     # Handles backup downloads
├── index.php               # Main application file
├── list_backups.php        # Lists available backups
└── README.md               # This file
```

## 👥 Contributing

We welcome contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) to get started.

## � License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Thanks to all contributors who help improve this project
- Built with ❤️ by [Shwetank Dwivedi](https://www.linkedin.com/in/im-shwetank)

## 🌐 Connect with Me

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Connect-blue?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/im-shwetank)
[![Twitter](https://img.shields.io/twitter/url?style=for-the-badge&url=https%3A%2F%2Ftwitter.com%2Fim_shwetank)](https://twitter.com/im_shwetank)
[![GitHub stars](https://img.shields.io/github/stars/MICS-Shwetank/project-bd-backup?style=for-the-badge)](https://github.com/MICS-Shwetank/project-bd-backup/stargazers)

<!-- LinkedIn Badge -->
<a href="https://www.linkedin.com/in/im-shwetank" target="_blank">
  <img src="https://img.shields.io/badge/LinkedIn-Profile-blue?style=flat-square&logo=linkedin" alt="Shwetank Dwivedi on LinkedIn">
</a>

---

⭐ Star this project on [GitHub](https://github.com/MICS-Shwetank/project-bd-backup)

🔧 Built with PHP, Bootstrap, and jQuery
