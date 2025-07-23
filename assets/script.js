// Wait for Lobibox to be loaded
function initLobibox() {
    if (typeof Lobibox === 'undefined') {
        setTimeout(initLobibox, 100);
        return;
    }

    // Initialize Lobibox
    Lobibox.notify.DEFAULTS = $.extend({}, Lobibox.notify.DEFAULTS, {
        iconSource: 'fontAwesome',
        size: 'normal',
        rounded: true,
        delayIndicator: false
    });
}

// Function to show confirmation dialog
function showConfirm(message, callback) {
    // Make sure Lobibox is loaded
    if (typeof Lobibox === 'undefined') {
        if (confirm(message.replace(/<[^>]*>?/gm, ''))) {
            callback();
        }
        return;
    }
    
    Lobibox.confirm({
        title: 'Confirm Delete',
        msg: message,
        buttons: {
            confirm: {
                'class': 'btn btn-danger',
                'text': 'Yes, Delete It!'
            },
            cancel: {
                'class': 'btn btn-secondary',
                'text': 'Cancel'
            }
        },
        callback: function (result) {
            if (result) {
                callback();
            }
        },
        modalClass: 'lobibox-better',
        icon: 'fa fa-question-circle',
        closeButton: true,
        closeOnEsc: true,
        draggable: true
    });
}

// Function to show notification
function showNotification(type, title, message) {
    if (typeof Lobibox === 'undefined') {
        alert(title + ': ' + message);
        return;
    }
    
    Lobibox.notify(type, {
        title: title,
        msg: message,
        position: 'topRight',
        sound: false
    });
}

// Function to format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Function to format date
function formatDate(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString();
}

// Initialize Lobibox when document is ready
$(document).ready(function () {
    initLobibox();
    // Initialize Bootstrap modal
    const backupModal = new bootstrap.Modal(document.getElementById('backupModal'));
    let currentClient = null;

    // Start backup button click handler - No confirmation, direct backup
    $('.start-backup').click(function () {
        const button = $(this);
        const client = button.data('client');
        const clientName = $(`h5:contains('${client}')`).text() || client;
        const statusEl = $('#status-' + client);
        
        // Disable button and show loader
        const originalText = button.html();
        button.prop('disabled', true);
        button.html('<span class="spinner-border spinner-border-sm" role="status"></span> Creating Backup...');
        statusEl.html('<div class="text-primary">Please wait, backup in progress...</div>');
        
        // Start backup directly
        $.post('backup.php', { client: client }, function (response) {
            showNotification('success', 'Backup Complete', `Backup of ${clientName} completed successfully!`);
            statusEl.html('<span class="text-success">✅ Backup completed successfully!</span>');
            
            // Refresh backup list if modal is open for this client
            if (currentClient === client) {
                loadBackups(client);
            }
            
            // Re-enable button after a delay
            setTimeout(() => {
                button.html(originalText);
                button.prop('disabled', false);
                statusEl.html('');
            }, 3000);
            
        }).fail(function(xhr) {
            const errorMessage = xhr.responseText || 'An error occurred during backup';
            showNotification('error', 'Backup Failed', errorMessage);
            statusEl.html(`<span class="text-danger">❌ ${errorMessage}</span>`);
            button.html(originalText);
            button.prop('disabled', false);
        });
    });

    // View backups button click handler
    $(document).on('click', '.view-backups', function() {
        const button = $(this);
        const client = button.data('client');
        const clientName = button.data('client-name');
        currentClient = client;
        
        // Set modal title
        $('#modalClientName').text(clientName);
        
        // Show loading
        $('#backupListBody').html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading backups...</td></tr>');
        
        // Show modal first
        backupModal.show();
        
        // Then load backups
        loadBackups(client);
    });

    // Function to load backups for a client
    function loadBackups(client) {
        console.log('Loading backups for client:', client);
        
        // Show loading state
        const viewButton = $(`button.view-backups[data-client="${client}"]`);
        viewButton.html('<span class="spinner-border spinner-border-sm" role="status"></span> Loading...');
        
        $.get('list_backups.php', { client: client, _: new Date().getTime() }, function(data) {
            console.log('Backup data received:', data);
            
            // Update backup count in the view button
            const backupCount = data.count || 0;
            viewButton.html(`View Backups <span class="badge bg-primary backup-count">${backupCount}</span>`);
            
            if (data.status === 'success' && data.files && data.files.length > 0) {
                let html = '';
                data.files.forEach(file => {
                    html += `
                    <tr>
                        <td>${file.name}</td>
                        <td>${formatDate(file.timestamp)}</td>
                        <td>${formatFileSize(file.size)}</td>
                        <td>
                            <a href="download_backup.php?file=${encodeURIComponent(file.path)}&name=${encodeURIComponent(file.name)}" 
                               class="btn btn-sm btn-primary" download>
                                Download
                            </a>
                            <button class="btn btn-sm btn-danger delete-backup ms-1" 
                                    data-file="${encodeURIComponent(file.path)}">
                                Delete
                            </button>
                        </td>
                    </tr>`;
                });
                $('#backupListBody').html(html);
                
                // Show the view button if there are backups
                viewButton.fadeIn();
            } else {
                // Hide the view button if no backups
                if (backupCount === 0) {
                    viewButton.fadeOut();
                } else {
                    viewButton.fadeIn();
                }
            }
        }, 'json').fail(function() {
            $('#backupListBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading backups</td></tr>');
        });
    }

    // Delete backup handler - Updated version with Lobibox confirm
    $(document).on('click', '.delete-backup', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const fileName = button.closest('tr').find('td:first').text();
        const file = button.data('file');
        const backupModalInstance = bootstrap.Modal.getInstance(document.getElementById('backupModal'));
        
        Lobibox.confirm({
            title: 'Delete Backup',
            msg: `Are you sure you want to delete backup: <strong>${fileName}</strong>?<br>This action cannot be undone.`,
            buttons: {
                confirm: {
                    'class': 'btn btn-danger',
                    'text': 'Yes, Delete It!'
                },
                cancel: {
                    'class': 'btn btn-secondary',
                    'text': 'Cancel'
                }
            },
            callback: function ($this, type, ev) {
                if (type === 'yes') {
                    // User confirmed, proceed with delete
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Deleting...');
                    
                    $.post('delete_backup.php', { file: file }, function(response) {
                        if (response.status === 'success') {
                            const row = button.closest('tr');
                            row.fadeOut(400, function() {
                                row.remove();
                                
                                // Show Lobibox success notification
                                Lobibox.notify('success', {
                                    title: 'Success',
                                    msg: 'Backup deleted successfully',
                                    position: 'topRight',
                                    sound: false
                                });
                                
                                // Close the modal
                                backupModalInstance.hide();
                                
                                // Update backup count
                                const client = currentClient;
                                if (client) {
                                    loadBackups(client);
                                }
                            });
                        } else {
                            showNotification('error', 'Error', response.message || 'Failed to delete backup');
                            button.prop('disabled', false).html('Delete');
                        }
                    }, 'json').fail(function() {
                        showNotification('error', 'Error', 'Failed to connect to server');
                        button.prop('disabled', false).html('Delete');
                    });
                }
                // If user clicks No or closes the dialog, do nothing
            }
        });
    });
});