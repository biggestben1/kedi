@extends('layouts.customer')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('order-groups.index') }}">Order Groups</a></li>
    <li class="breadcrumb-item"><a href="{{ route('order-groups.show', $group) }}">{{ $group->displayName() }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pay</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="page-title mb-1">Pay for all orders</h1>
        <p class="text-muted mb-0">Group: <strong>{{ $group->displayName() }}</strong></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.kd.registration.create') }}" class="btn btn-primary">
            <i class="fe fe-edit me-1"></i>Register KD NO
        </a>
        <a href="{{ route('admin.kedi-kits.purchase.create') }}" class="btn btn-outline-primary">
            <i class="fe fe-shopping-cart me-1"></i>Purchase KEDI Kit
        </a>
        <a href="{{ route('order-groups.show', $group) }}" class="btn btn-outline-secondary">Back to group</a>
    </div>
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
                            <th>Subtotal</th>
                            <th class="text-end">₦{{ number_format($draftTotal, 0) }}</th>
                        </tr>
                        @if(($discountAmount ?? 0) > 0)
                        <tr class="text-success">
                            <th>Coupon discount @if($coupon)({{ number_format($coupon->discount_percentage, 0) }}%)@endif</th>
                            <th class="text-end">-₦{{ number_format($discountAmount, 0) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th>Amount due</th>
                            <th class="text-end">₦{{ number_format($amountDue ?? $draftTotal, 0) }}</th>
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
                <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <strong><i class="fe fe-user me-1"></i> Register KEDI / buy a kit</strong>
                        <span class="d-block small mb-0">
                            Use the admin forms to register a KD NO or purchase a KEDI kit, then come back here to pay with that credit code.
                        </span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.kd.registration.create') }}" class="btn btn-primary">
                            <i class="fe fe-edit me-1"></i>Register KD NO
                        </a>
                        <a href="{{ route('admin.kedi-kits.purchase.create') }}" class="btn btn-outline-primary">
                            <i class="fe fe-shopping-cart me-1"></i>Purchase KEDI Kit
                        </a>
                        <a href="{{ route('admin.kd.registration.index') }}" class="btn btn-outline-secondary">
                            KD Registrations
                        </a>
                    </div>
                </div>

                <div class="p-3 bg-primary text-white rounded mb-4 text-center">
                    <div class="opacity-75">Pay once for the whole group</div>
                    <h2 class="mb-0">₦{{ number_format($amountDue ?? $draftTotal, 0) }}</h2>
                    <small>{{ $drafts->count() }} order(s){{ ($discountAmount ?? 0) > 0 ? ' · after coupon' : '' }}</small>
                </div>

                <form method="POST" action="{{ route('order-groups.pay', $group) }}" id="group-pay-form">
                    @csrf
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
                    <input type="hidden" name="coupon_code" id="coupon_code_hidden" value="{{ old('coupon_code', optional($coupon)->code ?? session('coupon_code')) }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Coupon code</label>
                        @if($coupon ?? null)
                            <div class="alert alert-success py-2 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <span>Applied: <strong>{{ $coupon->code }}</strong> ({{ number_format($coupon->discount_percentage, 0) }}% off · save ₦{{ number_format($discountAmount, 0) }})</span>
                                <button type="submit" class="btn btn-sm btn-outline-danger" formaction="{{ route('cart.remove-coupon') }}" formmethod="POST">Remove</button>
                            </div>
                        @else
                            <div class="input-group">
                                <input type="text" id="coupon_code_input" class="form-control @error('coupon_code') is-invalid @enderror" value="{{ old('coupon_code') }}" placeholder="Enter coupon code (optional)" autocomplete="off">
                                <button type="submit" class="btn btn-outline-primary" id="apply_coupon_btn" formaction="{{ route('cart.apply-coupon') }}" formmethod="POST">Apply</button>
                            </div>
                            <div id="coupon_feedback" class="small mt-1"></div>
                            @error('coupon_code')<div class="text-danger small">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                            <label class="form-label fw-semibold mb-0" for="kd_id_input">Credit code (KD NO)</label>
                            <a href="{{ route('admin.kd.registration.create') }}" class="small">
                                Register KD NO
                            </a>
                        </div>
                        <input type="text" name="kd_id" id="kd_id_input" class="form-control @error('kd_id') is-invalid @enderror" value="{{ old('kd_id', $kdId) }}" placeholder="Enter KD / credit code for KD Credit payment" autocomplete="off">
                        @error('kd_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text" id="kd_credit_balance_display" style="{{ ($kdId && $kdCreditBalance > 0) ? '' : 'display:none;' }}">
                            KD Credit balance: <strong>₦<span id="kd-credit-balance-text">{{ number_format($kdCreditBalance, 2) }}</span></strong>
                        </div>
                        @if(session('kd_id') && session('customer_name'))
                            <div class="small text-success mt-1">
                                Current session: <strong>{{ session('kd_id') }}</strong> — {{ session('customer_name') }}
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment method</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pm_wallet" value="wallet" {{ old('payment_method', 'wallet') === 'wallet' ? 'checked' : '' }} {{ $canPayWithWallet ? '' : 'disabled' }}>
                            <label class="form-check-label" for="pm_wallet">Wallet @if(!$canPayWithWallet)<span class="text-muted">(insufficient)</span>@endif</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pm_kd" value="kd_credit" {{ old('payment_method') === 'kd_credit' ? 'checked' : '' }} {{ $canPayWithCredit ? '' : 'disabled' }}>
                            <label class="form-check-label" for="pm_kd">KD Credit @if(!$canPayWithCredit)<span class="text-muted">(need credit code + balance)</span>@endif</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pm_dpbv" value="dpbv" {{ old('payment_method') === 'dpbv' ? 'checked' : '' }} {{ $canPayWithDpbv ? '' : 'disabled' }}>
                            <label class="form-check-label" for="pm_dpbv">DPBV @if(!$canPayWithDpbv)<span class="text-muted">(insufficient)</span>@endif</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pm_pod" value="pay_on_delivery" {{ old('payment_method') === 'pay_on_delivery' ? 'checked' : '' }}>
                            <label class="form-check-label" for="pm_pod">Pay on delivery / COD</label>
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
                        <p class="small mt-2 mb-0">Entered: ₦<span id="split_sum">0.00</span> / Due: ₦{{ number_format($amountDue ?? $draftTotal, 2) }}</p>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100" id="group-pay-submit">
                        <i class="fe fe-credit-card me-1"></i>Pay all ₦{{ number_format($amountDue ?? $draftTotal, 0) }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade" id="payKdRegisterModal" tabindex="-1" aria-labelledby="payKdRegisterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payKdRegisterModalLabel"><i class="fe fe-user me-2"></i>Register KEDI NO &amp; name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('kd-info.store') }}" id="payKdRegisterForm">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ route('order-groups.pay-form', $group) }}">
                <div class="modal-body">
                    <p class="text-muted mb-3">Enter the KEDI number. If it is new, add the customer name to register it. You will return here to pay.</p>
                    <div class="mb-3">
                        <label for="pay_reg_kd_id" class="form-label">KD NO</label>
                        <div class="input-group">
                            <input type="text" name="kd_id" id="pay_reg_kd_id" class="form-control" placeholder="Enter KD number" required autofocus>
                            <button type="button" class="btn btn-outline-info" id="payKdSearchBtn"><i class="fe fe-search me-1"></i>Search</button>
                        </div>
                        <div id="payKdSearchResult" class="mt-2 small"></div>
                    </div>
                    <div class="mb-3" id="payCustomerNameField">
                        <label for="pay_reg_customer_name" class="form-label">Customer name</label>
                        <input type="text" name="customer_name" id="pay_reg_customer_name" class="form-control" placeholder="Customer name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fe fe-check me-1"></i>Register</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    var due = {{ json_encode((float) ($amountDue ?? $draftTotal)) }};
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    var applyCouponBtn = document.getElementById('apply_coupon_btn');
    var couponInput = document.getElementById('coupon_code_input');
    var couponHidden = document.getElementById('coupon_code_hidden');
    if (applyCouponBtn && couponInput) {
        applyCouponBtn.addEventListener('click', function () {
            couponInput.setAttribute('name', 'code');
            if (couponHidden) couponHidden.disabled = true;
        });
    }

    var kdInput = document.getElementById('kd_id_input');
    var kdBalanceWrap = document.getElementById('kd_credit_balance_display');
    var kdBalanceText = document.getElementById('kd-credit-balance-text');
    var kdTimer = null;
    function checkKdCredit(kdNo) {
        if (!kdNo) {
            if (kdBalanceWrap) kdBalanceWrap.style.display = 'none';
            return;
        }
        fetch('{{ route('checkout.check-kd-credit') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ kd_no: kdNo })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!kdBalanceWrap || !kdBalanceText) return;
            if (data && data.has_credit) {
                kdBalanceText.textContent = Number(data.balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                kdBalanceWrap.style.display = '';
            } else {
                kdBalanceWrap.style.display = 'none';
            }
        })
        .catch(function () {});
    }
    if (kdInput) {
        kdInput.addEventListener('input', function () {
            clearTimeout(kdTimer);
            kdTimer = setTimeout(function () { checkKdCredit(kdInput.value.trim()); }, 400);
        });
        if (kdInput.value.trim()) checkKdCredit(kdInput.value.trim());
    }

    // Register modal: look up existing KD
    var payKdSearchBtn = document.getElementById('payKdSearchBtn');
    var payRegKd = document.getElementById('pay_reg_kd_id');
    var payRegName = document.getElementById('pay_reg_customer_name');
    var payKdSearchResult = document.getElementById('payKdSearchResult');
    if (payKdSearchBtn && payRegKd) {
        payKdSearchBtn.addEventListener('click', function () {
            var kdNo = (payRegKd.value || '').trim();
            if (!kdNo) {
                if (payKdSearchResult) payKdSearchResult.innerHTML = '<span class="text-danger">Enter a KD NO first.</span>';
                return;
            }
            if (payKdSearchResult) payKdSearchResult.innerHTML = '<span class="text-muted">Searching...</span>';
            fetch('{{ route('kd-info.search') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ kd_no: kdNo })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!payKdSearchResult) return;
                if (data && data.found) {
                    if (payRegName) payRegName.value = data.customer_name || '';
                    payKdSearchResult.innerHTML = '<span class="text-success">Found: <strong>' + (data.kd_no || kdNo) + '</strong> — ' + (data.customer_name || '') + '. Submit to use it.</span>';
                } else {
                    payKdSearchResult.innerHTML = '<span class="text-warning">New KD NO. Enter the customer name to register it.</span>';
                    if (payRegName) payRegName.focus();
                }
            })
            .catch(function () {
                if (payKdSearchResult) payKdSearchResult.innerHTML = '<span class="text-danger">Search failed. Try again.</span>';
            });
        });
    }

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
        var submitter = e.submitter;
        if (submitter && submitter.getAttribute('formaction')) {
            return;
        }

        document.querySelectorAll('.money-field').forEach(syncHidden);

        var splitOn = document.getElementById('split_payment')?.value === '1';
        if (splitOn) {
            var sum = updateSum();
            var rem = due - sum;
            if (Math.abs(rem) > 0.009) {
                e.preventDefault();
                alert('Payment amounts must add up to the amount due. Remaining: ₦' + fmt(rem));
                return;
            }
        }

        if (!confirm('Pay ₦' + fmt(due) + ' for all orders in this group?')) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
