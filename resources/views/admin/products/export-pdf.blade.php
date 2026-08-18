<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Products Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 6px 0; }
        .meta { font-size: 10px; color: #444; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-weight: bold; }
        .num { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Products</h1>
    <div class="meta">
        Generated: {{ $generatedAt?->format('M d, Y H:i') }} |
        Total: {{ is_array($rows) ? count($rows) : 0 }}
        @if(!empty($q))
            | Search: "{{ $q }}"
        @endif
        @if(!empty($categoryId))
            | Category ID: {{ $categoryId }}
        @endif
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 70px;">Item Code</th>
            <th>Name</th>
            <th style="width: 90px;">Category</th>
            <th style="width: 70px;">Pack Size</th>
            <th class="num" style="width: 70px;">Cost</th>
            <th class="num" style="width: 70px;">Price</th>
            <th class="num" style="width: 40px;">Stock</th>
            <th class="num" style="width: 35px;">BV</th>
            <th class="num" style="width: 35px;">PV</th>
            <th style="width: 55px;">DPBV</th>
            <th style="width: 55px;">Status</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $r['item_code'] ?? '' }}</td>
                <td>{{ $r['name'] ?? '' }}</td>
                <td>{{ $r['category'] ?? '' }}</td>
                <td>{{ $r['pack_size'] ?? '' }}</td>
                <td class="num">{{ number_format((float) ($r['cost_price'] ?? 0), 2) }}</td>
                <td class="num">{{ number_format((float) ($r['selling_price'] ?? 0), 2) }}</td>
                <td class="num">{{ (int) ($r['stock'] ?? 0) }}</td>
                <td class="num">{{ number_format((float) ($r['bv'] ?? 0), 1) }}</td>
                <td class="num">{{ number_format((float) ($r['pv'] ?? 0), 1) }}</td>
                <td>{{ !empty($r['dpbv']) ? 'Allowed' : 'Not Allowed' }}</td>
                <td>{{ !empty($r['status']) ? 'Active' : 'Inactive' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="muted">No products found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>

