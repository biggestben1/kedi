import { useEffect, useMemo, useRef, useState } from 'react';
import { Shell, Icon, naira } from './Shell';

function blankItem() {
    return { item_name: '', quantity: 1, unit_price: '', custom: false };
}

export function InvoiceCreate({ data }) {
    const { pos, old, errors, products, branches, groups, urls } = data;
    const priceByName = useMemo(() => {
        const map = {};
        products.forEach((product) => {
            [product.name, ...(product.aliases || [])].forEach((label) => {
                if (label) map[String(label).trim().toLowerCase()] = product.price;
            });
        });
        return map;
    }, [products]);

    const [customerName, setCustomerName] = useState(old.customer_name || '');
    const [phone, setPhone] = useState(old.customer_phone || '');
    const [kd, setKd] = useState(old.kd_id || '');
    const [kdNote, setKdNote] = useState('');
    const [kdOk, setKdOk] = useState(null);
    const [kdBusy, setKdBusy] = useState(false);
    const [groupId, setGroupId] = useState(old.order_group_id || '');
    const [groupOptions, setGroupOptions] = useState(groups);
    const [centerId, setCenterId] = useState(old.collection_branch_id || '');
    const [notes, setNotes] = useState(old.notes || '');
    const [items, setItems] = useState(() => {
        if (!old.items?.length) return [blankItem()];
        return old.items.map((item) => ({
            ...blankItem(),
            ...item,
            custom: Boolean(item.item_name) && !products.some((product) => product.name === item.item_name),
        }));
    });
    const [modalOpen, setModalOpen] = useState(false);
    const [groupName, setGroupName] = useState('');
    const [groupError, setGroupError] = useState('');
    const [formErrors, setFormErrors] = useState(Object.values(errors || {}).flat());
    const [notice, setNotice] = useState(null);
    const [saving, setSaving] = useState(false);
    const kdTimer = useRef(null);
    const lastKdChecked = useRef('');

    const center = branches.find((branch) => String(branch.id) === String(centerId));
    const namedCount = items.filter((item) => item.item_name.trim() !== '').length;
    const units = items.reduce((sum, item) => sum + (item.item_name.trim() ? Number(item.quantity) || 0 : 0), 0);
    const total = items.reduce((sum, item) => {
        if (!item.item_name.trim()) return sum;
        return sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    }, 0);

    function updateItem(index, patch) {
        setItems((current) => current.map((item, i) => (i === index ? { ...item, ...patch } : item)));
    }

    function onName(index, value) {
        const price = priceByName[value.trim().toLowerCase()];
        updateItem(index, {
            item_name: value,
            unit_price: price !== undefined ? price : items[index].unit_price,
        });
    }

    async function searchKd(force = false) {
        const value = kd.trim().toUpperCase();
        if (!value) {
            setKdNote('');
            setKdOk(null);
            return;
        }
        if (!force && lastKdChecked.current === value) return;
        setKdBusy(true);
        setKdNote('Checking Kedi number...');
        setKdOk(null);
        try {
            const response = await fetch(urls.kdSearch, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': pos.csrf,
                },
                body: JSON.stringify({ kd_no: value }),
            });
            const payload = await response.json();
            lastKdChecked.current = value;
            if (!response.ok) {
                setKdOk(false);
                setKdNote(payload.error || payload.message || 'Could not check this Kedi number.');
                return;
            }
            if (payload.found) {
                setKd(payload.kd_no || value);
                if (payload.customer_name) setCustomerName(payload.customer_name);
                setKdOk(true);
                setKdNote(payload.customer_name
                    ? `Registered: ${payload.customer_name}`
                    : (payload.message || 'Existing Kedi number.'));
            } else {
                setKdOk(false);
                setKdNote(payload.message || 'Not registered yet. Enter the customer name to register it.');
            }
        } catch {
            setKdOk(false);
            setKdNote('Could not check this Kedi number.');
        } finally {
            setKdBusy(false);
        }
    }

    useEffect(() => {
        if (kdTimer.current) clearTimeout(kdTimer.current);
        if (!kd.trim()) {
            setKdNote('');
            setKdOk(null);
            return undefined;
        }
        kdTimer.current = setTimeout(() => {
            searchKd(false);
        }, 500);
        return () => clearTimeout(kdTimer.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [kd]);

    async function createGroup(event) {
        event.preventDefault();
        setGroupError('');
        const response = await fetch(urls.groupStore, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': pos.csrf,
            },
            body: JSON.stringify({ name: groupName }),
        });
        const payload = await response.json();
        if (!response.ok) {
            setGroupError(payload.errors?.name?.[0] || payload.message || 'Could not create the group.');
            return;
        }
        setGroupOptions((current) => [{ id: payload.id, name: payload.name }, ...current]);
        setGroupId(String(payload.id));
        setGroupName('');
        setModalOpen(false);
    }

    function resetForm(keepGroup = true) {
        setCustomerName('');
        setPhone('');
        setKd('');
        setKdNote('');
        setKdOk(null);
        lastKdChecked.current = '';
        if (!keepGroup) setGroupId('');
        setCenterId('');
        setNotes('');
        setItems([blankItem()]);
        setFormErrors([]);
    }

    async function onSubmit(event) {
        if (!groupId) return;

        event.preventDefault();
        setFormErrors([]);
        setNotice(null);
        setSaving(true);
        try {
            const body = new FormData(event.target);
            const response = await fetch(urls.store, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': pos.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const messages = payload.errors
                    ? Object.values(payload.errors).flat()
                    : [payload.message || 'Could not save this invoice to the group.'];
                setFormErrors(messages);
                return;
            }
            setNotice({
                message: payload.message || `Invoice ${payload.invoice_number} saved in the group.`,
                groupUrl: payload.group_url || urls.groupsIndex,
                groupName: payload.group_name || 'Order group',
            });
            resetForm(true);
        } catch {
            setFormErrors(['Could not save this invoice to the group.']);
        } finally {
            setSaving(false);
        }
    }

    return (
        <Shell pos={pos}>
            <div className="kedi-head">
                <div>
                    <div className="kedi-crumb">
                        <a href={urls.index}>Invoices</a>
                        <Icon name="chevron_right" />
                        <span>New Invoice</span>
                    </div>
                    <h1 className="kedi-title">New Invoice</h1>
                </div>
                <div className="kedi-actions">
                    <a className="kedi-btn soft" href={urls.groupsIndex}><Icon name="group_work" /> Order groups</a>
                    <div className="kedi-chip"><Icon name="verified_user" /> Terminal Verified <i className="kedi-dot" /></div>
                </div>
            </div>

            {formErrors.length > 0 ? (
                <div className="kedi-error">
                    {formErrors.map((message) => <div key={message}>{message}</div>)}
                </div>
            ) : null}

            {notice ? (
                <div className="kedi-banner">
                    <div className="mark"><Icon name="check_circle" /></div>
                    <div style={{ flex: 1 }}>
                        <b>SAVED IN GROUP</b>
                        <p style={{ margin: '4px 0 0' }}>{notice.message}</p>
                        <p style={{ margin: '0.55rem 0 0' }}>
                            <a className="kedi-view" href={notice.groupUrl}>
                                <Icon name="open_in_new" /> Open {notice.groupName}
                            </a>
                        </p>
                    </div>
                    <button type="button" className="kedi-btn ghost" onClick={() => setNotice(null)}>Close</button>
                </div>
            ) : null}

            <div className="kedi-banner">
                <div className="mark"><Icon name="info" /></div>
                <div>
                    <b>COUNTER FULFILLMENT NOTICE</b>
                    <p style={{ margin: '4px 0 0' }}>Stock is not checked here. The invoice goes to the collection center you choose. Items leave that center’s stock when the customer collects.</p>
                </div>
            </div>

            <form method="POST" action={urls.store} onSubmit={onSubmit} className="kedi-create-stack">
                <input type="hidden" name="_token" value={pos.csrf} />

                <section className="kedi-card kedi-section">
                    <div className="kedi-section-head">
                        <h2><i className="kedi-bar" />Customer and collection</h2>
                        <span className="kedi-pill kd">Step 1</span>
                    </div>
                    <div className="kedi-form-grid">
                        <label>
                            <span className="kedi-label">Kedi number</span>
                            <div className="kedi-row">
                                <span className="kedi-field" style={{ flex: 1 }}>
                                    <span className="material-symbols-outlined lead">badge</span>
                                    <input
                                        className="kedi-input iconed"
                                        name="kd_id"
                                        value={kd}
                                        onChange={(event) => setKd(event.target.value.toUpperCase())}
                                        onBlur={() => searchKd(true)}
                                        maxLength="100"
                                        placeholder="KD-200"
                                        autoComplete="off"
                                    />
                                </span>
                                <button type="button" className="kedi-btn soft" onClick={() => searchKd(true)} disabled={kdBusy}>
                                    <Icon name="search" /> {kdBusy ? 'Checking...' : 'Search'}
                                </button>
                            </div>
                            {kdNote ? (
                                <div className={kdOk === false ? 'kedi-help' : 'kedi-ok'} style={{ marginTop: '0.45rem' }}>
                                    <Icon name={kdOk ? 'check_circle' : (kdOk === false ? 'info' : 'progress_activity')} />
                                    <span>{kdNote}</span>
                                </div>
                            ) : (
                                <p className="kedi-help">Type a Kedi number. If it is registered, the customer name fills in.</p>
                            )}
                        </label>
                        <label>
                            <span className="kedi-label">Customer name <span className="kedi-req">*</span></span>
                            <span className="kedi-field">
                                <span className="material-symbols-outlined lead">person</span>
                                <input className="kedi-input iconed" name="customer_name" value={customerName} onChange={(event) => setCustomerName(event.target.value)} required maxLength="255" placeholder="Ada Buyer" />
                            </span>
                        </label>
                        <label>
                            <span className="kedi-label">Customer phone</span>
                            <span className="kedi-field">
                                <span className="material-symbols-outlined lead">call</span>
                                <input className="kedi-input iconed" name="customer_phone" value={phone} onChange={(event) => setPhone(event.target.value)} maxLength="50" placeholder="08012345678" />
                            </span>
                        </label>
                        <label>
                            <span className="kedi-label">Collection center <span className="kedi-req">*</span></span>
                            <span className="kedi-field">
                                <span className="material-symbols-outlined lead">store</span>
                                <select className="kedi-select iconed" name="collection_branch_id" value={centerId} onChange={(event) => setCenterId(event.target.value)} required>
                                    <option value="">— Select collection center —</option>
                                    {branches.map((branch) => (
                                        <option key={branch.id} value={branch.id}>{branch.name}{branch.phone ? ` — ${branch.phone}` : ''}</option>
                                    ))}
                                </select>
                            </span>
                        </label>
                    </div>
                    <div style={{ marginTop: '1rem' }}>
                        <span className="kedi-label">Add to group</span>
                        <div className="kedi-row">
                            <span className="kedi-field" style={{ flex: 1 }}>
                                <span className="material-symbols-outlined lead">folder_special</span>
                                <select className="kedi-select iconed" name="order_group_id" value={groupId} onChange={(event) => setGroupId(event.target.value)}>
                                    <option value="">— Do not add to a group —</option>
                                    {groupOptions.map((group) => (
                                        <option key={group.id} value={group.id}>{group.name}</option>
                                    ))}
                                </select>
                            </span>
                            <button type="button" className="kedi-btn mint" onClick={() => setModalOpen(true)}><Icon name="add" /> New group</button>
                        </div>
                        <p className="kedi-help">Add to group keeps you on this page. Open Order Groups later to check out the drafts.</p>
                    </div>
                    <div style={{ marginTop: '1rem' }}>
                        <span className="kedi-label">Notes</span>
                        <textarea className="kedi-area" name="notes" rows="2" maxLength="2000" value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Optional instructions for the collection center" />
                    </div>
                </section>

                <section className="kedi-card kedi-section">
                    <div className="kedi-section-head">
                        <h2><i className="kedi-bar" />Items</h2>
                        <button type="button" className="kedi-btn mint" onClick={() => setItems((current) => [...current, blankItem()])}><Icon name="add" /> Add item</button>
                    </div>
                    <div className="kedi-item-rows">
                        <div className="kedi-item-head">
                            <span>Item name</span>
                            <span>Qty</span>
                            <span>Unit price (₦)</span>
                            <span>Line total</span>
                            <span />
                        </div>
                        {items.map((item, index) => {
                            const known = item.item_name.trim() === '' || priceByName[item.item_name.trim().toLowerCase()] !== undefined;
                            const line = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
                            const matched = products.some((product) => product.name === item.item_name);
                            const customMode = Boolean(item.custom) || (!matched && item.item_name.trim() !== '');
                            return (
                                <div className="kedi-item-row" key={index}>
                                    <div className="kedi-item-name">
                                        <select
                                            className="kedi-select"
                                            value={matched ? item.item_name : (customMode ? '__custom__' : '')}
                                            onChange={(event) => {
                                                const value = event.target.value;
                                                if (value === '__custom__') {
                                                    updateItem(index, { item_name: '', unit_price: '', custom: true });
                                                    return;
                                                }
                                                updateItem(index, {
                                                    item_name: value,
                                                    unit_price: priceByName[value.trim().toLowerCase()] ?? '',
                                                    custom: false,
                                                });
                                            }}
                                            aria-label={`Item ${index + 1} name`}
                                        >
                                            <option value="">— Pick a product —</option>
                                            {products.map((product) => (
                                                <option key={product.name} value={product.name}>{product.name}</option>
                                            ))}
                                            <option value="__custom__">Other / type a name</option>
                                        </select>
                                        {customMode ? (
                                            <input
                                                className="kedi-input"
                                                name={`items[${index}][item_name]`}
                                                list="kedi-products"
                                                value={item.item_name}
                                                placeholder="Type the item name"
                                                onChange={(event) => onName(index, event.target.value)}
                                            />
                                        ) : (
                                            <input type="hidden" name={`items[${index}][item_name]`} value={item.item_name} />
                                        )}
                                        {!known ? <div className="warn">Not in the product list — no stock will be removed for this item at collection.</div> : null}
                                    </div>
                                    <input className="kedi-input qty" type="number" min="1" name={`items[${index}][quantity]`} value={item.quantity} onChange={(event) => updateItem(index, { quantity: event.target.value })} />
                                    <input className="kedi-input" type="number" min="0" step="0.01" name={`items[${index}][unit_price]`} value={item.unit_price} onChange={(event) => updateItem(index, { unit_price: event.target.value })} />
                                    <div className="kedi-item-total">{item.item_name.trim() ? naira(line) : '₦0'}</div>
                                    <button type="button" className="kedi-btn ghost" title="Remove" disabled={items.length === 1} onClick={() => setItems((current) => current.filter((_, i) => i !== index))}>
                                        <Icon name="delete" />
                                    </button>
                                </div>
                            );
                        })}
                    </div>
                    <datalist id="kedi-products">
                        {products.map((product) => <option key={product.name} value={product.name} />)}
                    </datalist>
                    <div className="kedi-summary">
                        <div className="kedi-line"><span>Items count</span><span>{namedCount} item ({units} units)</span></div>
                        <div className="kedi-line"><span>Destination center</span><span>{center ? center.name : '—'}</span></div>
                        <div className="total"><span>Total</span><b>{naira(total)}</b></div>
                    </div>
                    <div className="kedi-foot-actions">
                        <a className="kedi-btn ghost" href={urls.index}>Cancel</a>
                        <button className="kedi-btn primary" type="submit" disabled={saving}>
                            <Icon name={groupId ? 'playlist_add' : 'send'} />
                            {saving ? 'Saving...' : (groupId ? 'Add to group' : 'Save & send to collection center')}
                        </button>
                    </div>
                </section>
            </form>

            {modalOpen ? (
                <div className="kedi-modal" role="dialog" aria-modal="true">
                    <form className="kedi-dialog" onSubmit={createGroup}>
                        <div className="kedi-section-head">
                            <h2>New invoice group</h2>
                            <button type="button" className="kedi-btn ghost" onClick={() => setModalOpen(false)}><Icon name="close" /></button>
                        </div>
                        <p className="kedi-help">Name the group, then add this invoice to it.</p>
                        <label>
                            <span className="kedi-label">Group name <span className="kedi-req">*</span></span>
                            <input className="kedi-input" value={groupName} onChange={(event) => setGroupName(event.target.value)} placeholder="e.g. Morning batch" required maxLength="255" />
                        </label>
                        {groupError ? <div className="kedi-error">{groupError}</div> : null}
                        <div className="kedi-foot-actions">
                            <button type="button" className="kedi-btn ghost" onClick={() => setModalOpen(false)}>Cancel</button>
                            <button type="submit" className="kedi-btn primary"><Icon name="add_circle" /> Create group</button>
                        </div>
                    </form>
                </div>
            ) : null}
        </Shell>
    );
}
