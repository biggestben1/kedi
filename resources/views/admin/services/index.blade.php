@extends('layouts.admin')

@section('title', 'Manage Services')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Landing Page Services</h3>
                <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm">Add New Service</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom">
                        <thead>
                            <tr>
                                <th class="wd-5p">Order</th>
                                <th class="wd-10p">Icon</th>
                                <th class="wd-20p">Title</th>
                                <th class="wd-40p">Description</th>
                                <th class="wd-10p">Status</th>
                                <th class="wd-15p">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                            <tr>
                                <td>{{ $service->sort_order }}</td>
                                <td class="fs-24">{{ $service->icon }}</td>
                                <td>{{ $service->title }}</td>
                                <td>{{ Str::limit($service->description, 50) }}</td>
                                <td>
                                    @if($service->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-list">
                                        <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form action="{{ route('admin.services.toggle-active', $service) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-{{ $service->is_active ? 'warning' : 'success' }}">
                                                {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
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
