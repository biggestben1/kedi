import { useEffect, useState } from 'react';

export function LandingNav({ links }) {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        document.body.classList.toggle('nav-open', open);
        return () => document.body.classList.remove('nav-open');
    }, [open]);

    return (
        <div className="nav-content">
            <a className="logo" href="/">Optimal Consult</a>
            <button
                type="button"
                className="nav-toggle"
                aria-label={open ? 'Close menu' : 'Open menu'}
                aria-expanded={open}
                onClick={() => setOpen((current) => !current)}
            >
                <span />
                <span />
                <span />
            </button>
            <div className={`nav-links${open ? ' is-open' : ''}`}>
                {links.map((link) => (
                    <a
                        key={link.href + link.label}
                        href={link.href}
                        className={link.mobileOnly ? 'nav-mobile-only' : undefined}
                        onClick={() => setOpen(false)}
                    >
                        {link.label}
                    </a>
                ))}
            </div>
        </div>
    );
}
