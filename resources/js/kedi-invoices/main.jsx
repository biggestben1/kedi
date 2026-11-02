import { createRoot } from 'react-dom/client';
import { InvoiceList } from './InvoiceList';
import { InvoiceCreate } from './InvoiceCreate';
import { InvoiceShow } from './InvoiceShow';

const pages = {
    list: InvoiceList,
    create: InvoiceCreate,
    show: InvoiceShow,
};

const data = window.__KEDI_INVOICE__;
const Page = pages[data?.page];
const root = document.getElementById('kedi-invoice-root');

if (Page && root) {
    createRoot(root).render(<Page data={data} />);
}
