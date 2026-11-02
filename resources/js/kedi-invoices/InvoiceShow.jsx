import { Shell, Icon, naira } from './Shell';

export function InvoiceShow({ data }) {
    const { pos, invoice, urls } = data;

    return (
        <Shell pos={pos}>
            <div className="kedi-head">
                <div>
                    <div className="kedi-crumb">
                        <a href={urls.index}>Invoices</a>
                        <Icon name="chevron_right" />
                        <span>{invoice.number}</span>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                        <h1 className="kedi-title">{invoice.number}</h1>
                        <span className={`kedi-pill ${invoice.status}`}>{invoice.statusLabel}</span>
                    </div>
                    <p className="kedi-sub">Created {invoice.createdAt} by {invoice.createdBy}</p>
                </div>
            </div>

            <section className="kedi-card">
                <div className="kedi-form-grid">
                    <div><div className="kedi-help">Customer</div><b>{invoice.customer}</b>{invoice.phone ? <div>{invoice.phone}</div> : null}</div>
                    <div><div className="kedi-help">Kedi number</div><b>{invoice.kd || '—'}</b></div>
                    <div><div className="kedi-help">Group</div><b>{invoice.groupUrl ? <a href={invoice.groupUrl}>{invoice.group}</a> : (invoice.group || '—')}</b></div>
                    <div><div className="kedi-help">Collection center</div><b>{invoice.center || '—'}</b>{invoice.centerPhone ? <div>{invoice.centerPhone}</div> : null}</div>
                </div>
                <table className="kedi-table" style={{ marginTop: '1rem' }}>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style={{ textAlign: 'right' }}>Qty</th>
                            <th style={{ textAlign: 'right' }}>Unit price</th>
                            <th style={{ textAlign: 'right' }}>Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {invoice.items.map((item, index) => (
                            <tr key={index}>
                                <td>
                                    {item.name}
                                    {!item.stocked ? <div className="warn">Not in the product list — no stock removed at collection.</div> : null}
                                </td>
                                <td style={{ textAlign: 'right' }}>{item.quantity}</td>
                                <td style={{ textAlign: 'right' }}>{naira(item.unitPrice)}</td>
                                <td style={{ textAlign: 'right' }}>{naira(item.lineTotal)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                <div className="kedi-summary">
                    <div className="total"><span>Total</span><b>{naira(invoice.total)}</b></div>
                </div>
                {invoice.notes ? <p style={{ marginTop: '1rem' }}><span className="kedi-help">Notes</span><br />{invoice.notes}</p> : null}
                <div className="kedi-foot-actions">
                    <a className="kedi-btn ghost" href={urls.index}>Back to invoices</a>
                    <button type="button" className="kedi-btn soft" onClick={() => window.print()}><Icon name="print" /> Print</button>
                    {invoice.canDelete ? (
                        <form method="POST" action={urls.destroy} onSubmit={(event) => {
                            if (!window.confirm('Delete this invoice and remove it from the collection center?')) event.preventDefault();
                        }}>
                            <input type="hidden" name="_token" value={pos.csrf} />
                            <input type="hidden" name="_method" value="DELETE" />
                            <button type="submit" className="kedi-btn ghost" style={{ color: 'var(--error)' }}><Icon name="delete" /> Delete</button>
                        </form>
                    ) : null}
                </div>
            </section>
        </Shell>
    );
}
