import { createRoot } from 'react-dom/client';
import { LandingNav } from './Nav';

const root = document.getElementById('kedi-landing-nav');
const links = window.__KEDI_LANDING_NAV__ || [];

if (root) {
    createRoot(root).render(<LandingNav links={links} />);
}
