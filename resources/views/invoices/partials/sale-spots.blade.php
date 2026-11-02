@php
    $selectedKd = old('kd_id', $selectedKd ?? '');
    $selectedGroupId = (int) old('order_group_id', $selectedGroupId ?? 0);
    $groupSelectId = $selectedGroupId > 0 ? $selectedGroupId : (int) ($activeGroupId ?? 0);
    $selectedBranchId = (int) old('collection_branch_id', $selectedBranchId ?? 0);
    $selectedGroup = collect($openOrderGroups ?? [])->first(fn ($group) => (int) $group->id === $selectedGroupId);
    $selectedBranch = collect($collectionBranches ?? [])->first(fn ($branch) => (int) $branch->id === $selectedBranchId);
@endphp

@if(($part ?? '') === 'buttons')
    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#invoiceKdModal" id="invoice-kd-spot">
        <i class="fe fe-user me-1"></i><span id="invoice-kd-spot-label">{{ $selectedKd !== '' ? $selectedKd : 'Kedi number' }}</span>
    </button>
    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#invoiceGroupModal" id="invoice-group-spot">
        <i class="fe fe-layers me-1"></i><span id="invoice-group-spot-label">Add to group{{ $selectedGroup ? ': '.$selectedGroup->displayName() : '' }}</span>
    </button>
    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#invoiceCollectionModal" id="invoice-collection-spot">
        <i class="fe fe-map-pin me-1"></i><span id="invoice-collection-spot-label">{{ $selectedBranch ? $selectedBranch->name : 'Collection center' }}</span>
    </button>
@endif

@if(($part ?? '') === 'fields')
    <input type="hidden" name="kd_id" id="invoice_kd_id" value="{{ $selectedKd }}">
    <input type="hidden" name="order_group_id" id="invoice_order_group_id" value="{{ $selectedGroupId > 0 ? $selectedGroupId : '' }}">
    <input type="hidden" name="collection_branch_id" id="invoice_collection_branch_id" value="{{ $selectedBranchId > 0 ? $selectedBranchId : '' }}">
    @error('kd_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    @error('order_group_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    @error('collection_branch_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
@endif

@if(($part ?? '') === 'modals')
    <div class="modal fade" id="invoiceKdModal" tabindex="-1" aria-labelledby="invoiceKdModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceKdModalLabel">Kedi number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="invoice_kd_lookup">Kedi number</label>
                    <div class="input-group">
                        <input type="text" id="invoice_kd_lookup" class="form-control" value="{{ $selectedKd }}" placeholder="Enter Kedi number" autocomplete="off">
                        <button type="button" class="btn btn-outline-info" id="invoice_kd_search_btn">Search</button>
                    </div>
                    <div id="invoice_kd_feedback" class="small mt-2"></div>
                    <div class="mt-3" id="invoice_kd_name_wrap" style="display:none;">
                        <label class="form-label" for="invoice_kd_customer_name">Customer name</label>
                        <input type="text" id="invoice_kd_customer_name" class="form-control" placeholder="Name for a new Kedi number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="invoice_kd_clear">Clear</button>
                    <button type="button" class="btn btn-primary" id="invoice_kd_apply" data-bs-dismiss="modal">Use this number</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceGroupModal" tabindex="-1" aria-labelledby="invoiceGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceGroupModalLabel">Add to group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="invoice_group_lookup">Open group</label>
                    <select id="invoice_group_lookup" class="form-select">
                        <option value="">— Select a group —</option>
                        @foreach(($openOrderGroups ?? []) as $group)
                            <option value="{{ $group->id }}" @selected($groupSelectId === (int) $group->id)>{{ $group->displayName() }}</option>
                        @endforeach
                    </select>
                    <p class="text-muted small mt-2 mb-0">Choose the group, then use <strong>Add to group</strong>. The invoice is saved as a draft in that group. Enter a Kedi number first.</p>
                    @if(collect($openOrderGroups ?? [])->isEmpty())
                        <p class="small mt-2 mb-0"><a href="{{ route('order-groups.create') }}">Create an order group</a> to add this invoice to one.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="invoice_group_clear">Clear</button>
                    <button type="button" class="btn btn-outline-primary" id="invoice_group_apply" data-bs-dismiss="modal">Keep selection</button>
                    <button type="submit" form="invoice-form" class="btn btn-success" name="add_to_group" value="1" id="invoice_group_submit">Add to group</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceCollectionModal" tabindex="-1" aria-labelledby="invoiceCollectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceCollectionModalLabel">Collection center</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="invoice_collection_lookup">Collection center</label>
                    <select id="invoice_collection_lookup" class="form-select">
                        <option value="">— Select collection center —</option>
                        @foreach(($collectionBranches ?? []) as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranchId === (int) $branch->id)>
                                {{ $branch->name }}@if($branch->phone) — {{ $branch->phone }}@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-muted small mt-2 mb-0">The customer collects at this center. Stock is removed there when the order is collected.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="invoice_collection_clear">Clear</button>
                    <button type="button" class="btn btn-primary" id="invoice_collection_apply" data-bs-dismiss="modal">Use this center</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var kdHidden = document.getElementById('invoice_kd_id');
            var groupHidden = document.getElementById('invoice_order_group_id');
            var branchHidden = document.getElementById('invoice_collection_branch_id');
            var kdLookup = document.getElementById('invoice_kd_lookup');
            var groupLookup = document.getElementById('invoice_group_lookup');
            var branchLookup = document.getElementById('invoice_collection_lookup');
            var kdLabel = document.getElementById('invoice-kd-spot-label');
            var groupLabel = document.getElementById('invoice-group-spot-label');
            var branchLabel = document.getElementById('invoice-collection-spot-label');
            var feedback = document.getElementById('invoice_kd_feedback');
            var nameWrap = document.getElementById('invoice_kd_name_wrap');
            var nameInput = document.getElementById('invoice_kd_customer_name');
            var buyerName = document.querySelector('input[name="buyer_name"]');

            function applyKd() {
                var value = (kdLookup.value || '').trim().toUpperCase();
                kdLookup.value = value;
                kdHidden.value = value;
                kdLabel.textContent = value !== '' ? value : 'Kedi number';
                if (value !== '' && nameInput && nameInput.value.trim() !== '' && buyerName && buyerName.value.trim() === '') {
                    buyerName.value = nameInput.value.trim();
                }
            }

            function applyGroup() {
                groupHidden.value = groupLookup.value;
                var text = groupLookup.options[groupLookup.selectedIndex] ? groupLookup.options[groupLookup.selectedIndex].text : '';
                groupLabel.textContent = groupLookup.value ? 'Add to group: ' + text : 'Add to group';
            }

            function applyBranch() {
                branchHidden.value = branchLookup.value;
                var text = branchLookup.options[branchLookup.selectedIndex] ? branchLookup.options[branchLookup.selectedIndex].text : 'Collection center';
                branchLabel.textContent = branchLookup.value ? text.split(' — ')[0] : 'Collection center';
            }

            document.getElementById('invoice_kd_apply').addEventListener('click', applyKd);
            document.getElementById('invoice_group_apply').addEventListener('click', applyGroup);
            document.getElementById('invoice_collection_apply').addEventListener('click', applyBranch);
            document.getElementById('invoice_group_submit').addEventListener('click', function () {
                applyKd();
                applyGroup();
                applyBranch();
            });
            document.getElementById('invoice_kd_clear').addEventListener('click', function () {
                kdLookup.value = '';
                if (nameInput) nameInput.value = '';
                if (nameWrap) nameWrap.style.display = 'none';
                if (feedback) feedback.innerHTML = '';
                applyKd();
            });
            document.getElementById('invoice_group_clear').addEventListener('click', function () {
                groupLookup.value = '';
                applyGroup();
            });
            document.getElementById('invoice_collection_clear').addEventListener('click', function () {
                branchLookup.value = '';
                applyBranch();
            });

            document.getElementById('invoice_kd_search_btn').addEventListener('click', function () {
                var kdNo = (kdLookup.value || '').trim();
                if (!kdNo) {
                    feedback.innerHTML = '<div class="text-danger">Enter a Kedi number first.</div>';
                    return;
                }
                feedback.innerHTML = '<div class="text-muted">Checking Kedi number...</div>';
                fetch(@json(route('kd-info.search')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ kd_no: kdNo })
                }).then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                    .then(function (result) {
                        if (!result.ok) {
                            feedback.innerHTML = '<div class="text-danger">' + (result.data.error || result.data.message || 'Could not check this Kedi number.') + '</div>';
                            return;
                        }
                        if (result.data.found) {
                            kdLookup.value = result.data.kd_no || kdNo;
                            if (nameInput) nameInput.value = result.data.customer_name || '';
                            if (nameWrap) nameWrap.style.display = 'none';
                            feedback.innerHTML = '<div class="text-success">' + (result.data.message || 'Existing Kedi number found.') + '</div>';
                        } else {
                            if (nameWrap) nameWrap.style.display = '';
                            feedback.innerHTML = '<div class="text-warning">' + (result.data.message || 'New Kedi number. Enter the customer name.') + '</div>';
                        }
                    })
                    .catch(function () {
                        feedback.innerHTML = '<div class="text-danger">Could not check this Kedi number.</div>';
                    });
            });
        })();
    </script>
@endif
