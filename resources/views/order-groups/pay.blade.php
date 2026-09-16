<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pay group – {{ $group->displayName() }} – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
</head>
<body class="app sidebar-mini ltr">
<div class="page">
    <div class="page-main">
        <div class="main-content app-content mt-0">
            <div class="side-app">
                <div class="main-container container-fluid py-4">
                    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h1 class="page-title mb-1">Pay for all orders</h1>
                            <p class="text-muted mb-0">Group: <strong>{{ $group->displayName() }}</strong></p>
                        </div>
                        <a href="{{ route('order-groups.show', $group) }}" class="btn btn-outline-secondary">Back to group</a>
                    </div>

                    @foreach (['success', 'message', 'error'] as $flash)
                        @if(session($flash))
                            <div class="alert alert-{{ $flash === 'error' ? 'danger' : 'success' }} alert-dismissible fade show">
                                {{ session($flash) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                    @endforeach
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title mb-0">Unpaid orders</h3></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead>
                                            <tr>
                                                <th>Invoice</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($drafts as $order)
                                                <tr>
                                                    <td>{{ $order->invoice_number }}</td>
                                                    <td class="text-end">₦{{ number_format($order->subtotal, 0) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <th>Total to pay</th>
                                                <th class="text-end">₦{{ number_format($draftTotal, 0) }}</th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <p class="small text-muted mt-3 mb-0">
                                        Wallet: ₦{{ number_format($walletBalance, 2) }} ·
                                        DPBV: ₦{{ number_format($dpbvNairaEquivalent, 2) }} ·
                                        KD Credit: ₦{{ number_format($kdCreditBalance, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title mb-0">Choose payment</h3></div>
                                <div class="card-body">
                                    <div class="p-3 bg-primary text-white rounded mb-4 text-center">
                                        <div class="opacity-75">Pay once for the whole group</div>
                                        <h2 class="mb-0">₦{{ number_format($draftTotal, 0) }}</h2>
                                        <small>{{ $drafts->count() }} order(s)</small>
                                    </div>

                                    <form method="POST" action="{{ route('order-groups.pay', $group) }}" id="group-pay-form">
                                        @csrf
                                        <input type="hidden" name="kd_id" value="{{ $kdId }}">
                                        <input type="hidden" name="split_payment" id="split_payment" value="{{ old('split_payment') ? '1' : '0' }}">
                                        <input type="hidden" name="split_wallet_amount" id="split_wallet_amount" value="{{ old('split_wallet_amount', 0) }}">
                                        <input type="hidden" name="split_kd_credit_amount" id="split_kd_credit_amount" value="{{ old('split_kd_credit_amount', 0) }}">
                                        <input type="hidden" name="split_dpbv_amount" id="split_dpbv_amount" value="{{ old('split_dpbv_amount', 0) }}">
                                        <input type="hidden" name="split_cash_amount" id="split_cash_amount" value="{{ old('split_cash_amount', 0) }}">
                                        <input type="hidden" name="split_cheque_amount" id="split_cheque_amount" value="{{ old('split_cheque_amount', 0) }}">
                                        <input type="hidden" name="pos_amount_paid" id="pos_amount_paid" value="{{ old('pos_amount_paid', 0) }}">
                                        <input type="hidden" name="bank_amount_paid" id="bank_amount_paid" value="{{ old('bank_amount_paid', 0) }}">
                                        <input type="hidden" name="pos_machine_id" id="pos_machine_id" value="{{ old('pos_machine_id') }}">
                                        <input type="hidden" name="bank_account_id" id="bank_account_id" value="{{ old('bank_account_id') }}">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Payment method</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="pm_wallet" value="wallet" {{ old('payment_method', 'wallet') === 'wallet' ? 'checked' : '' }} {{ $canPayWithWallet ? '' : 'disabled' }}>
                                                <label class="form-check-label" for="pm_wallet">Wallet @if(!$canPayWithWallet)<span class="text-muted">(insufficient)</span>@endif</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="pm_kd" value="kd_credit" {{ old('payment_method') === 'kd_credit' ? 'checked' : '' }} {{ $canPayWithCredit ? '' : 'disabled' }}>
                                                <label class="form-check-label" for="pm_kd">KD Credit @if(!$canPayWithCredit)<span class="text-muted">(need KD + balance)</span>@endif</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="pm_dpbv" value="dpbv" {{ old('payment_method') === 'dpbv' ? 'checked' : '' }} {{ $canPayWithDpbv ? '' : 'disabled' }}>
                                                <label class="form-check-label" for="pm_dpbv">DPBV @if(!$canPayWithDpbv)<span class="text-muted">(insufficient)</span>@endif</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="pm_pod" value="pay_on_delivery" {{ old('payment_method') === 'pay_on_delivery' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="pm_pod">Pay on delivery</label>
                                            </div>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="split_toggle" {{ old('split_payment') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="split_toggle">Split payment</label>
                                        </div>

                                        <div id="split_box" class="border rounded p-3 mb-3" style="{{ old('split_payment') ? '' : 'display:none;' }}">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Wallet</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="split_wallet_amount" value="{{ old('split_wallet_amount') }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">KD Credit</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="split_kd_credit_amount" value="{{ old('split_kd_credit_amount') }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">DPBV</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="split_dpbv_amount" value="{{ old('split_dpbv_amount') }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Cash</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="split_cash_amount" value="{{ old('split_cash_amount') }}" placeholder="e.g. 1,000,000">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Cheque</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="split_cheque_amount" value="{{ old('split_cheque_amount') }}" placeholder="e.g. 1,000,000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">POS machine</label>
                                                    <select class="form-select" id="pos_machine_select">
                                                        <option value="">Select POS machine</option>
                                                        @foreach($posMachines as $m)
                                                            <option value="{{ $m->id }}"
                                                                data-bank="{{ $m->bank_name ?: 'POS' }}"
                                                                data-account-number="{{ $m->account_number }}"
                                                                data-account-name="{{ $m->account_name ?? '' }}"
                                                                {{ (string) old('pos_machine_id') === (string) $m->id ? 'selected' : '' }}>
                                                                {{ $m->bank_name ?: 'POS' }}{{ $m->account_number ? ' • '.$m->account_number : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text" id="pos_machine_note"></div>
                                                </div>
                                                <div class="col-md-6" id="pos_amount_wrap" style="{{ old('pos_machine_id') ? '' : 'display:none;' }}">
                                                    <label class="form-label small">POS amount</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="pos_amount_paid" id="pos_amount_display" value="{{ old('pos_amount_paid') }}" placeholder="e.g. 1,000,000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Bank account</label>
                                                    <select class="form-select" id="bank_account_select">
                                                        <option value="">Select bank account</option>
                                                        @foreach($banks as $b)
                                                            <option value="{{ $b->id }}"
                                                                data-bank="{{ $b->name }}"
                                                                data-account-number="{{ $b->account_number }}"
                                                                data-account-name="{{ $b->account_name ?? '' }}"
                                                                {{ (string) old('bank_account_id') === (string) $b->id ? 'selected' : '' }}>
                                                                {{ $b->name }}{{ $b->account_number ? ' • '.$b->account_number : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text" id="bank_account_note"></div>
                                                </div>
                                                <div class="col-md-6" id="bank_amount_wrap" style="{{ old('bank_account_id') ? '' : 'display:none;' }}">
                                                    <label class="form-label small">Bank amount</label>
                                                    <input type="text" inputmode="decimal" class="form-control money-field" data-target="bank_amount_paid" id="bank_amount_display" value="{{ old('bank_amount_paid') }}" placeholder="e.g. 1,000,000">
                                                </div>
                                            </div>
                                            <p class="small mt-2 mb-0">Entered: ₦<span id="split_sum">0.00</span> / Due: ₦{{ number_format($draftTotal, 2) }}</p>
                                        </div>

                                        <button type="submit" class="btn btn-success btn-lg w-100" id="group-pay-submit">
                                            <i class="fe fe-credit-card me-1"></i>Pay all ₦{{ number_format($draftTotal, 0) }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var due = {{ json_encode((float) $draftTotal) }};

    function parseMoney(str) {
        var cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
        var firstDot = cleaned.indexOf('.');
        if (firstDot !== -1) {
            cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
        }
        var num = Number(cleaned);
        return Number.isFinite(num) ? num : 0;
    }

    function fmt(num) {
        return (parseFloat(num || 0) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function syncHidden(el) {
        var target = document.getElementById(el.dataset.target);
        if (!target) return;
        var raw = (el.value || '').trim();
        target.value = raw === '' ? '0' : parseMoney(raw).toFixed(2);
    }

    function updateSum() {
        var sum = 0;
        document.querySelectorAll('.money-field').forEach(function (f) {
            sum += parseMoney(f.value);
        });
        var el = document.getElementById('split_sum');
        if (el) el.textContent = fmt(sum);
        return sum;
    }

    function accountNote(select, noteEl, fallback) {
        if (!select || !noteEl) return;
        var opt = select.options[select.selectedIndex];
        if (!select.value) {
            noteEl.textContent = '';
            return;
        }
        var parts = [opt.getAttribute('data-bank') || fallback];
        if (opt.getAttribute('data-account-number')) parts.push(opt.getAttribute('data-account-number'));
        if (opt.getAttribute('data-account-name')) parts.push(opt.getAttribute('data-account-name'));
        noteEl.textContent = parts.join(' • ');
    }

    function wireMoney(el) {
        if (!el) return;
        if ((el.value || '').trim() !== '' && parseMoney(el.value) > 0) {
            el.value = fmt(parseMoney(el.value));
        } else {
            el.value = '';
        }
        syncHidden(el);

        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/[^0-9.,]/g, '');
            syncHidden(el);
            updateSum();
        });
        el.addEventListener('blur', function () {
            if ((el.value || '').trim() === '') {
                syncHidden(el);
                updateSum();
                return;
            }
            el.value = fmt(parseMoney(el.value));
            syncHidden(el);
            updateSum();
        });
    }

    document.getElementById('split_toggle')?.addEventListener('change', function () {
        document.getElementById('split_box').style.display = this.checked ? '' : 'none';
        document.getElementById('split_payment').value = this.checked ? '1' : '0';
    });

    document.querySelectorAll('.money-field').forEach(wireMoney);

    var posSelect = document.getElementById('pos_machine_select');
    var bankSelect = document.getElementById('bank_account_select');
    var posWrap = document.getElementById('pos_amount_wrap');
    var bankWrap = document.getElementById('bank_amount_wrap');
    var posDisplay = document.getElementById('pos_amount_display');
    var bankDisplay = document.getElementById('bank_amount_display');

    function syncPosMachine() {
        document.getElementById('pos_machine_id').value = posSelect.value || '';
        accountNote(posSelect, document.getElementById('pos_machine_note'), 'POS');
        var on = !!posSelect.value;
        if (posWrap) posWrap.style.display = on ? '' : 'none';
        if (!on && posDisplay) {
            posDisplay.value = '';
            syncHidden(posDisplay);
            updateSum();
        }
    }

    function syncBankAccount() {
        document.getElementById('bank_account_id').value = bankSelect.value || '';
        accountNote(bankSelect, document.getElementById('bank_account_note'), 'Bank');
        var on = !!bankSelect.value;
        if (bankWrap) bankWrap.style.display = on ? '' : 'none';
        if (!on && bankDisplay) {
            bankDisplay.value = '';
            syncHidden(bankDisplay);
            updateSum();
        }
    }

    posSelect?.addEventListener('change', syncPosMachine);
    bankSelect?.addEventListener('change', syncBankAccount);
    syncPosMachine();
    syncBankAccount();
    updateSum();

    document.getElementById('group-pay-form')?.addEventListener('submit', function (e) {
        document.querySelectorAll('.money-field').forEach(syncHidden);

        var splitOn = document.getElementById('split_payment')?.value === '1';
        if (splitOn) {
            var sum = updateSum();
            var rem = due - sum;
            if (Math.abs(rem) > 0.009) {
                e.preventDefault();
                alert('Payment amounts must add up to the group total. Remaining: ₦' + fmt(rem));
                return;
            }
        }

        if (!confirm('Pay ₦' + fmt(due) + ' for all orders in this group?')) {
            e.preventDefault();
        }
    });
})();
</script>
<script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
</body>
</html>
