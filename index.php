<?php
$users=['Shwetank'=>['password'=>'Shwetank@123'],
'Ankit'=>['password'=>'Ankit@123']];
//usw hasing to encrypt this
$config = require 'config.php';

// Clean empty backup folders when the page loads if enabled in config
if ($config['auto_cleanup'] && file_exists('clean_empty_folders.php')) {
    include 'clean_empty_folders.php';
}

$clients = $config['clients'];

// Function to check database connection
function checkDbConnection($host, $user, $pass, $db) {
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            return ['status' => false, 'error' => $conn->connect_error];
        }
        $conn->close();
        return ['status' => true];
    } catch (Exception $e) {
        return ['status' => false, 'error' => $e->getMessage()];
    }
}
// Check connection status for each client
foreach ($clients as $key => $client) {
    $result = checkDbConnection($client['hostname'], $client['username'], $client['password'], $client['database']);
    $clients[$key]['connection_status'] = $result['status'] ? 'success' : 'danger';
    $clients[$key]['connection_message'] = $result['status'] ? 'Connected' : ($result['error'] ?? 'Connection failed');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Magadh IT and Consultancy Services Pvt. Ltd. provides secure and reliable Database Backup Manager solutions. Safeguard your business data with advanced backup, recovery, and management services designed for enterprises in India.">
    <meta name="keywords" content="Magadh IT, Database Backup Manager, data backup solutions, IT consultancy, secure data management, database recovery, business data protection, India IT services">
    <meta name="author" content="Magadh IT and Consultancy Services Pvt. Ltd.">
    <meta name="robots" content="index, follow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magadh IT and Consultancy Services Pvt. Ltd. 🔒 Database Backup Manager</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="https://img.icons8.com/color/48/000000/database-backup.png" type="image/x-icon">
    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Lobibox CSS -->
    <link rel="stylesheet" href="assets/notification/css/lobibox.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4bb543;
            --danger-color: #ff3333;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
        }
        
        .badge-online {
            background-color: var(--success-color);
        }
        
        .badge-offline {
            background-color: var(--danger-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .last-backup {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .database-icon {
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .backup-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #dee2e6;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-value {
            font-weight: 600;
            display: block;
            font-size: 1.1rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .footer {
            margin-top: 3rem;
            padding: 1.5rem 0;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-database me-2"></i>Database Backup Manager
            </a>
            <div class="d-flex">
                <button class="btn btn-sm btn-outline-primary me-2" id="backupAllBtn">
                    <i class="fas fa-play-circle me-1"></i> Start All
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-1">Database Backup Dashboard</h4>
                                <p class="text-muted mb-0">Manage and monitor your database backups</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-server me-1"></i> 
                                    <?= count($clients) ?> Databases
                                </span>
                                <span class="badge bg-light text-dark ms-2">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    <?= count(array_filter($clients, fn($c) => $c['connection_status'] === 'success')) ?> Online
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    <!-- Backup List Modal -->
    <div class="modal fade" id="backupModal" tabindex="-1" aria-labelledby="backupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="backupModalLabel">Backup List - <span id="modalClientName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="backupListBody">
                                <!-- Backup list will be populated here by JavaScript -->
                                <tr>
                                    <td colspan="4" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <?php 
        $statusIcons = [
            'success' => '<i class="fas fa-check-circle text-success"></i>',
            'danger' => '<i class="fas fa-times-circle text-danger"></i>'
        ];
        
        foreach ($clients as $key => $client): 
            $isOnline = $client['connection_status'] === 'success';
            $lastBackup = 'Never';
            $backupSize = '0 MB';
            
            // Get last backup info (you can implement this function)
            // $lastBackupInfo = getLastBackupInfo($key);
            // if ($lastBackupInfo) {
            //     $lastBackup = date('M d, Y H:i', $lastBackupInfo['time']);
            //     $backupSize = formatFileSize($lastBackupInfo['size']);
            // }
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-database database-icon"></i>
                        <?= htmlspecialchars($client['client_name']) ?>
                    </h5>
                    <span class="badge rounded-pill bg-<?= $isOnline ? 'success' : 'danger' ?>">
                        <?= $isOnline ? 'Online' : 'Offline' ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Database:</span>
                            <span class="fw-bold"><?= htmlspecialchars($client['database']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Host:</span>
                            <span><?= htmlspecialchars($client['hostname']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Databases</h2>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Backup Interval:</span>
                            <span class="badge bg-light text-dark">
                                <i class="far fa-clock me-1"></i> 
                                <?= $client['interval_hours'] ?> hours
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!$isOnline): ?>
                        <div class="alert alert-warning p-2 small mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <?= htmlspecialchars($client['connection_message'] ?? 'Connection failed') ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="backup-stats">
                        <div class="stat-item">
                            <span class="stat-value" id="backup-count-<?= $key ?>">0</span>
                            <span class="stat-label">Backups</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="last-backup-<?= $key ?>">
                                <?php if ($lastBackup && $lastBackup !== 'Never'): ?>
                                    <?= date('M d, Y H:i', $lastBackup) ?>
                                    <small class="text-muted d-block">
                                        (<?= floor((time() - $lastBackup) / 3600) ?> hours ago)
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </span>
                            <span class="stat-label">Last Backup</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $backupSize ?></span>
                            <span class="stat-label">Size</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary start-backup flex-grow-1" 
                                data-client="<?= $key ?>" 
                                <?= !$isOnline ? 'disabled' : '' ?>>
                            <i class="fas fa-download me-1"></i> Backup Now
                        </button>
                        <button class="btn btn-sm btn-outline-secondary view-backups" 
                                data-client="<?= $key ?>" 
                                data-client-name="<?= htmlspecialchars($client['client_name']) ?>"
                                <?= !$isOnline ? 'disabled' : '' ?>>
                            <i class="far fa-folder-open me-1"></i>
                            <span class="backup-text">View</span> 
                            <span class="badge bg-primary backup-count">0</span>
                        </button>
                    </div>
                    <div class="mt-2 small text-muted text-center status" id="status-<?= $key ?>"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

   
    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0">
                        <i class="far fa-copyright"></i> <?= date('Y') ?> Database Backup Manager 
                        <span class="text-muted">v1.0.0</span>
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-sync-alt text-primary"></i> Last updated: <?= date('M d, Y H:i:s') ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Load JavaScript -->
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Lobibox -->
    <script src="https://cdn.jsdelivr.net/npm/lobibox@1.2.7/dist/js/lobibox.min.js"></script>
    
    <!-- Include Backup All Modal -->
    <?php include 'templates/backup_modal.php'; ?>
    
    <!-- Custom Scripts -->
    <script src="assets/script.js"></script>
    
    <script>
    // Load backup counts for all clients
    $(document).ready(function() {
        // Handle Backup All button click
        $('#backupAllBtn').on('click', function() {
            // Initialize modal
            const backupModal = new bootstrap.Modal(document.getElementById('backupAllModal'));
            // Show modal
            backupModal.show();
        });

        <?php foreach ($clients as $key => $client): ?>
        (function() {
            const clientKey = '<?= $key ?>';
            const viewButton = $(`button.view-backups[data-client="${clientKey}"]`);
            const backupCountEl = $(`#backup-count-${clientKey}`);
            const lastBackupEl = $(`#last-backup-${clientKey}`);
            
            // Show loading state
            viewButton.html('<i class="fas fa-spinner fa-spin me-1"></i> Loading...');
            
            // Load backup info
            $.get('list_backups.php', { client: clientKey, _: new Date().getTime() }, function(data) {
                const backupCount = data.count || 0;
                const lastBackupTime = data.last_backup_time;
                const hoursAgo = data.hours_ago;
                
                // Update backup count
                backupCountEl.text(backupCount);
                
                // Update last backup time
                if (lastBackupTime) {
                    const lastBackupDate = new Date(lastBackupTime * 1000);
                    lastBackupEl.html(`
                        <i class="far fa-clock me-1"></i>
                        ${lastBackupDate.toLocaleString()}
                        <small class="text-muted ms-2">(${hoursAgo} hours ago)</small>
                    `);
                } else {
                    lastBackupEl.html('<span class="text-muted">Never</span>');
                }
                
                viewButton.html(`
                    <i class="far fa-folder-open me-1"></i>
                    <span class="backup-text">View</span> 
                    <span class="badge bg-primary backup-count">${backupCount}</span>
                `);
                
                // Show button if there are backups
                if (backupCount > 0) {
                    viewButton.fadeIn();
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Error loading backups:', status, error);
                viewButton.html(`
                    <i class="far fa-folder-open me-1"></i>
                    <span class="backup-text">View</span>
                `);
            });
        })();
        <?php endforeach; ?>
    });
    </script>
</body>
</html>
