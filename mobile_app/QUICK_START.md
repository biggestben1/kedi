# Quick Start - Run on Your Phone

## Option 1: Android Phone (Easiest)

### Prerequisites
1. Install Flutter: https://flutter.dev/docs/get-started/install
2. Enable Developer Options and USB Debugging on your Android phone
3. Connect phone via USB

### Steps

```bash
# 1. Navigate to mobile app directory
cd mobile_app

# 2. Generate platform files (first time only)
flutter create . --project-name mobile_app

# 3. Install dependencies
flutter pub get

# 4. Check connected devices
flutter devices

# 5. Run on your phone
flutter run
```

The app will install and launch on your phone automatically!

---

## Option 2: iOS Phone (iPhone)

### Prerequisites
1. Install Flutter: https://flutter.dev/docs/get-started/install
2. Install Xcode from Mac App Store
3. Connect iPhone via USB
4. Trust the computer on your iPhone

### Steps

```bash
# 1. Navigate to mobile app directory
cd mobile_app

# 2. Generate platform files (first time only)
flutter create . --project-name mobile_app

# 3. Install dependencies
flutter pub get

# 4. Open Xcode and sign in with Apple ID
# 5. Check connected devices
flutter devices

# 6. Run on your phone
flutter run
```

---

## Option 3: Build APK (Android) - Share with Others

```bash
cd mobile_app
flutter build apk --release
```

APK will be at: `build/app/outputs/flutter-apk/app-release.apk`

Send this file to install on any Android phone!

---

## Option 4: Test in Browser (Quick Preview)

```bash
cd mobile_app
flutter run -d chrome
```

Opens in Chrome browser for quick testing.

---

## Troubleshooting

### "No devices found"
- **Android**: Enable USB debugging, install drivers
- **iOS**: Trust computer, check Xcode setup

### "Flutter not found"
- Install Flutter: https://flutter.dev/docs/get-started/install
- Add to PATH: `export PATH="$PATH:/path/to/flutter/bin"`

### Build errors
```bash
flutter clean
flutter pub get
flutter run
```

---

## What You'll See

1. **Login Screen** - Register or login
2. **Products Tab** - Browse products, search, filter by category
3. **Orders Tab** - View your order history
4. **Wallet Tab** - Check balance, view transactions, top up
5. **Profile Tab** - User info, invoices, logout

The app connects to: **https://optimalconsult.org/api/v1**
