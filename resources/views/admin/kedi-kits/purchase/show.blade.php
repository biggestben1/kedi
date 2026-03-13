@extends('layouts.admin')

@section('title', 'Kit Purchase Details')

@section('content')
<div class="page-header">
    <h1 class="page-title">Kit Purchase #{{ $purchase->id }}</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.kedi-kits.purchase.index') }}">Kit Purchases</a></li>
            <li class="breadcrumb-item active" aria-current="page">Purchase Details</li>
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
        <h3 class="card-title">Purchase Details</h3>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Purchase Information</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Purchase ID:</th>
                        <td>{{ $purchase->id }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            @if($purchase->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($purchase->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($purchase->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif($purchase->status === 'completed')
                                <span class="badge bg-info">Completed</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Buyer:</th>
                        <td>{{ $purchase->buyer->name }} ({{ $purchase->buyer->email }})</td>
                    </tr>
                    <tr>
                        <th>Seller:</th>
                        <td>{{ $purchase->seller->name }} ({{ $purchase->seller->email }})</td>
                    </tr>
                    <tr>
                        <th>Quantity:</th>
                        <td>{{ $purchase->quantity }}</td>
                    </tr>
                    <tr>
                        <th>Unit Price:</th>
                        <td>₦{{ number_format($purchase->unit_price, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total Price:</th>
                        <td><strong>₦{{ number_format($purchase->total_price, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <th>Purchase Date:</th>
                        <td>{{ $purchase->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @if($purchase->notes)
                    <tr>
                        <th>Notes:</th>
                        <td>{{ $purchase->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="mb-4">
            <h5>Kedi Kit Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th width="40%">KEDI Kit :</th>
                    <td>{{ $purchase->kit->id }}</td>
                </tr>
                <tr>
                    <th>Category:</th>
                    <td>
                        <span class="badge bg-{{ $purchase->kit->category === 'english' ? 'primary' : 'info' }}">
                            {{ $purchase->kit->category_label }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Price:</th>
                    <td>₦{{ number_format($purchase->kit->price, 2) }}</td>
                </tr>
                @if($purchase->kit->description)
                <tr>
                    <th>Description:</th>
                    <td>{{ $purchase->kit->description }}</td>
                </tr>
                @endif
            </table>
        </div>


        @php
            // Only show KD numbers that are assigned to THIS purchase's buyer
            // Count how many KD numbers are assigned to this buyer from this kit
            $kit = $purchase->kit;
            $assignedToThisBuyer = $kit->items()->where('purchased_by_user_id', $purchase->buyer_user_id)->count();
            $fulfilledCount = min($assignedToThisBuyer, $purchase->quantity);
        @endphp
        
        @if($purchase->kit->items->count() > 0)
        <div class="mb-4">
            <h5>Kedi Kit Registrations</h5>
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <div>
                    <strong>Purchase Quantity:</strong> {{ $purchase->quantity }} kit(s)
                </div>
                @if(auth()->id() === $purchase->buyer_user_id && $purchase->status !== 'completed')
                <form action="{{ route('admin.kedi-kits.purchase.sync-registrations', $purchase) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fe fe-refresh-cw me-1"></i>Sync Existing Registrations
                    </button>
                </form>
                @endif
            </div>
            <div class="alert alert-light border mb-4">
                <i class="fe fe-info me-2"></i> Only kits registered specifically for <strong>Purchase #{{ $purchase->id }}</strong> will be displayed below. 
                If you have already registered kits for this purchase, they should appear here.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Who Bought It</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $shownCount = 0;
                            // Items explicitly assigned to THIS specific purchase
                            $assignedItems = $purchase->kit->items->filter(function($item) use ($purchase) {
                                return $item->kedi_kit_purchase_id == $purchase->id;
                            })->take($purchase->quantity);
                            
                            // Get unassigned items from this kit (no purchase link yet)
                            $unassignedItems = $purchase->kit->items->where('kedi_kit_purchase_id', null)->values();
                            $unassignedIndex = 0;
                        @endphp
                        
                        @foreach($assignedItems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if($item->registration)
                                        <span class="badge bg-success">Registered</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $item->purchasedBy->name }} ({{ $item->purchasedBy->email }})
                                </td>
                                <td>
                                    @if($item->registration)
                                        <a href="{{ route('admin.kd.registration.show', $item->registration->id) }}" class="btn btn-sm btn-info">
                                            <i class="fe fe-eye me-1"></i>View
                                        </a>
                                    @else
                                        <a href="{{ route('admin.kd.registration.create', ['from_kit' => 1, 'kd_no' => $item->kd_no, 'purchase_id' => $purchase->id]) }}" class="btn btn-sm btn-primary">
                                            <i class="fe fe-user-plus me-1"></i>Register
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @php $shownCount++; @endphp
                        @endforeach
                        
                        @if($purchase->quantity > 0)
                            @for($j = 0; $j < $purchase->quantity; $j++)
                                @php
                                    $nextUnassigned = $unassignedItems->get($unassignedIndex++);
                                    $rowNumber = $shownCount + $j + 1;
                                @endphp
                                <tr>
                                    <td>{{ $rowNumber }}</td>
                                    <td>
                                        <span class="badge bg-warning">Unassigned</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">Waiting for registration</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.kd.registration.create', ['from_kit' => 1, 'purchase_id' => $purchase->id, 'kd_no' => $nextUnassigned ? $nextUnassigned->kd_no : '']) }}" class="btn btn-sm btn-primary">
                                            <i class="fe fe-user-plus me-1"></i>Register
                                        </a>
                                    </td>
                                </tr>
                            @endfor
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(auth()->id() === $purchase->seller_user_id && $purchase->status === 'pending')
        <div class="mt-4">
            <form action="{{ route('admin.kedi-kits.purchase.approve', $purchase) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fe fe-check me-2"></i>Approve Purchase
                </button>
            </form>
            <form action="{{ route('admin.kedi-kits.purchase.reject', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this purchase?');">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fe fe-x me-2"></i>Reject Purchase
                </button>
            </form>
        </div>
        @endif

        @if((auth()->id() === $purchase->buyer_user_id || auth()->id() === $purchase->seller_user_id) && $purchase->status !== 'rejected' && ($purchase->status === 'pending' || $purchase->status === 'approved' || $purchase->status === 'completed'))
        <div class="mt-4">
            <form action="{{ route('admin.kedi-kits.purchase.unassign-kd-numbers', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to unassign all KD Numbers? They will be marked as pending and made available again.');">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="fe fe-refresh-cw me-2"></i>Unassign All KD Numbers
                </button>
            </form>
        </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('admin.kedi-kits.purchase.index') }}" class="btn btn-secondary">
                <i class="fe fe-arrow-left me-2"></i>Back to Purchases
            </a>
        </div>
    </div>
</div>
@endsection
