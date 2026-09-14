@extends('layouts.admin')

@section('title', 'Collection Center')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Collection Center</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('shop') }}">Shop</a></li>
                <li class="breadcrumb-item active">Branches</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">All branches</h3>
            <a href="{{ route('checkout.show') }}" class="btn btn-sm btn-outline-primary">Back to checkout</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>
                                    <strong>{{ $branch->name }}</strong>
                                    @if((int) $selectedId === (int) $branch->id)
                                        <span class="badge bg-success ms-1">Selected</span>
                                    @endif
                                </td>
                                <td>{{ $branch->phone ?: '—' }}</td>
                                <td>{{ $branch->email }}</td>
                                <td class="text-end">
                                    <a href="{{ route('collection-centers.show', $branch) }}" class="btn btn-sm btn-primary">View account & POS</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted p-4">No branches yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
