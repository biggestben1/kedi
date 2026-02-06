import 'package:intl/intl.dart';

class ApiConfig {
  // LOCAL - Laravel Valet/similar (http://my-laravel-app.test)
  static const String baseUrl = 'http://my-laravel-app.test/api/v1';
  static const String storageBaseUrl = 'http://my-laravel-app.test';

  /// Currency symbol for display (Naira)
  static const String currencySymbol = '₦';

  /// Format number as price: 2,000.00
  static String formatPrice(num value) => NumberFormat('#,##0.00').format(value);

  // Production (switch when ready):
  // static const String baseUrl = 'https://optimalconsult.org/api/v1';

  static const bool skipSslVerification = false;
  static const String tokenKey = 'auth_token';

  // Full URLs (avoid path concatenation issues)
  static String get loginUrl => '$baseUrl/login';
  static String get registerUrl => '$baseUrl/register';
  static String get logoutUrl => '$baseUrl/logout';
  static String get productsUrl => '$baseUrl/products';
  static String get categoriesUrl => '$baseUrl/categories';
  static String get userUrl => '$baseUrl/user';
  static String get walletUrl => '$baseUrl/wallet';
  static String get walletTransactionsUrl => '$baseUrl/wallet/transactions';
  static String get walletTopupUrl => '$baseUrl/wallet/topup';
  static String get ordersUrl => '$baseUrl/orders';

  // Relative paths (for ApiService methods that use baseUrl)
  static const String user = 'user';
  static const String products = 'products';
  static const String categories = 'categories';
  static const String orders = 'orders';
  static const String wallet = 'wallet';
  static const String walletTransactions = 'wallet/transactions';
  static const String walletTopup = 'wallet/topup';
  static const String invoices = 'invoices';
  static const String cart = 'cart';
}
