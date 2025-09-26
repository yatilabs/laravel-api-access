<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery for AJAX requests -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

@if(config('api-access.layout'))
<style>
    .api-key-value, .secret-value {
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        background: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
    }
    
    .copy-btn {
        margin-left: 0.5rem;
    }
    
    .status-badge {
        font-size: 0.875rem;
    }
    
    .mode-badge {
        font-size: 0.75rem;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .nav-tabs .nav-link {
        color: #6c757d;
    }
    
    .nav-tabs .nav-link.active {
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    
    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .btn-sm {
        font-size: 0.875rem;
    }
    
    .secret-display {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 0.375rem;
        padding: 0.75rem;
        font-family: 'Courier New', monospace;
        word-break: break-all;
    }
    
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
</style>

<!-- Toast Container for custom layouts -->
<div class="toast-container"></div>
@endif

<script>
    // Set CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize modals
    const apiKeyModal = new bootstrap.Modal(document.getElementById('apiKeyModal'));
    const domainModal = new bootstrap.Modal(document.getElementById('domainModal'));
    const secretModal = new bootstrap.Modal(document.getElementById('secretModal'));

    // Show create API key modal
    function showCreateApiKeyModal() {
        document.getElementById('apiKeyModalTitle').textContent = 'Create New API Key';
        document.getElementById('apiKeySubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i> Create API Key';
        document.getElementById('apiKeyForm').reset();
        document.getElementById('apiKeyId').value = '';
        document.getElementById('is_active').checked = true;
        
        // Load modal data including owner options
        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/create`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Handle owner section
                    const ownerSection = document.getElementById('ownerSection');
                    const ownerSelect = document.getElementById('owner_id');
                    const ownerLabel = document.getElementById('ownerLabel');
                    
                    if (data.owner_enabled) {
                        ownerSection.style.display = 'block';
                        ownerLabel.textContent = data.owner_label || 'Owner';
                        
                        // Populate owner options
                        ownerSelect.innerHTML = '<option value="">Select ' + (data.owner_label || 'Owner') + '</option>';
                        data.available_owners.forEach(owner => {
                            ownerSelect.innerHTML += `<option value="${owner.id}">${owner.text}</option>`;
                        });
                        
                        // Set required attribute if needed
                        if (data.owner_required) {
                            ownerSelect.setAttribute('required', 'required');
                        } else {
                            ownerSelect.removeAttribute('required');
                        }
                    } else {
                        ownerSection.style.display = 'none';
                        ownerSelect.removeAttribute('required');
                    }
                    
                    apiKeyModal.show();
                }
            })
            .catch(error => {
                showToast('Error', 'Failed to load modal data', 'error');
            });
    }

    // Show edit API key modal
    function showEditApiKeyModal(id) {
        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/${id}/edit`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('apiKeyModalTitle').textContent = 'Edit API Key';
                    document.getElementById('apiKeySubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i> Update API Key';
                    document.getElementById('apiKeyId').value = data.api_key.id;
                    document.getElementById('description').value = data.api_key.description || '';
                    document.getElementById('mode').value = data.api_key.mode;
                    document.getElementById('is_active').checked = data.api_key.is_active;
                    document.getElementById('expires_at').value = data.api_key.expires_at ? 
                        new Date(data.api_key.expires_at).toISOString().slice(0, 16) : '';
                    
                    // Handle owner section
                    const ownerSection = document.getElementById('ownerSection');
                    const ownerSelect = document.getElementById('owner_id');
                    const ownerLabel = document.getElementById('ownerLabel');
                    
                    if (data.owner_enabled) {
                        ownerSection.style.display = 'block';
                        ownerLabel.textContent = data.owner_label || 'Owner';
                        
                        // Populate owner options
                        ownerSelect.innerHTML = '<option value="">Select ' + (data.owner_label || 'Owner') + '</option>';
                        data.available_owners.forEach(owner => {
                            ownerSelect.innerHTML += `<option value="${owner.id}">${owner.text}</option>`;
                        });
                        
                        // Set current owner if exists
                        if (data.api_key.owner_id) {
                            ownerSelect.value = data.api_key.owner_id;
                        }
                        
                        // Set required attribute if needed
                        if (data.owner_required) {
                            ownerSelect.setAttribute('required', 'required');
                        } else {
                            ownerSelect.removeAttribute('required');
                        }
                    } else {
                        ownerSection.style.display = 'none';
                        ownerSelect.removeAttribute('required');
                    }
                    
                    apiKeyModal.show();
                } else {
                    showToast('Error', data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error', 'Failed to load API key data', 'error');
            });
    }

    // Submit API key form
        window.submitApiKeyForm = function(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('apiKeyForm'));
            const isEdit = document.getElementById('apiKeyId').value !== '';
            
            const url = isEdit ? 
                `{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/${document.getElementById('apiKeyId').value}/update` :
                `{{ config('api-access.routes.prefix', 'api-access') }}/api-keys`;
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                apiKeyModal.hide();
                showToast('Success', data.message, 'success');
                
                if (data.plain_secret) {
                    // Show secret modal first
                    showSecret(data.plain_secret);
                    
                    // Reload page after secret modal is closed
                    const secretModalElement = document.getElementById('secretModal');
                    secretModalElement.addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    }, { once: true }); // Use once: true to ensure it only fires once
                } else {
                    // If no secret, just reload immediately (for updates)
                    location.reload();
                }
            } else {
                if (data.errors) {
                    let errorMsg = Object.values(data.errors).flat().join('<br>');
                    showToast('Validation Error', errorMsg, 'error');
                } else {
                    showToast('Error', data.message, 'error');
                }
            }
        })
        .catch(error => {
            showToast('Error', 'An error occurred', 'error');
        });
    }

    // Show create domain modal
    function showCreateDomainModal() {
        document.getElementById('domainModalTitle').textContent = 'Add Domain';
        document.getElementById('domainSubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i> Add Domain';
        document.getElementById('domainForm').reset();
        document.getElementById('domainId').value = '';
        domainModal.show();
    }

    // Show edit domain modal
    function showEditDomainModal(id) {
        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/domains/${id}/edit`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('domainModalTitle').textContent = 'Edit Domain';
                    document.getElementById('domainSubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i> Update Domain';
                    document.getElementById('domainId').value = data.domain.id;
                    document.getElementById('api_key_id').value = data.domain.api_key_id;
                    document.getElementById('domain_pattern').value = data.domain.domain_pattern;
                    domainModal.show();
                } else {
                    showToast('Error', data.message, 'error');
                }
            });
    }

    // Submit domain form
    window.submitDomainForm = function(event) {
        event.preventDefault();
        const formData = new FormData(document.getElementById('domainForm'));
        const isEdit = document.getElementById('domainId').value !== '';
        
        const url = isEdit ? 
            `{{ config('api-access.routes.prefix', 'api-access') }}/domains/${document.getElementById('domainId').value}/update` :
            `{{ config('api-access.routes.prefix', 'api-access') }}/domains`;
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                domainModal.hide();
                showToast('Success', data.message, 'success');
                location.reload(); // Refresh to show updated data
            } else {
                if (data.errors) {
                    let errorMsg = Object.values(data.errors).flat().join('<br>');
                    showToast('Validation Error', errorMsg, 'error');
                } else {
                    showToast('Error', data.message, 'error');
                }
            }
        });
    }

    // Regenerate secret
    function regenerateSecret(id) {
        if (confirm('Are you sure you want to regenerate the secret? The old secret will stop working immediately.')) {
            fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/${id}/regenerate-secret`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', data.message, 'success');
                    showSecret(data.plain_secret);
                } else {
                    showToast('Error', data.message, 'error');
                }
            });
        }
    }

    // Toggle API key status
    function toggleStatus(id) {
        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
                location.reload();
            } else {
                showToast('Error', data.message, 'error');
            }
        });
    }

    // Delete API key
    function deleteApiKey(id) {
        if (confirm('Are you sure you want to delete this API key? This action cannot be undone.')) {
            fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', data.message, 'success');
                    document.querySelector(`tr[data-api-key-id="${id}"]`).remove();
                } else {
                    showToast('Error', data.message, 'error');
                }
            });
        }
    }

    // Delete domain
    function deleteDomain(id) {
        if (confirm('Are you sure you want to delete this domain?')) {
            fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/domains/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', data.message, 'success');
                    document.querySelector(`tr[data-domain-id="${id}"]`).remove();
                } else {
                    showToast('Error', data.message, 'error');
                }
            });
        }
    }

    // Copy to clipboard
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(() => {
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 1000);
            
            showToast('Copied', 'Copied to clipboard', 'success');
        });
    }

    // Show secret modal
    function showSecret(secret) {
        document.getElementById('secretValue').textContent = secret;
        secretModal.show();
    }

    // Copy secret from modal
    function copySecret() {
        const secret = document.getElementById('secretValue').textContent;
        navigator.clipboard.writeText(secret).then(() => {
            showToast('Copied', 'Secret copied to clipboard', 'success');
        });
    }

    // Show toast notification
    function showToast(title, message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        const toastId = 'toast-' + Date.now();
        
        const bgClass = type === 'success' ? 'bg-success' : 
                       type === 'error' ? 'bg-danger' : 
                       type === 'warning' ? 'bg-warning' : 'bg-primary';
        
        const toast = document.createElement('div');
        toast.className = `toast ${bgClass} text-white`;
        toast.id = toastId;
        toast.innerHTML = `
            <div class="toast-header ${bgClass} text-white border-0">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // =====================================
    // LOGS FUNCTIONALITY - START
    // =====================================

    // Global variables for logs
    let currentPage = 1;
    let currentFilters = {};
    let logFiltersData = {};
    let logDetailModal = null;

    // Initialize logs modal
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('logDetailModal')) {
            logDetailModal = new bootstrap.Modal(document.getElementById('logDetailModal'));
        }
    });

    // Load logs when tab is clicked
    function loadLogs(page = 1) {
        currentPage = page;
        const logsLoading = document.getElementById('logsLoading');
        const logsTableContainer = document.getElementById('logsTableContainer');
        const noLogsMessage = document.getElementById('noLogsMessage');
        const logsDisabled = document.getElementById('logsDisabled');

        if (logsLoading) logsLoading.classList.remove('d-none');
        if (logsTableContainer) logsTableContainer.classList.add('d-none');
        if (noLogsMessage) noLogsMessage.classList.add('d-none');
        if (logsDisabled) logsDisabled.classList.add('d-none');

        const params = new URLSearchParams({
            page: page,
            per_page: 25,
            ...currentFilters
        });

        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/logs?${params}`)
            .then(response => response.json())
            .then(data => {
                if (logsLoading) logsLoading.classList.add('d-none');

                if (!data.logs_enabled) {
                    if (logsDisabled) logsDisabled.classList.remove('d-none');
                    return;
                }

                if (data.success && data.data.length > 0) {
                    populateLogsTable(data.data);
                    setupLogsPagination(data);
                    if (logsTableContainer) logsTableContainer.classList.remove('d-none');
                } else {
                    if (noLogsMessage) noLogsMessage.classList.remove('d-none');
                }
            })
            .catch(error => {
                if (logsLoading) logsLoading.classList.add('d-none');
                showToast('Error', 'Failed to load logs', 'error');
            });
    }

    function populateLogsTable(logs) {
        const tbody = document.getElementById('logsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';

        logs.forEach(log => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div>${log.created_at}</div>
                    <small class="text-muted">${log.client_info || log.ip_address}</small>
                </td>
                <td>
                    <span class="badge bg-secondary">${log.method}</span>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 200px;" title="${log.url}">
                        ${log.url}
                    </div>
                </td>
                <td>
                    <div>${log.ip_address}</div>
                    ${log.user_agent ? `<small class="text-muted text-truncate d-block" style="max-width: 150px;" title="${log.user_agent}">${log.user_agent}</small>` : ''}
                </td>
                <td>
                    <span class="badge bg-${log.status_color}">${log.response_status}</span>
                    ${log.has_error ? '<i class="fas fa-exclamation-triangle text-warning ms-1"></i>' : ''}
                </td>
                <td>
                    ${log.api_key ? `
                        <div>
                            <strong>${log.api_key.description}</strong>
                            <br>
                            <code class="small">${log.api_key.key_preview}</code>
                            ${log.api_key.owner_display_name ? `
                                <br>
                                <small class="text-muted">
                                    ${log.api_key.owner_label}: ${log.api_key.owner_display_name}
                                </small>
                            ` : ''}
                        </div>
                    ` : `
                        <span class="badge bg-${log.is_authenticated ? 'success' : 'danger'}">
                            ${log.is_authenticated ? 'Authenticated' : 'Failed Auth'}
                        </span>
                    `}
                </td>
                <td>
                    <div>${log.formatted_execution_time}</div>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="showLogDetail(${log.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function setupLogsPagination(data) {
        const paginationContainer = document.getElementById('logsPagination');
        if (!paginationContainer) return;
        
        paginationContainer.innerHTML = '';

        if (data.last_page <= 1) return;

        const pagination = document.createElement('nav');
        pagination.innerHTML = `
            <ul class="pagination pagination-sm">
                <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadLogs(${data.current_page - 1})">Previous</a>
                </li>
                ${generatePageNumbers(data.current_page, data.last_page)}
                <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadLogs(${data.current_page + 1})">Next</a>
                </li>
            </ul>
        `;

        paginationContainer.appendChild(pagination);
    }

    function generatePageNumbers(currentPage, lastPage) {
        let pages = '';
        const maxPages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
        let endPage = Math.min(lastPage, startPage + maxPages - 1);

        if (endPage - startPage < maxPages - 1) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            pages += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadLogs(${i})">${i}</a>
                </li>
            `;
        }

        return pages;
    }

    function showLogFilters() {
        const filtersContainer = document.getElementById('logFilters');
        if (!filtersContainer) return;
        
        if (filtersContainer.classList.contains('d-none')) {
            loadLogFiltersData();
            filtersContainer.classList.remove('d-none');
        } else {
            filtersContainer.classList.add('d-none');
        }
    }

    function loadLogFiltersData() {
        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/logs/filters/options`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logFiltersData = data.filters;
                    populateFilterDropdowns();
                }
            })
            .catch(error => {
                showToast('Error', 'Failed to load filter options', 'error');
            });
    }

    function populateFilterDropdowns() {
        // Populate API Keys dropdown
        const apiKeySelect = document.getElementById('filter_api_key_id');
        if (apiKeySelect && logFiltersData.api_keys) {
            apiKeySelect.innerHTML = '<option value="">All API Keys</option>';
            logFiltersData.api_keys.forEach(apiKey => {
                apiKeySelect.innerHTML += `<option value="${apiKey.id}">${apiKey.label}</option>`;
            });
        }

        // Populate Methods dropdown
        const methodSelect = document.getElementById('filter_method');
        if (methodSelect && logFiltersData.methods) {
            methodSelect.innerHTML = '<option value="">All Methods</option>';
            logFiltersData.methods.forEach(method => {
                methodSelect.innerHTML += `<option value="${method}">${method}</option>`;
            });
        }

        // Populate Status Codes dropdown
        const statusSelect = document.getElementById('filter_status_code');
        if (statusSelect && logFiltersData.status_codes) {
            statusSelect.innerHTML = '<option value="">All Status</option>';
            logFiltersData.status_codes.forEach(status => {
                statusSelect.innerHTML += `<option value="${status}">${status}</option>`;
            });
        }
    }

    function applyLogFilters(event) {
        if (event) event.preventDefault();
        const form = document.getElementById('logFilterForm');
        if (!form) return;
        
        const formData = new FormData(form);
        currentFilters = {};

        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                currentFilters[key] = value;
            }
        }

        loadLogs(1);
    }

    function clearLogFilters() {
        const form = document.getElementById('logFilterForm');
        if (form) {
            form.reset();
            currentFilters = {};
            loadLogs(1);
        }
    }

    function refreshLogs() {
        loadLogs(currentPage);
    }

    function showLogDetail(logId) {
        fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/logs/${logId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateLogDetailModal(data.log);
                    if (logDetailModal) {
                        logDetailModal.show();
                    }
                } else {
                    showToast('Error', data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error', 'Failed to load log details', 'error');
            });
    }

    function populateLogDetailModal(log) {
        const content = document.getElementById('logDetailContent');
        if (!content) return;
        
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle me-2"></i> Request Information</h6>
                    <table class="table table-bordered table-striped table-sm">
                        <tr><th>Time:</th><td class="text-break small">${log.created_at}</td></tr>
                        <tr><th>Request ID:</th><td class="text-break small">${log.request_id || 'N/A'}</td></tr>
                        <tr><th>Method:</th><td class="text-break small"><span class="badge bg-secondary">${log.method}</span></td></tr>
                        <tr><th>URL:</th><td class="text-break small">${log.url}</td></tr>
                        <tr><th>Route:</th><td class="text-break small">${log.route || 'N/A'}</td></tr>
                        <tr><th>IP Address:</th><td class="text-break small">${log.ip_address}</td></tr>
                        <tr><th>User Agent:</th><td class="text-break small">${log.user_agent || 'N/A'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-chart-bar me-2"></i> Response Information</h6>
                    <table class="table table-bordered table-striped table-sm">
                        <tr><th>Status:</th><td class="text-break small"><span class="badge bg-${getStatusColor(log.response_status)}">${log.response_status}</span></td></tr>
                        <tr><th>Duration:</th><td class="text-break small">${log.formatted_execution_time}</td></tr>
                        <tr><th>Authenticated:</th><td class="text-break small"><span class="badge bg-${log.is_authenticated ? 'success' : 'danger'}">${log.is_authenticated ? 'Yes' : 'No'}</span></td></tr>
                        ${log.api_key ? `
                            <tr><th>API Key:</th><td>
                                <div><strong>${log.api_key.description}</strong></div>
                                <div><code>${log.api_key.key}</code></div>
                                <div><small class="text-muted">Mode: ${log.api_key.mode.toUpperCase()}</small></div>
                                ${log.api_key.owner_display_name ? `
                                    <div><small class="text-muted">${log.api_key.owner_label}: ${log.api_key.owner_display_name}</small></div>
                                ` : ''}
                            </td></tr>
                        ` : ''}
                        ${log.error_message ? `<tr><th>Error:</th><td class="text-danger small">${log.error_message}</td></tr>` : ''}
                    </table>
                </div>
            </div>

            ${log.query_parameters && Object.keys(log.query_parameters).length > 0 ? `
                <div class="mt-3">
                    <h6><i class="fas fa-question-circle me-2"></i>Query Parameters</h6>
                    <pre class="bg-light p-3 rounded small"><code>${JSON.stringify(log.query_parameters, null, 2)}</code></pre>
                </div>
            ` : ''}

            ${log.request_headers && Object.keys(log.request_headers).length > 0 ? `
                <div class="mt-3">
                    <h6><i class="fas fa-arrow-up me-2"></i> Request Headers</h6>
                    <pre class="bg-light p-3 rounded small"><code>${JSON.stringify(log.request_headers, null, 2)}</code></pre>
                </div>
            ` : ''}

            ${log.request_body ? `
                <div class="mt-3">
                    <h6><i class="fas fa-file-code me-2"></i> Request Body</h6>
                    <pre class="bg-light p-3 rounded text-break small"><code>${log.request_body}</code></pre>
                </div>
            ` : ''}

            ${log.response_headers && Object.keys(log.response_headers).length > 0 ? `
                <div class="mt-3">
                    <h6><i class="fas fa-arrow-down me-2"></i> Response Headers</h6>
                    <pre class="bg-light p-3 rounded small"><code>${JSON.stringify(log.response_headers, null, 2)}</code></pre>
                </div>
            ` : ''}

            ${log.response_body ? `
                <div class="mt-3">
                    <h6><i class="fas fa-file-alt me-2"></i> Response Body</h6>
                    <pre class="bg-light p-3 rounded text-break small"><code>${log.response_body}</code></pre>
                </div>
            ` : ''}

            ${log.error_trace ? `
                <div class="mt-3">
                    <h6><i class="fas fa-bug me-2"></i> Error Trace</h6>
                    <pre class="bg-light p-3 rounded text-break small"><code>${log.error_trace}</code></pre>
                </div>
            ` : ''}
        `;
    }

    function getStatusColor(status) {
        if (status >= 200 && status < 300) return 'success';
        if (status >= 300 && status < 400) return 'warning';
        if (status >= 400 && status < 500) return 'danger';
        if (status >= 500) return 'dark';
        return 'secondary';
    }

    // =====================================
    // LOGS FUNCTIONALITY - END
    // =====================================
</script>