@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1 class="page-title">Trashed Products</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page">Trash</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Deleted Products</h3>
                <div class="card-options">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-primary btn-sm">Back to Products</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Deleted At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->item_code }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                    <td>{{ $product->formatted_price }}</td>
                                    <td>{{ $product->deleted_at->format('M d, Y H:i') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.products.restore', $product->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                        </form>
                                        <form action="{{ route('admin.products.force-delete', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Purge</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">Trash bin is empty.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($products->hasPages())
                <div class="card-footer">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
