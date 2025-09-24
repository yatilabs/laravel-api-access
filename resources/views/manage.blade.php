<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Access Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
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
</head>
<body class="bg-light">
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">API Access Management</h4>
                        <p class="text-muted mb-0">Manage your API keys and domain restrictions</p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs mb-4" id="managementTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#api-keys">
                                    <i class="fas fa-key me-2"></i>API Keys
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#domains">
                                    <i class="fas fa-globe me-2"></i>Domain Restrictions
                                </a>
                            </li>
                        </ul>
                        
                        <!-- Tab content -->
                        <div class="tab-content">
                            <!-- API Keys Tab -->
                            <div class="tab-pane fade show active" id="api-keys">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Your API Keys</h5>
                                    <button type="button" class="btn btn-primary" onclick="showCreateApiKeyModal()">
                                        <i class="fas fa-plus me-2"></i>New API Key
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th>API Key</th>
                                                <th>Secret</th>
                                                <th>Mode</th>
                                                <th>Status</th>
                                                <th>Usage</th>
                                                <th>Expires</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="apiKeysTableBody">
                                            @foreach($apiKeys as $apiKey)
                                            <tr data-api-key-id="{{ $apiKey->id }}">
                                                <td>
                                                    <strong>{{ $apiKey->description ?: 'Unnamed Key' }}</strong>
                                                    <br>
                                                    <small class="text-muted">Created {{ $apiKey->created_at->format('M j, Y') }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <code class="api-key-value">{{ $apiKey->key }}</code>
                                                        <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                                                onclick="copyToClipboard('{{ $apiKey->key }}', this)" 
                                                                title="Copy API Key">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($apiKey->secret)
                                                        <div class="d-flex align-items-center">
                                                            <code class="secret-value">••••••••••••••••</code>
                                                            <button class="btn btn-sm btn-outline-warning copy-btn" 
                                                                    onclick="regenerateSecret({{ $apiKey->id }})" 
                                                                    title="Regenerate Secret">
                                                                <i class="fas fa-refresh"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No secret</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge mode-badge {{ $apiKey->mode === 'live' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                        {{ ucfirst($apiKey->mode) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge status-badge {{ $apiKey->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>{{ number_format($apiKey->usage_count) }} requests</div>
                                                    @if($apiKey->last_used_at)
                                                        <small class="text-muted">Last: {{ $apiKey->last_used_at->diffForHumans() }}</small>
                                                    @else
                                                        <small class="text-muted">Never used</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($apiKey->expires_at)
                                                        <div>{{ $apiKey->expires_at->format('M j, Y') }}</div>
                                                        <small class="text-{{ $apiKey->expires_at->isPast() ? 'danger' : 'muted' }}">
                                                            {{ $apiKey->expires_at->diffForHumans() }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">Never</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="showEditApiKeyModal({{ $apiKey->id }})" 
                                                                title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-{{ $apiKey->is_active ? 'warning' : 'success' }}" 
                                                                onclick="toggleStatus({{ $apiKey->id }})" 
                                                                title="{{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteApiKey({{ $apiKey->id }})" 
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                @if($apiKeys->hasPages())
                                    <div class="d-flex justify-content-center mt-4">
                                        {{ $apiKeys->links() }}
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Domains Tab -->
                            <div class="tab-pane fade" id="domains">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Domain Restrictions</h5>
                                    <button type="button" class="btn btn-primary" onclick="showCreateDomainModal()">
                                        <i class="fas fa-plus me-2"></i>Add Domain Restriction
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>API Key</th>
                                                <th>Domain Pattern</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="domainsTableBody">
                                            @foreach($apiKeys as $apiKey)
                                                @foreach($apiKey->domains as $domain)
                                                <tr data-domain-id="{{ $domain->id }}">
                                                    <td>
                                                        <div>
                                                            <strong>{{ $apiKey->description ?: 'Unnamed Key' }}</strong>
                                                            <br>
                                                            <code class="text-muted small">{{ $apiKey->key }}</code>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code class="api-key-value">{{ $domain->domain_pattern }}</code>
                                                        <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                                                onclick="copyToClipboard('{{ $domain->domain_pattern }}', this)" 
                                                                title="Copy Pattern">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div>{{ $domain->created_at->format('M j, Y') }}</div>
                                                        <small class="text-muted">{{ $domain->created_at->diffForHumans() }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    onclick="showEditDomainModal({{ $domain->id }})" 
                                                                    title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteDomain({{ $domain->id }})" 
                                                                    title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($apiKeys->sum(function($key) { return $key->domains->count(); }) === 0)
                                    <div class="text-center py-5">
                                        <i class="fas fa-globe text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="text-muted mt-3">No Domain Restrictions</h5>
                                        <p class="text-muted">Add domain restrictions to control where your API keys can be used.</p>
                                        <button type="button" class="btn btn-primary" onclick="showCreateDomainModal()">
                                            <i class="fas fa-plus me-2"></i>Add First Domain Restriction
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Key Modal -->
    <div class="modal fade" id="apiKeyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="apiKeyModalTitle">Create API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="apiKeyForm" onsubmit="submitApiKeyForm(event)">
                    <div class="modal-body">
                        <input type="hidden" id="apiKeyId" name="api_key_id">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" 
                                           placeholder="Enter a description for this API key">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="mode" class="form-label">Mode</label>
                                    <select class="form-select" id="mode" name="mode">
                                        <option value="test">Test Mode (allows localhost)</option>
                                        <option value="live">Live Mode (strict domain validation)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expires_at" class="form-label">Expires At (Optional)</label>
                                    <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="apiKeySubmitBtn">
                            <i class="fas fa-save me-2"></i>Save API Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Domain Modal -->
    <div class="modal fade" id="domainModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="domainModalTitle">Add Domain Restriction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="domainForm" onsubmit="submitDomainForm(event)">
                    <div class="modal-body">
                        <input type="hidden" id="domainId" name="domain_id">
                        
                        <div class="mb-3">
                            <label for="api_key_id" class="form-label">API Key</label>
                            <select class="form-select" id="api_key_id" name="api_key_id" required>
                                <option value="">Select API Key</option>
                                @foreach($apiKeysForDropdown as $apiKey)
                                    <option value="{{ $apiKey->id }}">
                                        {{ $apiKey->description ?: 'Unnamed Key' }} ({{ substr($apiKey->key, 0, 20) }}...)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="domain_pattern" class="form-label">Domain Pattern</label>
                            <input type="text" class="form-control" id="domain_pattern" name="domain_pattern" 
                                   placeholder="example.com or *.example.com" required>
                            <div class="form-text">
                                <strong>Examples:</strong><br>
                                • <code>example.com</code> - Exact match<br>
                                • <code>*.example.com</code> - Subdomain wildcard<br>
                                • <code>*</code> - Match any domain
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="domainSubmitBtn">
                            <i class="fas fa-save me-2"></i>Save Domain Restriction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Secret Display Modal -->
    <div class="modal fade" id="secretModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Important: Save Your Secret
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>This is the only time you will see this secret!</strong><br>
                        Make sure to copy and store it securely.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Secret:</label>
                        <div class="d-flex align-items-center">
                            <div class="secret-display flex-grow-1" id="secretValue"></div>
                            <button class="btn btn-outline-secondary copy-btn" 
                                    onclick="copySecret()" 
                                    title="Copy Secret">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I've Saved It</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery for AJAX requests -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
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
            document.getElementById('apiKeySubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Create API Key';
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
                        document.getElementById('apiKeySubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update API Key';
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
        function submitApiKeyForm(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('apiKeyForm'));
            const isEdit = document.getElementById('apiKeyId').value !== '';
            
            const url = isEdit ? 
                `{{ config('api-access.routes.prefix', 'api-access') }}/api-keys/${document.getElementById('apiKeyId').value}` :
                `{{ config('api-access.routes.prefix', 'api-access') }}/api-keys`;
            
            const method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
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
            document.getElementById('domainModalTitle').textContent = 'Add Domain Restriction';
            document.getElementById('domainSubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Add Restriction';
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
                        document.getElementById('domainModalTitle').textContent = 'Edit Domain Restriction';
                        document.getElementById('domainSubmitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Restriction';
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
        function submitDomainForm(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('domainForm'));
            const isEdit = document.getElementById('domainId').value !== '';
            
            const url = isEdit ? 
                `{{ config('api-access.routes.prefix', 'api-access') }}/domains/${document.getElementById('domainId').value}` :
                `{{ config('api-access.routes.prefix', 'api-access') }}/domains`;
            
            const method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
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
            if (confirm('Are you sure you want to delete this domain restriction?')) {
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
</body>
</html>