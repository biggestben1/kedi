@extends('layouts.admin')

@section('title', 'Expenditures')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Expenditures</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Expenditures</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filter Expenditures</h3>
                    <div class="card-options">
                        <a href="{{ route('admin.expenditures.create') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus me-2"></i>New Expenditure</a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.expenditures.index') }}" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-control form-select">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                    <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary btn-block mt-2">Clear</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Expenditure List</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($expenditures->count())
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap border-bottom" id="responsive-datatable">
                            <thead>
                                <tr>
                                    <th class="wd-15p border-bottom-0">Date</th>
                                    <th class="wd-20p border-bottom-0">Category</th>
                                    <th class="wd-40p border-bottom-0">Description</th>
                                    <th class="wd-15p border-bottom-0 text-end">Amount</th>
                                    <th class="wd-10p border-bottom-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expenditures as $exp)
                                <tr>
                                    <td>{{ $exp->date->format('d M Y') }}</td>
                                    <td><span class="badge bg-primary-transparent rounded-pill text-primary p-2 px-3">{{ $exp->category }}</span></td>
                                    <td>
                                        {{ $exp->description }}
                                        @if($exp->notes)
                                            <i class="fe fe-info text-muted ms-1" data-bs-toggle="tooltip" title="{{ $exp->notes }}"></i>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">₦{{ number_format($exp->amount, 2) }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.expenditures.edit', $exp) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit"><i class="fe fe-edit"></i></a>
                                            <form action="{{ route('admin.expenditures.destroy', $exp) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expenditure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Delete"><i class="fe fe-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total (Page):</td>
                                    <td class="text-end">₦{{ number_format($expenditures->sum('amount'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $expenditures->links() }}
                    </div>
                    @else
                        <div class="text-center p-5">
                            <i class="fe fe-file-text fs-50 text-muted mb-3 d-inline-block"></i>
                            <h4 class="text-muted">No expenditures found based on your filters.</h4>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
