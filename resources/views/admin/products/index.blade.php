@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Products</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Products</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 align-items-center w-100">
                <div class="d-flex gap-2 flex-grow-1 align-items-center">
                    <input type="search" id="product-search" class="form-control" placeholder="Search item code, name, pack size..." value="{{ $q }}" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#products-filter-collapse" aria-expanded="false" aria-controls="products-filter-collapse">
                        <i class="fe fe-filter me-1"></i>Filters
                    </button>
                </div>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary ms-auto">
                    <i class="fe fe-plus me-2"></i>Create Product
                </a>
            </div>
            <div class="collapse mt-3" id="products-filter-collapse">
                <div class="d-flex flex-wrap gap-3 align-items-end">
                    <div>
                        <label class="form-label small mb-1">Category</label>
                        <select id="product-category-filter" class="form-select form-select-sm" style="min-width: 200px;">
                            <option value="">All categories</option>
                            @foreach($categories ?? [] as $cat)
                                <option value="{{ $cat->id }}" {{ (string) ($categoryId ?? '') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="product-filter-apply" class="btn btn-sm btn-primary">Apply</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Item Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Pack Size</th>
                            <th class="text-end">Cost Price</th>
                            <th class="text-end">Selling Price</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">BV</th>
                            <th class="text-end">PV</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        @forelse($products as $product)
                            <tr data-product-row>
                                <td>
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="" class="rounded" style="max-width: 48px; max-height: 48px; object-fit: cover;">
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $product->item_code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>{{ $product->pack_size ?? '—' }}</td>
                                <td class="text-end">{{ $product->formatted_cost_price }}</td>
                                <td class="text-end">{{ $product->formatted_price }}</td>
                                <td class="text-end">{{ $product->stock }}</td>
                                <td class="text-end">{{ number_format($product->bv, 1) }}</td>
                                <td class="text-end">{{ number_format($product->pv, 1) }}</td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr id="products-empty-row">
                                <td colspan="12" class="text-center text-muted p-4">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($products->hasPages() && !request()->header('X-Requested-With'))
            <div class="card-footer" id="products-pagination">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Stock / Expiry / Low Stock Reports --}}
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Inventory Reports</h3>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills mb-3" id="inventoryReportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="stock-report-tab" data-bs-toggle="pill" data-bs-target="#stock-report-panel" type="button" role="tab">Stock Report</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="expiry-report-tab" data-bs-toggle="pill" data-bs-target="#expiry-report-panel" type="button" role="tab">Expiry Report</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="lowstock-report-tab" data-bs-toggle="pill" data-bs-target="#lowstock-report-panel" type="button" role="tab">Low Stock Report</button>
                </li>
            </ul>
            <div class="tab-content" id="inventoryReportTabsContent">
                <div class="tab-pane fade show active" id="stock-report-panel" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Batch Number</th>
                                    <th class="text-end">Quantity Available</th>
                                    <th class="text-end">Cost Price</th>
                                    <th class="text-end">Selling Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockProducts as $p)
                                    <tr>
                                        <td>{{ $p->name }} @if($p->pack_size)<small class="text-muted">({{ $p->pack_size }})</small>@endif</td>
                                        <td>{{ $p->batch_number ?? '—' }}</td>
                                        <td class="text-end">{{ $p->stock }}</td>
                                        <td class="text-end">{{ $p->formatted_cost_price }}</td>
                                        <td class="text-end">{{ $p->formatted_price }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No products.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="expiry-report-panel" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Expiry Date</th>
                                    <th>Batch Tracking</th>
                                    <th>Expiry Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiryProducts as $p)
                                    <tr>
                                        <td>{{ $p->name }} @if($p->pack_size)<small class="text-muted">({{ $p->pack_size }})</small>@endif</td>
                                        <td>{{ $p->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $p->batch_number ?? '—' }}</td>
                                        <td>
                                            @if($p->expiry_date)
                                                @if($p->expiry_date->isPast())
                                                    <span class="badge bg-danger">Expired</span>
                                                @elseif(!$p->expiry_date->isPast() && $p->expiry_date->diffInDays(now()->startOfDay(), false) <= 30)
                                                    <span class="badge bg-warning">Expiring Soon</span>
                                                @else
                                                    <span class="badge bg-secondary">OK</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No expiry dates set.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="lowstock-report-panel" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Minimum Stock Level</th>
                                    <th class="text-end">Current Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProducts as $p)
                                    <tr>
                                        <td>{{ $p->name }} @if($p->pack_size)<small class="text-muted">({{ $p->pack_size }})</small>@endif</td>
                                        <td class="text-end">{{ $p->min_stock }}</td>
                                        <td class="text-end">{{ $p->stock }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No low stock items.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
(function() {
    var searchInput = document.getElementById('product-search');
    var categorySelect = document.getElementById('product-category-filter');
    var filterApplyBtn = document.getElementById('product-filter-apply');
    var tbody = document.getElementById('products-tbody');
    var paginationEl = document.getElementById('products-pagination');
    var baseUrl = '{{ route("admin.products.index") }}';
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var debounceTimer;

    function buildQueryParams() {
        var params = new URLSearchParams();
        var q = (searchInput && searchInput.value) ? searchInput.value.trim() : '';
        var categoryId = (categorySelect && categorySelect.value) ? categorySelect.value : '';
        if (q) params.set('q', q);
        if (categoryId) params.set('category_id', categoryId);
        return params.toString();
    }

    function renderRow(p) {
        var imgHtml = p.image_url
            ? '<img src="' + p.image_url + '" alt="" class="rounded" style="max-width:48px;max-height:48px;object-fit:cover;">'
            : '<span class="text-muted">—</span>';
        var statusBadge = p.is_active
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
        var formHtml = '<form action="' + p.destroy_url + '" method="POST" class="d-inline" onsubmit="return confirm(\'Delete this product?\');">' +
            '<input type="hidden" name="_token" value="' + (csrfToken || '') + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>';
        return '<tr data-product-row>' +
            '<td>' + imgHtml + '</td>' +
            '<td>' + (p.item_code || '') + '</td>' +
            '<td>' + (p.name || '') + '</td>' +
            '<td>' + (p.category_name || '—') + '</td>' +
            '<td>' + (p.pack_size || '—') + '</td>' +
            '<td class="text-end">' + (p.cost_price || '—') + '</td>' +
            '<td class="text-end">' + (p.price || '') + '</td>' +
            '<td class="text-end">' + (p.stock || 0) + '</td>' +
            '<td class="text-end">' + (p.bv || '') + '</td>' +
            '<td class="text-end">' + (p.pv || '') + '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td class="text-end">' +
            '<a href="' + p.edit_url + '" class="btn btn-sm btn-outline-primary">Edit</a> ' + formHtml +
            '</td></tr>';
    }

    function doSearch() {
        var query = buildQueryParams();
        var url = baseUrl + (query ? '?' + query : '');
        fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!tbody) return;
            tbody.innerHTML = '';
            if (data.products && data.products.length > 0) {
                data.products.forEach(function(p) {
                    tbody.insertAdjacentHTML('beforeend', renderRow(p));
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted p-4">No products found.</td></tr>';
            }
            if (paginationEl) paginationEl.style.display = 'none';
        })
        .catch(function() {
            if (tbody) tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger p-4">Search failed. Reload the page.</td></tr>';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(doSearch, 300);
        });
        searchInput.addEventListener('search', function() { doSearch(); });
    }
    if (filterApplyBtn) {
        filterApplyBtn.addEventListener('click', function() {
            var query = buildQueryParams();
            window.location.href = baseUrl + (query ? '?' + query : '');
        });
    }
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            var query = buildQueryParams();
            window.location.href = baseUrl + (query ? '?' + query : '');
        });
    }
})();
    </script>
    @endpush
@endsection
