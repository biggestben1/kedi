@extends('layouts.admin')

@section('title', 'KEDI Kits')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">KEDI Kits</h3>
                    <div class="card-options">
                        <a href="{{ route('admin.kedi-kits.create') }}" class="btn btn-primary btn-sm">
                            <i class="fe fe-plus"></i> Create New Kit
                        </a>
                    </div>
                </div>
                <div class="card-body">
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

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Price (₦)</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Purchased By</th>
                                    <th>KD Numbers Count</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kits as $kit)
                                    <tr>
                                        <td>{{ $kit->id }}</td>
                                        <td>
                                            <span class="badge bg-{{ $kit->category === 'english' ? 'primary' : 'info' }}">
                                                {{ $kit->category_label }}
                                            </span>
                                        </td>
                                        <td>₦{{ number_format($kit->price, 2) }}</td>
                                        <td>
                                            <strong class="text-{{ ($kit->quantity ?? 0) > 0 ? 'success' : 'danger' }}">
                                                {{ $kit->quantity ?? 0 }}
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $kit->is_old ? 'warning' : 'success' }}">
                                                {{ $kit->is_old ? 'Old' : 'New' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($kit->purchasedBy)
                                                {{ $kit->purchasedBy->name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $kit->items->count() }}</td>
                                        <td>{{ $kit->createdBy->name ?? 'N/A' }}</td>
                                        <td>{{ $kit->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.kedi-kits.show', $kit) }}" class="btn btn-sm btn-info">
                                                <i class="fe fe-eye"></i> View
                                            </a>
                                            <a href="{{ route('admin.kedi-kits.edit', $kit) }}" class="btn btn-sm btn-warning">
                                                <i class="fe fe-edit"></i> Edit
                                            </a>
                                            <form action="{{ route('admin.kedi-kits.destroy', $kit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this kit?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fe fe-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No KEDI Kits found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $kits->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
