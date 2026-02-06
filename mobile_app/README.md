# Mobile App (Flutter)

Flutter app that uses the Laravel API (`/api/v1`) for products, orders, wallet, and invoices.

**Production API:** `https://optimalconsult.org/api/v1`

## Prerequisites

- [Flutter SDK](https://flutter.dev/docs/get-started/install) (3.5+)
- Laravel backend running at https://optimalconsult.org

## Setup

1. **Generate platform files** (if `android/` and `ios/` are missing):

   ```bash
   cd mobile_app
   flutter create . --project-name mobile_app
   ```

2. **Install dependencies:**

   ```bash
   flutter pub get
   ```

3. **API base URL**

   The app is configured to use the production API at `https://optimalconsult.org/api/v1`.
   
   To change it (e.g., for local development), edit `lib/config/api_config.dart`:
   
   - Production: `https://optimalconsult.org/api/v1` (current)
   - Local development: `http://10.0.2.2:8000/api/v1` (Android emulator)
   - Local development: `http://127.0.0.1:8000/api/v1` (iOS simulator)

## Run

```bash
flutter run
```

## Features

- **Auth:** Login, register, logout
- **Products:** List by category, search, product detail, add to cart
- **Cart & checkout:** Cart screen, shipping form, place order (wallet or pay on delivery)
- **Orders:** List orders, order detail
- **Wallet:** Balance, transactions, top-up request
- **Invoices:** List invoices, invoice detail, PDF download
- **Profile:** User info, link to invoices, sign out

## Project structure

- `lib/config/api_config.dart` – API base URL and token key
- `lib/services/` – API client (Dio), auth, repository
- `lib/models/` – User, product, order, wallet, invoice models
- `lib/providers/` – Auth and cart state (Provider)
- `lib/screens/` – All UI screens

## Notes

- The app uses Laravel Sanctum for API authentication (Bearer tokens)
- Make sure your Laravel server has CORS configured if needed (Sanctum handles mobile apps via tokens)
- For production, ensure your SSL certificate is valid and the API endpoints are accessible
