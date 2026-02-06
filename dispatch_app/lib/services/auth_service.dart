import 'package:dio/dio.dart';

import '../config/api_config.dart';
import '../models/user_model.dart';
import 'api_service.dart';

class AuthService {
  final ApiService _apiService;

  AuthService(this._apiService);

  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _apiService.postUrl(
        ApiConfig.loginUrl,
        data: {'email': email, 'password': password},
      );

      if (response.statusCode == 200) {
        final data = response.data is Map ? response.data as Map<String, dynamic> : null;
        if (data == null) return {'success': false, 'message': 'Invalid response from server'};

        final token = data['token']?.toString();
        final userData = data['user'];
        if (token == null || token.isEmpty || userData is! Map<String, dynamic>) {
          return {'success': false, 'message': 'Invalid response from server'};
        }

        await _apiService.saveToken(token);
        final user = User.fromJson(Map<String, dynamic>.from(userData as Map));

        if (!user.isDispatch) {
          await _apiService.clearToken();
          return {'success': false, 'message': 'Only drivers can use this app. Please use the correct login.'};
        }

        return {'success': true, 'token': token, 'user': user};
      }
      return {'success': false, 'message': 'Login failed'};
    } on DioException catch (e) {
      return {'success': false, 'message': _parseDioError(e)};
    } catch (e) {
      return {'success': false, 'message': e.toString()};
    }
  }

  String _parseDioError(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      final msg = data['message']?.toString();
      if (msg != null && msg.isNotEmpty) return msg;
      final errors = data['errors'];
      if (errors is Map) {
        for (final v in errors.values) {
          if (v is List && v.isNotEmpty) return v.first.toString();
        }
      }
    }
    return e.message ?? 'Request failed. Please try again.';
  }

  Future<void> logout() async {
    try {
      await _apiService.postUrl(ApiConfig.logoutUrl);
    } catch (_) {}
    await _apiService.clearToken();
  }

  Future<User?> getCurrentUser() async {
    try {
      final response = await _apiService.get(ApiConfig.userUrl);
      if (response.statusCode == 200 && response.data is Map) {
        final data = response.data as Map<String, dynamic>;
        final userData = data['user'] ?? data;
        if (userData is Map<String, dynamic>) {
          return User.fromJson(userData);
        }
      }
      return null;
    } catch (_) {
      return null;
    }
  }
}
