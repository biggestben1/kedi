@extends('layouts.admin')

@section('title', $branch->name)

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $branch->name }}</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('collection-centers.index') }}">Collection Center</a></li>
                <li class="breadcrumb-item active">{{ $branch->name }}</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Branch</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Name:</strong> {{ $branch->name }}</p>
                    <p class="mb-1"><strong>Phone:</strong> {{ $branch->phone ?: '—' }}</p>
                    <p class="mb-3"><strong>Email:</strong> {{ $branch->email }}</p>
                    @if($selected)
                        <div class="alert alert-success py-2">This is the collection center for the current checkout.</div>
                    @endif
                    <form method="POST" action="{{ route('collection-centers.select', $branch) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">Send this order here</button>
                    </form>
                    <a href="{{ route('collection-centers.index') }}" class="btn btn-outline-secondary w-100 mt-2">All branches</a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title mb-0">Account details</h3>
                    <div class="d-flex gap-2">
                        @if($canManage)
                            <a href="#add-account" class="btn btn-outline-primary">Add account</a>
                        @endif
                        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#account-payment">Payment</button>
                    </div>
                </div>
                <div class="collapse {{ session('collection_payment_proof') ? 'show' : '' }}" id="account-payment">
                    <div class="card-body border-bottom">
                        <p class="mb-2"><strong>Payment options at this branch</strong></p>
                        @forelse($banks as $bank)
                            <div>Bank: {{ $bank->name }} — {{ $bank->account_name }} — {{ $bank->account_number }}</div>
                        @empty
                            <div class="text-muted">No bank account saved yet.</div>
                        @endforelse
                        @foreach($posMachines as $pos)
                            <div>POS: {{ $pos->bank_name }} — {{ $pos->account_name }} — {{ $pos->account_number }}</div>
                        @endforeach
                        <div>Cash is also accepted at the branch.</div>
                        <form method="POST" action="{{ route('collection-centers.proof', $branch) }}" enctype="multipart/form-data" class="mt-3">
                            @csrf
                            <label class="form-label">Upload proof (optional)</label>
                            <input type="file" name="payment_proof" class="form-control" accept="image/*,.pdf">
                            @if(session('collection_payment_proof'))
                                <div class="small mt-2"><a href="{{ asset('storage/'.session('collection_payment_proof')) }}" target="_blank">Proof already saved</a></div>
                            @endif
                            <button class="btn btn-success mt-2">Save proof</button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Bank</th><th>Account name</th><th>Account number</th></tr></thead>
                        <tbody>
                            @forelse($banks as $bank)
                                <tr>
                                    <td>{{ $bank->name }}</td>
                                    <td>{{ $bank->account_name }}</td>
                                    <td>{{ $bank->account_number }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted p-3">No account saved for this branch yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">POS</h3></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Bank</th><th>Account name</th><th>Account number</th></tr></thead>
                        <tbody>
                            @forelse($posMachines as $pos)
                                <tr>
                                    <td>{{ $pos->bank_name }}</td>
                                    <td>{{ $pos->account_name }}</td>
                                    <td>{{ $pos->account_number }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted p-3">No POS saved for this branch yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($canManage)
                <div class="row">
                    <div class="col-md-6">
                        <div class="card" id="add-account">
                            <div class="card-header"><h3 class="card-title">Add account</h3></div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('collection-centers.account', $branch) }}" class="row g-2">
                                    @csrf
                                    <div class="col-12"><input name="name" class="form-control" placeholder="Bank name" required></div>
                                    <div class="col-12"><input name="account_name" class="form-control" placeholder="Account name" required></div>
                                    <div class="col-12"><input name="account_number" class="form-control" placeholder="Account number" required></div>
                                    <div class="col-12"><button class="btn btn-primary">Save account</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Add POS</h3></div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('collection-centers.pos', $branch) }}" class="row g-2">
                                    @csrf
                                    <div class="col-12"><input name="bank_name" class="form-control" placeholder="POS bank" required></div>
                                    <div class="col-12"><input name="account_name" class="form-control" placeholder="Account name" required></div>
                                    <div class="col-12"><input name="account_number" class="form-control" placeholder="Account number" required></div>
                                    <div class="col-12"><button class="btn btn-primary">Save POS</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
