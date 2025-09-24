<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery for AJAX requests -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- API Access Management Styles (only needed when using     // Delete domain
    window.deleteDomain = function(domainId) {
        if (confirm('Are you sure you want to delete this domain?')) {
            fetch(`{{ config('api-access.routes.prefix', 'api-access') }}/domains/${domainId}/delete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })yout) -->
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
    
    .table th {
        border-top: none;
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
        apiKeyModal.show();
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
                    apiKeyModal.show();
                } else {
                    showToast('Error', data.message, 'error');
                }
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
                    showSecret(data.plain_secret);
                }
                
                location.reload(); // Refresh to show updated data
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
</script>