@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1 class="page-title">Landing Page Sliders</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Landing Page Sliders</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Sliders</h3>
                <div class="card-options">
                    <a href="{{ route('admin.landing-sliders.create') }}" class="btn btn-primary"><i class="fe fe-plus me-2"></i>Add New Slider</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Sort Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sliders as $slider)
                            <tr>
                                <td>
                                    @if(str_starts_with($slider->image, 'images/'))
                                        <img src="{{ asset($slider->image) }}" alt="" style="height: 50px; border-radius: 5px;">
                                    @else
                                        <img src="{{ asset('storage/' . $slider->image) }}" alt="" style="height: 50px; border-radius: 5px;">
                                    @endif
                                </td>
                                <td>{{ $slider->title ?? 'N/A' }}</td>
                                <td>{{ $slider->sort_order }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-status" type="checkbox" data-id="{{ $slider->id }}" {{ $slider->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.landing-sliders.edit', $slider) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.landing-sliders.destroy', $slider) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this slider?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.toggle-status').on('change', function() {
        let id = $(this).data('id');
        $.ajax({
            url: `/admin/landing-sliders/${id}/toggle-active`,
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    toastr.success('Status updated successfully');
                }
            }
        });
    });
});
</script>
@endpush
