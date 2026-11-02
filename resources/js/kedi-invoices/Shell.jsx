import { useState } from 'react';

export function naira(value) {
    const amount = Number(value || 0);
    return '₦' + amount.toLocaleString(undefined, {
        minimumFractionDigits: amount % 1 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    });
}

export function Icon({ name }) {
    return <span className="material-symbols-outlined">{name}</span>;
}

export function Shell({ pos, children }) {
    const [toast, setToast] = useState(pos.flash || '');

    return (
        <>
            <aside className="kedi-sidebar">
                <div>
                    <div className="kedi-brand">
                        <img src={pos.logo} alt="KEDI" />
                        <div>
                            <strong>KEDI POS</strong>
                            <span>HEALTHCARE</span>
                        </div>
                    </div>
                    <div className="kedi-online">
                        <span><i className="kedi-dot" />COUNTER ONLINE</span>
                        <span style={{ color: 'var(--muted)', fontWeight: 600 }}>POS</span>
                    </div>
                    <nav className="kedi-nav">
                        {pos.nav.map((item) => (
                            <a key={item.href} href={item.href} className={item.active ? 'active' : ''}>
                                <Icon name={item.icon} />
                                <span>{item.label}</span>
                            </a>
                        ))}
                    </nav>
                </div>
                <div className="kedi-side-foot">
                    <form method="POST" action={pos.logoutUrl}>
                        <input type="hidden" name="_token" value={pos.csrf} />
                        <button type="submit" className="kedi-logout">
                            <Icon name="logout" /> Logout
                        </button>
                    </form>
                </div>
            </aside>
            <div className="kedi-main">
                <header className="kedi-top">
                    <div style={{ display: 'flex', alignItems: 'center' }}>
                        <div className="kedi-chip">
                            <Icon name="domain" /> Invoice desk
                            <i className="kedi-dot" />
                        </div>
                        {pos.stats ? (
                            <div className="kedi-stats">
                                <span>Daily sales: <b>{naira(pos.stats.todaySales)}</b></span>
                                <span>Orders: <b className="green">{pos.stats.todayCount}</b></span>
                            </div>
                        ) : null}
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                        <div className="kedi-cashier">
                            <Icon name="badge" />
                            <div>
                                <b>{pos.userName}</b>
                                <small>Signed in</small>
                            </div>
                        </div>
                        <div className="kedi-avatar">{pos.userInitial}</div>
                    </div>
                </header>
                <main className="kedi-page">{children}</main>
            </div>
            {toast ? (
                <div className="kedi-toast" role="status">
                    <Icon name={pos.flashType === 'error' ? 'error' : 'check_circle'} />
                    <span>{toast}</span>
                    <button type="button" className="kedi-btn ghost" onClick={() => setToast('')}>Close</button>
                </div>
            ) : null}
        </>
    );
}
