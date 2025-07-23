<!-- Backup All Modal -->
<div class="modal fade" id="backupAllModal" tabindex="-1" aria-labelledby="backupAllModalLabel" aria-hidden="true" data-bs-backdrop='static'>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="backupAllModalLabel">
                    <i class="fas fa-database me-2"></i>Backup All Online Databases
                </h5>
                <button type="button" class="btn-close btn-close-white" id="modalCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Database</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="backupAllList">
                            <?php foreach ($clients as $key => $client): ?>
                                <?php if ($client['connection_status'] === 'success'): ?>
                                    <tr data-client="<?= $key ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($client['client_name']) ?></strong>
                                            <div class="text-muted small"><?= htmlspecialchars($client['database']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary status-badge">Pending</span>
                                            <div class="progress mt-1" style="height: 5px; display: none;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary start-backup-btn" data-client="<?= $key ?>">
                                                <i class="fas fa-play me-1"></i> Start
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="modalCloseBtnFooter" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" id="startAllBackups">
                        <i class="fas fa-play-circle me-1"></i> Start All Backups
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #backupAllModal .modal-dialog {
        max-width: 800px;
    }
    .status-badge {
        min-width: 80px;
        display: inline-block;
        text-align: center;
    }
    .progress {
        width: 150px;
    }
</style>

<script>
$(document).ready(function() {
    let isProcessing = false;
    let pendingBackups = [];
    let currentBackup = null;
    
    // Initialize modal with backdrop static option
    const backupModal = new bootstrap.Modal(document.getElementById('backupAllModal'), {
        backdrop: 'static',  // Prevents closing on outside click
        keyboard: false      // Prevents closing with ESC key
    });
    
    // Function to toggle modal controls
    function toggleModalControls(disable) {
        // Disable/enable close buttons and other controls
        $('#modalCloseBtn, #modalCloseBtnFooter, #startAllBackups, .start-backup-btn')
            .prop('disabled', disable)
            .css('pointer-events', disable ? 'none' : '')
            .css('opacity', disable ? '0.5' : '');
            
        // Prevent browser back/refresh when processing
        if (disable) {
            window.onbeforeunload = function() {
                return 'Backup in progress. Are you sure you want to leave?';
            };
        } else {
            window.onbeforeunload = null;
        }
    }
    
    // Start all backups
    $('#startAllBackups').on('click', function() {
        if (isProcessing) return;
        
        // Reset pending backups array
        pendingBackups = [];
        
        // Add all online databases to pending backups
        $('tr[data-client]').each(function() {
            const clientKey = $(this).data('client');
            pendingBackups.push({
                key: clientKey,
                row: $(this)
            });
        });
        
        if (pendingBackups.length === 0) {
            Lobibox.notify('info', {
                position: 'top right',
                size: 'mini',
                rounded: true,
                delayIndicator: false,
                msg: 'No databases available for backup'
            });
            return;
        }
        
        // Disable modal controls
        toggleModalControls(true);
        isProcessing = true;
        
        // Update button text
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');
        
        // Start processing backups
        processNextBackup($btn, originalText);
    });
    
    function processNextBackup($btn, originalText) {
        if (pendingBackups.length === 0) {
            // All backups completed
            isProcessing = false;
            toggleModalControls(false);
            $btn.html(originalText);
            
            Lobibox.notify('success', {
                position: 'top right',
                size: 'mini',
                rounded: true,
                delayIndicator: false,
                msg: 'All backups completed successfully!'
            });
            
            // Refresh the page after 2 seconds (commented as requested)
            // setTimeout(() => {
            //     location.reload();
            // }, 2000);
            
            return;
        }
        
        // Get next backup and remove from array
        currentBackup = pendingBackups.shift();
        startBackup(currentBackup.key, currentBackup.row, true, $btn, originalText);
    }
    
    // Start individual backup
    $('.start-backup-btn').on('click', function() {
        if (isProcessing) return;
        
        const $btn = $(this);
        const clientKey = $btn.data('client');
        const $row = $btn.closest('tr');
        
        // Disable all modal controls when starting single backup
        toggleModalControls(true);
        isProcessing = true;
        
        startBackup(clientKey, $row, false);
    });
    
    function startBackup(clientKey, $row, isAuto = false, $btn = null, originalText = '') {
        const $status = $row.find('.status-badge');
        const $progress = $row.find('.progress').show();
        const $progressBar = $progress.find('.progress-bar');
        
        // Update status
        $status.removeClass('bg-secondary bg-success bg-danger')
               .addClass('bg-warning')
               .text('In Progress');
        
        // Reset progress
        $progressBar.css('width', '0%');
        
        // Disable all buttons in the modal
        $('#modalCloseBtn, #modalCloseBtnFooter, #startAllBackups, .start-backup-btn')
            .prop('disabled', true)
            .css('pointer-events', 'none')
            .css('opacity', '0.5');
        
        // Start backup
        $.ajax({
            url: 'backup.php',
            type: 'POST',
            data: { client: clientKey },
            dataType: 'json',
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        $progressBar.css('width', percentComplete + '%');
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                if (response.status === 'success') {
                    $status.removeClass('bg-warning')
                           .addClass('bg-success')
                           .text('Success');
                    
                    if (!isAuto) {
                        // Re-enable modal controls for single backup
                        toggleModalControls(false);
                        isProcessing = false;
                        
                        Lobibox.notify('success', {
                            position: 'top right',
                            size: 'mini',
                            rounded: true,
                            delayIndicator: false,
                            msg: 'Backup created successfully!'
                        });
                        
                        // Keep this commented as requested
                        // setTimeout(() => {
                        //     location.reload();
                        // }, 2000);
                    }
                } else {
                    handleBackupError($status, response.message || 'Backup failed');
                    
                    // Re-enable modal controls on error for single backup
                    if (!isAuto) {
                        toggleModalControls(false);
                        isProcessing = false;
                    }
                }
                
                // Process next backup if in auto mode
                if (isAuto) {
                    setTimeout(() => processNextBackup($btn, originalText), 1000);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Error: ' + status + ' ' + error;
                
                handleBackupError($status, errorMsg);
                
                // Re-enable modal controls on error for single backup
                if (!isAuto) {
                    toggleModalControls(false);
                    isProcessing = false;
                }
                
                // Process next backup if in auto mode
                if (isAuto) {
                    setTimeout(() => processNextBackup($btn, originalText), 1000);
                }
            }
        });
    }
    
    function handleBackupError($status, message) {
        $status.removeClass('bg-warning bg-success')
               .addClass('bg-danger')
               .text('Failed');
        
        // Show error message
        Lobibox.notify('error', {
            position: 'top right',
            size: 'mini',
            rounded: true,
            delayIndicator: false,
            msg: message
        });
    }
    
    // Show modal when clicking backup all button
    $('button[data-bs-target="#backupAllModal"]').on('click', function() {
        // Reset all rows
        $('tr[data-client]').each(function() {
            const $row = $(this);
            $row.find('.status-badge')
                .removeClass('bg-warning bg-success bg-danger')
                .addClass('bg-secondary')
                .text('Pending');
                
            $row.find('.progress').hide();
            $row.find('.start-backup-btn').prop('disabled', false);
        });
        
        // Reset state
        isProcessing = false;
        pendingBackups = [];
        currentBackup = null;
    });
});
</script>
