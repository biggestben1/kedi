import { useMemo, useState } from 'react';
import { Shell, Icon, naira } from './Shell';

function blankItem() {
    return { item_name: '', quantity: 1, unit_price: '' };
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
    const [groupId, setGroupId] = useState(old.order_group_id || '');
    const [groupOptions, setGroupOptions] = useState(groups);
    const [centerId, setCenterId] = useState(old.collection_branch_id || '');
    const [notes, setNotes] = useState(old.notes || '');
    const [items, setItems] = useState(old.items?.length ? old.items : [blankItem()]);
    const [modalOpen, setModalOpen] = useState(false);
    const [groupName, setGroupName] = useState('');
    const [groupError, setGroupError] = useState('');

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
            unit_price: price !== undefined && items[index].unit_price === '' ? price : items[index].unit_price,
        });
    }

    async function searchKd() {
        if (!kd.trim()) {
            setKdNote('Enter a Kedi number first.');
            return;
        }
        setKdNote('Checking Kedi number...');
        const response = await fetch(urls.kdSearch, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': pos.csrf,
            },
            body: JSON.stringify({ kd_no: kd }),
        });
        const payload = await response.json();
        if (!response.ok) {
            setKdNote(payload.error || payload.message || 'Could not check this Kedi number.');
            return;
        }
        if (payload.found) {
            setKd(payload.kd_no || kd);
            if (payload.customer_name && !customerName.trim()) setCustomerName(payload.customer_name);
            setKdNote(payload.message || 'Existing Kedi number.');
        } else {
            setKdNote(payload.message || 'New Kedi number. The customer name will register it.');
        }
    }

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

    const errorList = Object.values(errors || {}).flat();

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
                    <div className="kedi-chip"><Icon name="verified_user" /> Terminal Verified <i className="kedi-dot" /></div>
                    <div className="kedi-chip"><Icon name="calendar_today" /> {new Date().toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}</div>
                </div>
            </div>

            {errorList.length > 0 ? (
                <div className="kedi-error">
                    {errorList.map((message) => <div key={message}>{message}</div>)}
                </div>
            ) : null}

            <div className="kedi-banner">
                <div className="mark"><Icon name="info" /></div>
                <div>
                    <b>COUNTER FULFILLMENT NOTICE</b>
                    <p style={{ margin: '4px 0 0' }}>Stock is not checked here. The invoice goes to the collection center you choose. Items leave that center’s stock when the customer collects.</p>
                </div>
            </div>

            <form method="POST" action={urls.store}>
                <input type="hidden" name="_token" value={pos.csrf} />
                <div className="kedi-grid">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                        <section className="kedi-card kedi-section">
                            <div className="kedi-section-head">
                                <h2><i className="kedi-bar" />Customer and collection</h2>
                                <span className="kedi-pill kd">Step 1 of 2</span>
                            </div>
                            <div className="kedi-form-grid">
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
                            </div>
                            <div style={{ marginTop: '1rem' }}>
                                <span className="kedi-label">Kedi number</span>
                                <div className="kedi-row">
                                    <span className="kedi-field" style={{ flex: 1 }}>
                                        <span className="material-symbols-outlined lead">badge</span>
                                        <input className="kedi-input iconed" name="kd_id" value={kd} onChange={(event) => setKd(event.target.value.toUpperCase())} maxLength="100" placeholder="KD-200" />
                                    </span>
                                    <button type="button" className="kedi-btn soft" onClick={searchKd}><Icon name="search" /> Search</button>
                                </div>
                                {kdNote ? <div className="kedi-ok"><Icon name="check_circle" /> {kdNote}</div> : null}
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
                                <p className="kedi-help">A Kedi number is required when this invoice is added to a group.</p>
                            </div>
                            <div style={{ marginTop: '1rem' }}>
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
                            </div>
                        </section>
                        <section className="kedi-card kedi-section">
                            <div className="kedi-section-head"><h2><i className="kedi-bar" style={{ background: 'var(--muted)' }} />Notes</h2></div>
                            <textarea className="kedi-area" name="notes" rows="3" maxLength="2000" value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Add optional instructions for the collection center" />
                            <p className="kedi-help">Shown with the invoice at the collection center.</p>
                        </section>
                    </div>

                    <section className="kedi-card kedi-section">
                        <div className="kedi-section-head">
                            <h2><i className="kedi-bar" />Items</h2>
                            <button type="button" className="kedi-btn mint" onClick={() => setItems((current) => [...current, blankItem()])}><Icon name="add" /> Add item</button>
                        </div>
                        <table className="kedi-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th style={{ textAlign: 'right' }}>Unit price (₦)</th>
                                    <th style={{ textAlign: 'right' }}>Line total</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item, index) => {
                                    const known = item.item_name.trim() === '' || priceByName[item.item_name.trim().toLowerCase()] !== undefined;
                                    const line = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
                                    return (
                                        <tr key={index}>
                                            <td>
                                                <input className="kedi-input" name={`items[${index}][item_name]`} list="kedi-products" value={item.item_name} placeholder="Type or pick a product" onChange={(event) => onName(index, event.target.value)} />
                                                {!known ? <div className="warn">Not in the product list — no stock will be removed for this item at collection.</div> : null}
                                            </td>
                                            <td><input className="kedi-input qty" type="number" min="1" name={`items[${index}][quantity]`} value={item.quantity} onChange={(event) => updateItem(index, { quantity: event.target.value })} /></td>
                                            <td><input className="kedi-input" type="number" min="0" step="0.01" name={`items[${index}][unit_price]`} value={item.unit_price} onChange={(event) => updateItem(index, { unit_price: event.target.value })} /></td>
                                            <td style={{ textAlign: 'right', fontWeight: 700 }}>{item.item_name.trim() ? naira(line) : '₦0'}</td>
                                            <td>
                                                <button type="button" className="kedi-btn ghost" title="Remove" disabled={items.length === 1} onClick={() => setItems((current) => current.filter((_, i) => i !== index))}>
                                                    <Icon name="delete" />
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
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
                            <button className="kedi-btn primary" type="submit">
                                <Icon name={groupId ? 'playlist_add' : 'send'} />
                                {groupId ? 'Add to group' : 'Save & send to collection center'}
                            </button>
                        </div>
                    </section>
                </div>
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
