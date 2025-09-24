@php
    // Get the management data
    $apiKeys = Yatilabs\ApiAccess\Models\ApiKey::with('domains')->paginate(10);
    $apiKeysForDropdown = Yatilabs\ApiAccess\Models\ApiKey::all();
@endphp

@if(config('api-access.layout'))
    @extends(config('api-access.layout'))

    @section('content')
        @include('api-access::management-content')
    @endsection

    @push('scripts')
        @include('api-access::management-scripts')
    @endpush
@else
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        .api-key-value, .secret-value {
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            word-break: break-all;
        }
        
        .copy-btn {
            padding: 0.25rem 0.5rem;
            margin-left: 0.25rem;
        }
        
        .secret-display {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
            font-family: monospace;
            font-size: 0.875rem;
            word-break: break-all;
            margin-right: 0.5rem;
        }
        
        .status-badge, .mode-badge {
            font-size: 0.75rem;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .btn-group .btn {
            margin-right: 0;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Toast container -->
    <div class="toast-container"></div>

    <div class="container-fluid py-4">
        @include('api-access::management-content')
    </div>

    @include('api-access::management-scripts')
</body>
</html>
@endif