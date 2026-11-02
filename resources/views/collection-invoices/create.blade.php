@extends('layouts.kedi-pos')

@section('content')
    @php
        $selectedGroup = (int) old('order_group_id', request('order_group_id'));
        $payload = [
            'page' => 'create',
            'pos' => $pos,
            'old' => [
                'customer_name' => old('customer_name', ''),
                'customer_phone' => old('customer_phone', ''),
                'kd_id' => old('kd_id', ''),
                'order_group_id' => $selectedGroup > 0 ? (string) $selectedGroup : '',
                'collection_branch_id' => old('collection_branch_id', ''),
                'notes' => old('notes', ''),
                'items' => array_values(old('items', [])),
            ],
            'errors' => ($errors ?? null) instanceof \Illuminate\Support\ViewErrorBag ? $errors->toArray() : [],
            'products' => $productOptions,
            'branches' => $collectionBranches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'phone' => $branch->phone,
            ])->values(),
            'groups' => $openOrderGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->displayName(),
            ])->values(),
            'urls' => [
                'index' => route('collection-invoices.index'),
                'store' => route('collection-invoices.store'),
                'kdSearch' => route('kd-info.search'),
                'groupStore' => route('order-groups.store'),
                'groupsIndex' => route('order-groups.index'),
            ],
        ];
    @endphp
    <script>window.__KEDI_INVOICE__ = @json($payload);</script>
    <div id="kedi-invoice-root"></div>
@endsection
