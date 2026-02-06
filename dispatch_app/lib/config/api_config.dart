import 'package:intl/intl.dart';

class ApiConfig {
  static const String baseUrl = 'http://my-laravel-app.test/api/v1';

  static const String currencySymbol = '₦';
  static String formatPrice(num value) => NumberFormat('#,##0.00').format(value);

  static const bool skipSslVerification = false;
  static const String tokenKey = 'auth_token';

  static String get loginUrl => '$baseUrl/login';
  static String get logoutUrl => '$baseUrl/logout';
  static String get userUrl => '$baseUrl/user';

  /// Driver orders: list, detail, update status, update tracking
  static String get driverOrdersUrl => '$baseUrl/driver/orders';
  static String driverOrderUrl(int id) => '$baseUrl/driver/orders/$id';
  static String driverOrderStatusUrl(int id) => '$baseUrl/driver/orders/$id/status';
  static String driverOrderTrackingUrl(int id) => '$baseUrl/driver/orders/$id/tracking';
}
