import { Shell, Icon, naira } from './Shell';

export function InvoiceList({ data }) {
    const { pos, rows, stats, centers, filters, createUrl, indexUrl } = data;

    return (
        <Shell pos={{ ...pos, stats }}>
            <div className="kedi-head">
                <div>
                    <div className="kedi-crumb">
                        <span>Counter operations</span>
                        <Icon name="chevron_right" />
                        <span style={{ color: 'var(--primary)' }}>Dispatch ledger</span>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <h1 className="kedi-title">Invoices sent to collection centers</h1>
                        <span className="kedi-live">LIVE FEED</span>
                    </div>
                    <p className="kedi-sub">Manage, verify, and route counter-issued invoices to the collection center that will hand them to the customer.</p>
                </div>
                <div className="kedi-actions">
                    <button type="button" className="kedi-btn ghost" onClick={() => window.print()}>
                        <Icon name="print" /> Batch Print
                    </button>
                    <a className="kedi-btn primary" href={createUrl}>
                        <Icon name="add_circle" /> + New Invoice
                    </a>
                </div>
            </div>

            <div className="kedi-cards">
                <article className="kedi-card">
                    <span className="label">Total invoices</span>
                    <span className="num">{stats.total}</span>
                    <span className="hint">Dispatched from this account</span>
                    <div className="icon"><Icon name="receipt_long" /></div>
                </article>
                <article className="kedi-card">
                    <span className="label" style={{ color: 'var(--tertiary)' }}>Pending collection</span>
                    <span className="num" style={{ color: 'var(--tertiary-container)' }}>{stats.pending}</span>
                    <span className="hint">Awaiting customer arrival</span>
                    <div className="icon" style={{ background: 'var(--tertiary-fixed)', color: 'var(--tertiary-container)' }}><Icon name="hourglass_top" /></div>
                </article>
                <article className="kedi-card">
                    <span className="label" style={{ color: 'var(--primary)' }}>Collected today</span>
                    <span className="num" style={{ color: 'var(--primary)' }}>{stats.collectedToday}</span>
                    <span className="hint">Fulfilled and signed off</span>
                    <div className="icon" style={{ background: 'var(--primary-fixed)' }}><Icon name="check_circle" /></div>
                </article>
            </div>

            <form className="kedi-card kedi-filters" method="GET" action={indexUrl}>
                <label className="kedi-field">
                    <span className="material-symbols-outlined lead">search</span>
                    <input className="kedi-input iconed" name="q" defaultValue={filters.q} placeholder="Search by invoice, Kedi number, or customer" />
                </label>
                <label className="kedi-field">
                    <span className="material-symbols-outlined lead">storefront</span>
                    <select className="kedi-select iconed" name="center" defaultValue={filters.center || ''} onChange={(event) => event.target.form.submit()}>
                        <option value="">All collection centers</option>
                        {centers.map((center) => (
                            <option key={center.id} value={center.id}>{center.name}</option>
                        ))}
                    </select>
                </label>
                <label className="kedi-field">
                    <span className="material-symbols-outlined lead">filter_list</span>
                    <select className="kedi-select iconed" name="status" defaultValue={filters.status || ''} onChange={(event) => event.target.form.submit()}>
                        <option value="">All statuses</option>
                        <option value="in_group">In group</option>
                        <option value="waiting">Waiting for collection</option>
                        <option value="collected">Collected</option>
                    </select>
                </label>
            </form>

            <div className="kedi-card" style={{ padding: 0 }}>
                <div className="kedi-table-wrap">
                    <table className="kedi-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Kedi number</th>
                                <th>Group</th>
                                <th>Collection center</th>
                                <th style={{ textAlign: 'right' }}>Total</th>
                                <th style={{ textAlign: 'center' }}>Status</th>
                                <th>Created by</th>
                                <th style={{ textAlign: 'center' }}>View</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan="10" style={{ color: 'var(--muted)', padding: '2rem' }}>No invoices yet.</td></tr>
                            ) : rows.map((row) => (
                                <tr key={row.number}>
                                    <td className="kedi-inv"><Icon name="description" /> {row.number}</td>
                                    <td style={{ color: 'var(--muted)' }}>{row.date}</td>
                                    <td style={{ fontWeight: 700 }}>{row.customer}</td>
                                    <td>{row.kd ? <span className="kedi-pill kd">{row.kd}</span> : '—'}</td>
                                    <td>{row.group || '—'}</td>
                                    <td>{row.center ? <span style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}><Icon name="pin_drop" /> {row.center}</span> : '—'}</td>
                                    <td style={{ textAlign: 'right', fontWeight: 700 }}>{naira(row.total)}</td>
                                    <td style={{ textAlign: 'center' }}><span className={`kedi-pill ${row.status}`}><i />{row.statusLabel}</span></td>
                                    <td style={{ color: 'var(--muted)' }}><span style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}><Icon name="person_outline" /> {row.createdBy}</span></td>
                                    <td style={{ textAlign: 'center' }}><a className="kedi-view" href={row.url}><Icon name="visibility" /> View</a></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="kedi-pager">
                    <div>Showing <b>{data.pagination.from || 0}-{data.pagination.to || 0}</b> of <b>{data.pagination.total}</b> invoices</div>
                    <div style={{ display: 'flex', gap: '0.5rem' }}>
                        {data.pagination.prev ? <a className="kedi-btn ghost" href={data.pagination.prev}>Previous</a> : <button className="kedi-btn ghost" type="button" disabled>Previous</button>}
                        <span style={{ alignSelf: 'center', fontWeight: 700 }}>{data.pagination.current} / {data.pagination.last}</span>
                        {data.pagination.next ? <a className="kedi-btn ghost" href={data.pagination.next}>Next</a> : <button className="kedi-btn ghost" type="button" disabled>Next</button>}
                    </div>
                </div>
            </div>

            <div className="kedi-notes">
                <article className="kedi-card" style={{ display: 'flex', gap: '0.8rem' }}>
                    <div className="icon" style={{ position: 'static' }}><Icon name="local_shipping" /></div>
                    <div>
                        <b>Direct Collection Routing</b>
                        <p className="kedi-help">The invoice goes to the collection center you choose. That center removes stock when the customer collects.</p>
                    </div>
                </article>
                <article className="kedi-card" style={{ display: 'flex', gap: '0.8rem' }}>
                    <div className="icon" style={{ position: 'static', background: 'var(--secondary-container)', color: 'var(--muted)' }}><Icon name="verified_user" /></div>
                    <div>
                        <b>Kedi Number Verification</b>
                        <p className="kedi-help">Search an existing Kedi number, or register a new one with the customer name when this invoice is added to a group.</p>
                    </div>
                </article>
            </div>
        </Shell>
    );
}
