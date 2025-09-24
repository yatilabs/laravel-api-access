{{-- Domain Restrictions Management --}}

{{-- Create Domain Restriction Card --}}
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Domain Restriction</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleDomainForm()">
                <span id="domain-toggle-text">Show Form</span>
            </button>
        </div>
    </div>
    <div class="card-body" id="domain-form" style="display: none;">
        @if($apiKeysForDropdown->count() > 0)
            <form method="POST" action="{{ route('api-keys.domains.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="api_key_id" class="form-label">API Key</label>
                            <select class="form-select" id="api_key_id" name="api_key_id" required>
                                <option value="">Select API Key</option>
                                @foreach($apiKeysForDropdown as $apiKey)
                                    <option value="{{ $apiKey->id }}" {{ old('api_key_id') == $apiKey->id ? 'selected' : '' }}>
                                        {{ substr($apiKey->key, 0, 8) }}...{{ substr($apiKey->key, -4) }}
                                        @if($apiKey->description)
                                            ({{ $apiKey->description }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="domain_pattern" class="form-label">Domain Pattern</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="domain_pattern" 
                                   name="domain_pattern" 
                                   value="{{ old('domain_pattern') }}"
                                   placeholder="e.g., example.com, *.example.com"
                                   required>
                            <div class="form-text">
                                Use * for wildcards. Examples: example.com, *.example.com, api.*.example.com
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary me-2" onclick="toggleDomainForm()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        Add Domain Restriction
                    </button>
                </div>
            </form>
        @else
            <div class="text-center py-3">
                <p class="text-muted">You need to create an API key first before adding domain restrictions.</p>
                <a href="?tab=api-keys" class="btn btn-primary">Create API Key</a>
            </div>
        @endif
    </div>
</div>

{{-- Domain Restrictions by API Key --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Domain Restrictions</h5>
    </div>
    <div class="card-body">
        @if($apiKeys->count() > 0)
            @foreach($apiKeys as $apiKey)
                @if($apiKey->domains->count() > 0 || $loop->first)
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>API Key:</strong>
                                    <code>{{ substr($apiKey->key, 0, 8) }}...{{ substr($apiKey->key, -4) }}</code>
                                    @if($apiKey->description)
                                        <span class="text-muted">({{ $apiKey->description }})</span>
                                    @endif
                                    <span class="badge {{ $apiKey->mode === 'live' ? 'badge-success' : 'badge-warning' }} ms-2">
                                        {{ ucfirst($apiKey->mode) }}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    {{ $apiKey->domains->count() }} restriction(s)
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($apiKey->domains->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Domain Pattern</th>
                                                <th>Type</th>
                                                <th>Examples</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($apiKey->domains as $domain)
                                                <tr>
                                                    <td>
                                                        <div class="domain-pattern">{{ $domain->domain_pattern }}</div>
                                                    </td>
                                                    <td>
                                                        @if(str_contains($domain->domain_pattern, '*'))
                                                            <span class="badge badge-warning">Wildcard</span>
                                                        @else
                                                            <span class="badge badge-secondary">Exact</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            @if($domain->domain_pattern === '*')
                                                                All domains
                                                            @elseif(str_contains($domain->domain_pattern, '*'))
                                                                @php
                                                                    $example1 = str_replace('*', 'app', $domain->domain_pattern);
                                                                    $example2 = str_replace('*', 'api', $domain->domain_pattern);
                                                                @endphp
                                                                {{ $example1 }}, {{ $example2 }}
                                                            @else
                                                                {{ $domain->domain_pattern }}
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-warning" 
                                                                    onclick="editDomain({{ $domain->id }})">
                                                                Edit
                                                            </button>
                                                            
                                                            <form method="POST" 
                                                                  action="{{ route('api-keys.domains.destroy', $domain->id) }}" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Are you sure you want to delete this domain restriction?')">
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
                                                <tr id="edit-domain-form-{{ $domain->id }}" style="display: none;">
                                                    <td colspan="4">
                                                        <form method="POST" action="{{ route('api-keys.domains.update', $domain->id) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            
                                                            <div class="row">
                                                                <div class="col-md-8">
                                                                    <div class="form-group mb-3">
                                                                        <label class="form-label">Domain Pattern</label>
                                                                        <input type="text" 
                                                                               class="form-control" 
                                                                               name="domain_pattern" 
                                                                               value="{{ $domain->domain_pattern }}"
                                                                               required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label">&nbsp;</label>
                                                                    <div class="d-flex">
                                                                        <button type="button" 
                                                                                class="btn btn-secondary me-2" 
                                                                                onclick="cancelDomainEdit({{ $domain->id }})">
                                                                            Cancel
                                                                        </button>
                                                                        <button type="submit" class="btn btn-success">
                                                                            Update
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center">
                                    <p class="text-muted">No domain restrictions set for this API key.</p>
                                    @if($apiKey->mode === 'test')
                                        <small class="text-info">
                                            <strong>Test mode:</strong> Automatically allows localhost and 127.0.0.1
                                        </small>
                                    @else
                                        <small class="text-warning">
                                            <strong>Live mode:</strong> All domains are blocked until you add restrictions
                                        </small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <div class="text-center py-4">
                <h6>No API Keys Found</h6>
                <p class="text-muted">Create an API key first to manage domain restrictions.</p>
                <a href="?tab=api-keys" class="btn btn-primary">Create API Key</a>
            </div>
        @endif
    </div>
</div>

{{-- Domain Testing Tool --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Test Domain Matching</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Test if a domain would be allowed for your API keys:</p>
        
        <form id="domain-test-form">
            <div class="row">
                <div class="col-md-4">
                    <select class="form-select" id="test-api-key" required>
                        <option value="">Select API Key</option>
                        @foreach($apiKeysForDropdown as $apiKey)
                            <option value="{{ $apiKey->id }}" data-mode="{{ $apiKey->mode }}">
                                {{ substr($apiKey->key, 0, 8) }}...{{ substr($apiKey->key, -4) }}
                                @if($apiKey->description)
                                    ({{ $apiKey->description }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" 
                           class="form-control" 
                           id="test-domain" 
                           placeholder="e.g., app.example.com"
                           required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        Test Domain
                    </button>
                </div>
            </div>
        </form>
        
        <div id="domain-test-result" class="mt-3" style="display: none;"></div>

        {{-- Domain Pattern Examples --}}
        <div class="mt-4">
            <h6>Domain Pattern Examples:</h6>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success">✓ Valid Patterns</h6>
                    <ul class="list-unstyled">
                        <li><code>example.com</code> - Exact domain match</li>
                        <li><code>*.example.com</code> - All subdomains</li>
                        <li><code>api.*.example.com</code> - Third-level wildcard</li>
                        <li><code>*</code> - All domains (not recommended for live)</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger">✗ Invalid Patterns</h6>
                    <ul class="list-unstyled">
                        <li><code>example*.com</code> - Wildcard must be separated by dots</li>
                        <li><code>..example.com</code> - No consecutive dots</li>
                        <li><code>example..com</code> - No consecutive dots</li>
                        <li><code>exam ple.com</code> - No spaces allowed</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Domain Management JavaScript --}}
<script>
    // Toggle domain form
    function toggleDomainForm() {
        const form = document.getElementById('domain-form');
        const toggleText = document.getElementById('domain-toggle-text');
        
        if (form.style.display === 'none') {
            form.style.display = 'block';
            toggleText.textContent = 'Hide Form';
        } else {
            form.style.display = 'none';
            toggleText.textContent = 'Show Form';
        }
    }

    // Edit domain
    function editDomain(id) {
        document.getElementById('edit-domain-form-' + id).style.display = 'table-row';
    }

    // Cancel domain edit
    function cancelDomainEdit(id) {
        document.getElementById('edit-domain-form-' + id).style.display = 'none';
    }

    // Test domain matching
    document.getElementById('domain-test-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const apiKeyId = document.getElementById('test-api-key').value;
        const domain = document.getElementById('test-domain').value;
        const resultDiv = document.getElementById('domain-test-result');
        
        if (!apiKeyId || !domain) {
            return;
        }

        // Show loading
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Testing...</div>';

        // Prepare data for testing
        const apiKeySelect = document.getElementById('test-api-key');
        const selectedOption = apiKeySelect.options[apiKeySelect.selectedIndex];
        const mode = selectedOption.getAttribute('data-mode');

        // Simple client-side testing logic
        fetch('{{ route("api-keys.domains.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                api_key_id: apiKeyId,
                domain: domain
            })
        })
        .then(response => response.json())
        .then(data => {
            let alertClass = data.allowed ? 'alert-success' : 'alert-danger';
            let result = data.allowed ? 'ALLOWED' : 'BLOCKED';
            
            resultDiv.innerHTML = `
                <div class="alert ${alertClass}">
                    <strong>Result:</strong> Domain <code>${data.domain}</code> is <strong>${result}</strong>
                    <br><small>${data.reason}</small>
                </div>
            `;
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> Could not test domain matching. ${error.message}
                </div>
            `;
        });
    });
</script>