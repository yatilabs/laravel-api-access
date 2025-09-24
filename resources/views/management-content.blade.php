<!-- Toast Container -->
@if (!config('api-access.layout'))
    <div class="toast-container"></div>
@endif
<style>
button.btn-close {
    border: none;
}
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0">API Keys</h4>
                <p class="text-muted mb-0">Manage your API keys and domains</p>
            </div>

            <div class="card-body">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs mb-4" id="managementTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#api-keys">
                            <i class="fas fa-key me-2"></i> API Keys
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#domains">
                            <i class="fas fa-globe me-2"></i> Domains
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
                                        <th>Status</th>
                                        <th>Usage</th>
                                        <th>Expires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="apiKeysTableBody">
                                    @foreach ($apiKeys as $apiKey)
                                        <tr data-api-key-id="{{ $apiKey->id }}">
                                            <td>
                                                <strong>{{ $apiKey->description ?: 'Unnamed Key' }}</strong>
                                                <span
                                                    class="badge mode-badge {{ $apiKey->mode === 'live' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                    {{ ucfirst($apiKey->mode) }}
                                                </span>
                                                <br>
                                                <small class="text-muted">Created
                                                    {{ $apiKey->created_at->format('M j, Y') }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <code
                                                        class="api-key-value">{{ \Illuminate\Support\Str::limit($apiKey->key, 12, '...') }}</code>
                                                    <button class="btn btn-sm btn-outline-secondary copy-btn"
                                                        onclick="copyToClipboard('{{ $apiKey->key }}', this)"
                                                        title="Copy API Key">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($apiKey->secret)
                                                    <div class="d-flex align-items-center">
                                                        <code class="secret-value">••••••••••</code>
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
                                                <span
                                                    class="badge status-badge {{ $apiKey->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div>{{ number_format($apiKey->usage_count) }} requests</div>
                                                @if ($apiKey->last_used_at)
                                                    <small class="text-muted">Last:
                                                        {{ $apiKey->last_used_at->diffForHumans() }}</small>
                                                @else
                                                    <small class="text-muted">Never used</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($apiKey->expires_at)
                                                    <div>{{ $apiKey->expires_at->format('M j, Y') }}</div>
                                                    <small
                                                        class="text-{{ $apiKey->expires_at->isPast() ? 'danger' : 'muted' }}">
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
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-{{ $apiKey->is_active ? 'warning' : 'success' }}"
                                                        onclick="toggleStatus({{ $apiKey->id }})"
                                                        title="{{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}">
                                                        <i
                                                            class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteApiKey({{ $apiKey->id }})" title="Delete">
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
                        @if ($apiKeys->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $apiKeys->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- Domains Tab -->
                    <div class="tab-pane fade" id="domains">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Domains</h5>
                            <button type="button" class="btn btn-primary" onclick="showCreateDomainModal()">
                                <i class="fas fa-plus me-2"></i>Add Domain
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>API Key</th>
                                        <th>Domain</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="domainsTableBody">
                                    @foreach ($apiKeys as $apiKey)
                                        @foreach ($apiKey->domains as $domain)
                                            <tr data-domain-id="{{ $domain->id }}">
                                                <td>
                                                    <div>
                                                        <strong>{{ $apiKey->description ?: 'Unnamed Key' }}</strong>
                                                        <br>
                                                        <code class="text-muted small">{{ \Illuminate\Support\Str::limit($apiKey->key, 12, '...') }}</code>
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
                                                    <small
                                                        class="text-muted">{{ $domain->created_at->diffForHumans() }}</small>
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

                        @if (
                            $apiKeys->sum(function ($key) {
                                return $key->domains->count();
                            }) === 0)
                            <div class="text-center py-5">
                                <i class="fas fa-globe text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">No Domains</h5>
                                <p class="text-muted">Add domain to control where your API keys can be used.</p>
                                <button type="button" class="btn btn-primary" onclick="showCreateDomainModal()">
                                    <i class="fas fa-plus me-2"></i>Add First Domain
                                </button>
                            </div>
                        @endif
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
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
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
                                <select class="form-select form-control" id="mode" name="mode">
                                    <option value="test">Test Mode</option>
                                    <option value="live">Live Mode</option>
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
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        value="1" checked>
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
                        <i class="fas fa-save me-2"></i> Save API Key
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
                <h5 class="modal-title" id="domainModalTitle">Add Domain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
            </div>
            <form id="domainForm" onsubmit="submitDomainForm(event)">
                <div class="modal-body">
                    <input type="hidden" id="domainId" name="domain_id">

                    <div class="mb-3">
                        <label for="api_key_id" class="form-label">API Key</label>
                        <select class="form-select form-control" id="api_key_id" name="api_key_id" required>
                            <option value="">Select API Key</option>
                            @foreach ($apiKeysForDropdown as $apiKey)
                                <option value="{{ $apiKey->id }}">
                                    {{ $apiKey->description ?: 'Unnamed Key' }} ({{ substr($apiKey->key, 0, 12) }}...)
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
                        <i class="fas fa-save me-2"></i>Save Domain
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
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
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
                        <button class="btn btn-outline-secondary copy-btn" onclick="copySecret()"
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
