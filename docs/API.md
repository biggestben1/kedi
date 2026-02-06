# Mobile API (v1)

Base URL: `/api/v1`

All authenticated endpoints require the header:

```
Authorization: Bearer {token}
```

---

## Authentication

### POST `/login`

**Body (JSON):**

| Field    | Type   | Required | Description |
|----------|--------|----------|-------------|
| email    | string | yes      | User email  |
| password | string | yes      | User password |

**Response:** `token`, `token_type` (`Bearer`), `user` (id, name, email, phone, role, wallet_balance).

---

### POST `/register`

**Body (JSON):**

| Field    | Type   | Required | Description        |
|----------|--------|----------|--------------------|
| name     | string | yes      | Full name          |
| email    | string | yes      | Unique email       |
| password | string | yes      | Min 8 chars        |
| password_confirmation | string | yes | Must match password |
| phone    | string | no       | Phone number       |

**Response:** Same as login (token + user). Status 201.

---

### POST `/logout`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "message": "Logged out successfully." }`

---

### GET `/user`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "user": { id, name, email, phone, role, wallet_balance } }`

---

## Categories

### GET `/categories`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "data": [ { id, name, slug, sort_order }, ... ] }`

---

## Products

### GET `/products`

**Headers:** `Authorization: Bearer {token}`

**Query:**

| Param      | Type   | Description                    |
|------------|--------|--------------------------------|
| category_id | int   | Filter by category             |
| search     | string | Search name / item_code        |
| per_page   | int    | Pagination size (default 15)   |
| page       | int    | Page number                    |

**Response:** Paginated list. Each item: id, item_code, name, pack_size, display_name, category_id, category, price (for current user), bv, pv, stock, image (full URL).

---

### GET `/products/{id}`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "data": { ...product fields } }`. 404 if product inactive.

---

## Orders

### GET `/orders`

**Headers:** `Authorization: Bearer {token}`

**Query:** `per_page`, `page`.

**Response:** Paginated list. Each order: id, invoice_number, tracking_number, status, payment_method, subtotal, total_bv, total_pv, shipping_*, created_at, items (item_code, product_name, quantity, unit_price, line_total).

---

### GET `/orders/{id}`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "data": { ...order with items } }`. 404 if not owned by user.

---

### POST `/orders`

**Headers:** `Authorization: Bearer {token}`

**Body (JSON):**

| Field            | Type   | Required | Description              |
|------------------|--------|----------|--------------------------|
| items            | array  | yes      | At least one item        |
| items[].item_code| string | yes      | Product item_code        |
| items[].quantity | int    | yes      | Min 1                    |
| payment_method   | string | yes      | `wallet` or `pay_on_delivery` |
| shipping_address | string | yes      | Full address             |
| shipping_city    | string | yes      | City                     |
| shipping_state   | string | no       | State                    |
| shipping_postal_code | string | no  | Postal code              |
| shipping_phone   | string | yes      | Contact phone            |

**Response:** 201 + `{ "message": "Order placed successfully.", "data": { ...order } }`.

**Errors:** 422 if cart empty, insufficient stock, or insufficient wallet balance (when payment_method is wallet).

---

## Wallet

### GET `/wallet`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "balance": 1234.56 }`

---

### GET `/wallet/transactions`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "data": [ { id, type, amount, balance_after, reference, status, created_at }, ... ] }` (last 50).

---

### POST `/wallet/topup`

**Headers:** `Authorization: Bearer {token}`  
**Content-Type:** `multipart/form-data` (if sending proof) or `application/json`.

**Body:**

| Field     | Type   | Required | Description              |
|-----------|--------|----------|--------------------------|
| amount    | number | yes      | Min 1                    |
| reference | string | no       | Payment reference        |
| proof     | file   | no       | jpg, jpeg, png, pdf, max 5MB |

**Response:** 201 + `{ "message": "...", "data": { id, amount, status, created_at } }`. Top-up is pending until approved by admin.

---

## Invoices

### GET `/invoices`

**Headers:** `Authorization: Bearer {token}`

**Query:** `per_page`, `page`.

**Response:** Paginated list. Each invoice: id, invoice_number, customer_*, dates, subtotal, tax, discount, total, status, items.

---

### GET `/invoices/{id}`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "data": { ...invoice with items } }`. 404 if not owned by user.

---

### GET `/invoices/{id}/pdf`

**Headers:** `Authorization: Bearer {token}`

**Response:** PDF file download. 404 if not owned by user.

---

## Driver API (dispatch role)

These endpoints are restricted to users with role `dispatch`. Returns 403 if user is not a driver.

### GET `/driver/orders`

**Headers:** `Authorization: Bearer {token}`

**Query:** `per_page`, `page`, `status` (paid|packed|shipped|delivered|completed), `search` (invoice_number, tracking_number, customer name/email)

**Response:** Paginated list of orders available for dispatch (paid, packed, shipped, delivered, completed). Same structure as `/orders` items, plus `delivery_courier`.

---

### GET `/driver/orders/{id}`

**Headers:** `Authorization: Bearer {token}`

**Response:** `{ "data": { ...order, "customer_name", "customer_email", "customer_phone" } }`. 404 if order not available for dispatch.

---

### PATCH `/driver/orders/{id}/status`

**Headers:** `Authorization: Bearer {token}`

**Body (JSON):**

| Field  | Type   | Required | Description                           |
|--------|--------|----------|---------------------------------------|
| status | string | yes      | One of: `packed`, `shipped`, `delivered` |

**Response:** `{ "message": "...", "data": { ...updated order } }`

---

### PATCH `/driver/orders/{id}/tracking`

**Headers:** `Authorization: Bearer {token}`

**Body (JSON):**

| Field           | Type   | Required | Description          |
|-----------------|--------|----------|----------------------|
| tracking_number | string | no       | Tracking number      |
| delivery_courier| string | no       | Courier name         |

**Response:** `{ "message": "Tracking info updated.", "data": { ...updated order } }`

---

## Errors

- **401 Unauthorized:** Missing or invalid token.
- **404:** Resource not found or not owned by user.
- **422 Unprocessable Entity:** Validation or business rule failure (e.g. insufficient stock, empty cart). Body includes `message` and optionally validation `errors`.

Validation errors format: `{ "message": "...", "errors": { "field": ["error1"] } }`.
