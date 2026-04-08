@if($collections->isEmpty())
    <p class="text-muted p-4 mb-0">You have no DPBV records yet. DPBV data is uploaded by Headquarters and matched to your account using your KD NO. If you have placed orders with your KD number, your DPBV will appear here once it has been uploaded.</p>
@else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code (KD NO)</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>SC</th>
                    <th class="text-end">DPBV</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collections as $c)
                @php
                    $displayDpbv = (float) ($netByCode[$c->code] ?? $c->dpbv);
                    $isTransferredOut = $displayDpbv <= 0.0000001;
                    $strikeStyle = $isTransferredOut ? 'text-decoration: line-through; color: #6c757d;' : '';
                @endphp
                <tr data-dpbv-row data-search="{{ strtolower(trim(($c->code ?? '') . ' ' . ($c->name ?? '') . ' ' . ($c->sc ?? '') . ' ' . optional($c->record_date)->format('Y-m-d'))) }}">
                    <td><strong style="{{ $strikeStyle }}">{{ $c->code }}</strong></td>
                    <td style="{{ $strikeStyle }}">{{ $c->name }}</td>
                    <td style="{{ $strikeStyle }}">{{ $c->record_date->format('M d, Y') }}</td>
                    <td style="{{ $strikeStyle }}">{{ $c->sc }}</td>
                    <td class="text-end" style="{{ $strikeStyle }}">{{ number_format($displayDpbv, 2) }}</td>
                    <td class="text-end">
                        @if(!$isTransferredOut)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary js-transfer-btn"
                                data-id="{{ $c->id }}"
                                data-code="{{ $c->code }}"
                                data-name="{{ $c->name }}"
                                data-dpbv="{{ number_format($displayDpbv, 2, '.', '') }}"
                            >
                                Transfer
                            </button>
                        @else
                            <span class="badge bg-light text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!$collections->isEmpty() && $collections->hasPages())
    <div class="card-footer">{{ $collections->links() }}</div>
@endif
