# KEDI Pharmaceutical Wholesale & Resell System and Online Mall

**Digital Distribution, Wallet, Credit, Installment & Product Tracking Platform**  
Prepared for Strategic, Investor & Regulatory Review

---

## 1. Project Overview

The KEDI Pharmaceutical Wholesale & Resell System & Online Mall is a digital platform designed to manage pharmaceutical wholesale distribution, reseller operations, and controlled retail sales. The system enables bulk drug sales to approved resellers who can resell to end customers using flexible installment payments (pay-small-small). Products are released strictly upon full payment or approved credit clearance, ensuring compliance and financial control.

## 2. Business Model

- Pharmaceutical wholesale distribution
- Reseller-driven retail sales
- Installment-based payments (pay-small-small)
- Wallet-funded transactions
- Credit-enabled purchasing for trusted resellers

## 3. User Roles

| Role | Description |
|------|-------------|
| **Super Admin** | Full system control, approvals, compliance, audits |
| **Wholesale Staff** | Inventory management, order fulfillment, delivery confirmation |
| **Reseller** | Customer sales, installment setup, wallet & credit tracking |
| **Customer** | Browse products, purchase items, pay via wallet or installments, customer support, automate online refill, prescription management |
| **Accountant** | Financial reconciliation, wallet & credit reporting |

## 4. KEDI Online Mall

A centralized pharmaceutical shopping mall for customers, resellers, and authorized buyers featuring:

- Product browsing
- Role-based pricing
- Secure checkout
- Order tracking

## 5. Wallet & Credit System

- Reseller and customer wallets
- Automatic deductions upon delivery
- Approved credit limits for trusted resellers
- Credit aging, default detection
- Full transaction audit logs

## 6. Product Tracking & Traceability

- End-to-end tracking from warehouse to final customer
- Batch numbers, delivery status, reseller assignment, customer purchase records
- Real-time delivery tracking, recall tracing, stock movement logs
- Audit-ready traceability reports

## 7. Inventory & Compliance

- Real-time stock tracking
- Drug availability, batch and expiry management
- Recall handling
- Role-based access control
- Complete audit trails for regulatory and pharmaceutical compliance

## 8. Reports & Analytics

- Sales summaries
- Installment tracking
- Wallet and credit reports
- Reseller performance analytics
- Inventory movement, expiry alerts
- Financial summaries

## 9. System Benefits

- Improved drug accessibility
- Flexible payments
- Reduced financial risk
- Enhanced reseller accountability
- Accurate product traceability
- Inventory control
- Full regulatory compliance

---

## Implementation Notes (Laravel)

- **Project overview:** `docs/KEDI_PROJECT_OVERVIEW.md`
- **User roles:** Super Admin, Wholesale Staff, Reseller, Customer, Accountant (see `App\Models\Role`, `database/seeders/RoleSeeder.php`)
- **Role middleware:** `role:super_admin`, `role:reseller`, etc. (see `App\Http\Middleware\CheckRole`)
- **Run KEDI setup (roles + migrations):** Visit `http://my-laravel-app.test/run-kedi-setup.php` once, then delete that file.
- **Next phases:** Products & categories, Wallets & credit, Orders & installments, Product tracking (batches, expiry, recall), Reports & analytics.
