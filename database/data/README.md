# KEDI Price List import

To **update products** from your KEDI price list:

1. Save your price list as a CSV file named `kedi_price_list.csv` in this folder (`database/data/`).

2. CSV columns (header row required). Either use these exact names or the alternatives in brackets:
   - **item_code** (or ITEM) – product code, e.g. A01, A02
   - **name** (or products / PRODUCTS) – product name
   - **pack_size** (or unit / UNIT) – e.g. Bottle, 30s, Packet
   - **bv** (or BV) – bonus value
   - **pv** (or PV) – point value
   - **member_price** (or Member Price (N)) – selling price in Naira
   - **retail_price** (or Retail Price (N)) – optional; stored as cost_price

3. Run:
   ```bash
   php artisan db:seed --class=KediPriceListSeeder
   ```
   Existing products are updated by `item_code`; new rows are created.

If `kedi_price_list.csv` is not present, the seeder uses the inline list in `KediPriceListSeeder.php` (you can edit that array instead).
