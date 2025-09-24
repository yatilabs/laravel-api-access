{{-- API Access Management View --}}
{{-- This view is designed to be included in your app's layout --}}

<link href="{{ asset('vendor/yatilabs/api-access/api-access.css') }}" rel="stylesheet">

<div class="api-access-wrapper">
    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul style="margin-bottom: 0; margin-top: 0.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Navigation Tabs --}}
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $currentTab === 'api-keys' ? 'active' : '' }}" 
               href="?tab=api-keys" 
               role="tab">
                API Keys
                <span class="badge badge-primary ms-2">{{ $apiKeys->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $currentTab === 'domains' ? 'active' : '' }}" 
               href="?tab=domains" 
               role="tab">
                Domain Restrictions
                <span class="badge badge-secondary ms-2">{{ $apiKeys->sum(function($key) { return $key->domains->count(); }) }}</span>
            </a>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
        {{-- API Keys Tab --}}
        <div class="tab-pane {{ $currentTab === 'api-keys' ? 'active' : '' }}" role="tabpanel">
            
            {{-- Create New API Key Card --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Create New API Key</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleCreateForm()">
                            <span id="toggle-text">Show Form</span>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="create-form" style="display: none;">
                    <form method="POST" action="{{ route('api-keys.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="description" 
                                           name="description" 
                                           value="{{ old('description') }}"
                                           placeholder="e.g., Mobile App API, Admin Dashboard">
                                    <div class="form-text">Optional: A human-readable description for this API key</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="mode" class="form-label">Mode</label>
                                    <select class="form-select" id="mode" name="mode" required>
                                        <option value="">Select Mode</option>
                                        @foreach($modes as $value => $label)
                                            <option value="{{ $value }}" {{ old('mode') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Test mode allows localhost, live mode requires domain restrictions</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="expires_at" class="form-label">Expiration Date</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="expires_at" 
                                           name="expires_at" 
                                           value="{{ old('expires_at') }}"
                                           min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                    <div class="form-text">Optional: Leave blank for no expiration</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               {{ old('is_active', '1') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <div class="form-text">Inactive API keys will be rejected</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="toggleCreateForm()">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                Create API Key
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- API Keys List --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your API Keys</h5>
                </div>
                <div class="card-body">
                    @if($apiKeys->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Description</th>
                                        <th>Mode</th>
                                        <th>Status</th>
                                        <th>Expires</th>
                                        <th>Usage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($apiKeys as $apiKey)
                                        <tr>
                                            <td>
                                                <div class="api-key-display">
                                                    {{ substr($apiKey->key, 0, 8) }}...{{ substr($apiKey->key, -4) }}
                                                </div>
                                                @if(session("new_secret_{$apiKey->id}"))
                                                    <div class="alert alert-warning mt-2" style="margin-bottom: 0;">
                                                        <strong>Secret (save this!):</strong>
                                                        <code>{{ session("new_secret_{$apiKey->id}") }}</code>
                                                    </div>
                                                @else
                                                    <small class="text-muted">Secret: ****{{ substr($apiKey->secret, -4) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $apiKey->description ?: 'No description' }}
                                                <br>
                                                <small class="text-muted">
                                                    Created {{ $apiKey->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $apiKey->mode === 'live' ? 'badge-success' : 'badge-warning' }}">
                                                    {{ ucfirst($apiKey->mode) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $apiKey->is_active ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($apiKey->expires_at)
                                                    <span class="{{ $apiKey->expires_at->isPast() ? 'text-danger' : ($apiKey->expires_at->isToday() ? 'text-warning' : '') }}">
                                                        {{ $apiKey->expires_at->format('M j, Y') }}
                                                        <br>
                                                        <small>{{ $apiKey->expires_at->diffForHumans() }}</small>
                                                    </span>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ number_format($apiKey->usage_count) }}</strong> requests
                                                @if($apiKey->last_used_at)
                                                    <br>
                                                    <small class="text-muted">Last: {{ $apiKey->last_used_at->diffForHumans() }}</small>
                                                @else
                                                    <br>
                                                    <small class="text-muted">Never used</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary" 
                                                            onclick="editApiKey({{ $apiKey->id }})">
                                                        Edit
                                                    </button>
                                                    
                                                    <form method="POST" action="{{ route('api-keys.toggle-status', $apiKey->id) }}" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-{{ $apiKey->is_active ? 'warning' : 'success' }}">
                                                            {{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-secondary" 
                                                            onclick="regenerateSecret({{ $apiKey->id }})">
                                                        New Secret
                                                    </button>
                                                    
                                                    <form method="POST" 
                                                          action="{{ route('api-keys.destroy', $apiKey->id) }}" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this API key? This action cannot be undone.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Edit Form Row (Hidden by default) --}}
                                        <tr id="edit-form-{{ $apiKey->id }}" style="display: none;">
                                            <td colspan="7">
                                                <form method="POST" action="{{ route('api-keys.update', $apiKey->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group mb-3">
                                                                <label class="form-label">Description</label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       name="description" 
                                                                       value="{{ $apiKey->description }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group mb-3">
                                                                <label class="form-label">Mode</label>
                                                                <select class="form-select" name="mode" required>
                                                                    @foreach($modes as $value => $label)
                                                                        <option value="{{ $value }}" {{ $apiKey->mode == $value ? 'selected' : '' }}>
                                                                            {{ $label }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group mb-3">
                                                                <label class="form-label">Expires At</label>
                                                                <input type="date" 
                                                                       class="form-control" 
                                                                       name="expires_at" 
                                                                       value="{{ $apiKey->expires_at ? $apiKey->expires_at->format('Y-m-d') : '' }}"
                                                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="form-group mb-3">
                                                                <label class="form-label">Status</label>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" 
                                                                           type="checkbox" 
                                                                           name="is_active" 
                                                                           value="1" 
                                                                           {{ $apiKey->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Active</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" 
                                                                class="btn btn-secondary me-2" 
                                                                onclick="cancelEdit({{ $apiKey->id }})">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="btn btn-success">
                                                            Update API Key
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if($apiKeys->hasPages())
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    Showing {{ $apiKeys->firstItem() }} to {{ $apiKeys->lastItem() }} of {{ $apiKeys->total() }} API keys
                                </div>
                                <div class="d-flex align-items-center">
                                    {{ $apiKeys->appends(request()->query())->links() }}
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <h6>No API Keys Found</h6>
                            <p class="text-muted">Create your first API key to get started.</p>
                            <button type="button" class="btn btn-primary" onclick="toggleCreateForm()">
                                Create First API Key
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Domains Tab --}}
        <div class="tab-pane {{ $currentTab === 'domains' ? 'active' : '' }}" role="tabpanel">
            {{-- Domain Management Content --}}
            @include('api-access::partials.domains', compact('apiKeys', 'apiKeysForDropdown'))
        </div>
    </div>
</div>

{{-- JavaScript --}}
<script>
    // Toggle create form
    function toggleCreateForm() {
        const form = document.getElementById('create-form');
        const toggleText = document.getElementById('toggle-text');
        
        if (form.style.display === 'none') {
            form.style.display = 'block';
            toggleText.textContent = 'Hide Form';
        } else {
            form.style.display = 'none';
            toggleText.textContent = 'Show Form';
        }
    }

    // Edit API Key
    function editApiKey(id) {
        document.getElementById('edit-form-' + id).style.display = 'table-row';
    }

    // Cancel Edit
    function cancelEdit(id) {
        document.getElementById('edit-form-' + id).style.display = 'none';
    }

    // Regenerate Secret
    function regenerateSecret(id) {
        if (confirm('Are you sure you want to regenerate the secret? The old secret will stop working immediately.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/api-keys/${id}/regenerate-secret`;
            form.innerHTML = '@csrf';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (!alert.textContent.includes('Secret')) { // Don't hide secret alerts
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            }
        });
    }, 5000);
</script>