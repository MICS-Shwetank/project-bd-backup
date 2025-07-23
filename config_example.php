<?php
/**
 * Database Backup Configuration
 *  if port is not specified, default port will be used
 * Supported Database Types:
 * - MySQL / MariaDB (driver: 'mysql' or 'mariadb')
 * - PostgreSQL (driver: 'pgsql', 'postgres' or 'postgresql')
 * - SQL Server (driver: 'sqlsrv' or 'mssql')
 * - SQLite (driver: 'sqlite')
 * 
 * Example Configurations:
 * 
 * 1. MySQL/MariaDB Example:
 * 'example_mysql' => [
 *     'client_name'    => 'MySQL Database',
 *     'driver'         => 'mysql',
 *     'port'           => '3306',  // Optional, default 3306
 *     'hostname'       => 'localhost',
 *     'username'       => 'db_user',
 *     'password'       => 'db_password',
 *     'database'       => 'database_name',
 *     'interval_hours' => 6  // Backup interval in hours
 * ],
 * 
 * 2. PostgreSQL Example:
 * 'example_pgsql' => [
 *     'client_name'    => 'PostgreSQL DB',
 *     'driver'         => 'pgsql',
 *     'port'           => '5432',  // Optional, default 5432
 *     'hostname'       => 'localhost',
 *     'username'       => 'db_user',
 *     'password'       => 'db_password',
 *     'database'       => 'database_name',
 *     'interval_hours' => 6
 * ],
 * 
 * 3. SQL Server Example:
 * 'example_sqlsrv' => [
 *     'client_name'    => 'SQL Server DB',
 *     'driver'         => 'sqlsrv',
 *     'port'           => '1433',  // Optional, default 1433
 *     'hostname'       => 'localhost',
 *     'username'       => 'db_user',
 *     'password'       => 'db_password',
 *     'database'       => 'database_name',
 *     'interval_hours' => 6
 * ],
 * 
 * 4. SQLite Example:
 * 'example_sqlite' => [
 *     'client_name'    => 'SQLite DB',
 *     'driver'         => 'sqlite',
 *     'database'       => '/path/to/database.sqlite',  // Full path to SQLite file
 *     'interval_hours' => 6
 * ]
 */

// Database host (usually localhost or IP)
$dbHostRemote='192.168.1.1';
$dbHostLocal='localhost';
return [
    // Global Configs
    'enable_zip'     => true,   // ज़िप backup चाहिए या नहीं
    'backup_path' => __DIR__ . '/backups',
    'max_backups' => 30, // Maximum number of backups to keep for each client
    'auto_cleanup' => true, // Automatically clean up old backups
    'notify_email' => 'admin@example.com', // Email to send notifications to

    // Database Configurations
    'clients' => [
        'example_mysql' => [
            'client_name'    => 'Example MySQL', // Client Name
            'driver'         => 'mysql', // Database Type
            'port'           => '', // Database Port Optional if default port is used
            'hostname'       => $dbHostRemote, // Database Host
            'username'       => 'db_user', // Database Username
            'password'       => 'db_password', // Database Password
            'database'       => 'example_db', // Database Name
            'interval_hours' => 6 // Backup interval in hours
        ],
        'example_pgsql' => [
            'client_name'    => 'Example PostgreSQL', // Client Name
            'driver'         => 'pgsql', // Database Type
            'port'           => '5432', // Database Port Optional if default port is used
            'hostname'       => $dbHostLocal, // Database Host
            'username'       => 'pgsql_user', // Database Username
            'password'       => 'pgsql_pass', // Database Password
            'database'       => 'example_pgdb', // Database Name
            'interval_hours' => 6 // Backup interval in hours
        ],
    ]
];
