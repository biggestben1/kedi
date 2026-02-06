import 'package:dio/dio.dart';

import '../config/api_config.dart';
import '../models/user_model.dart';
import 'api_service.dart';
import 'biometric_service.dart';

class AuthService {
  final ApiService _apiService;

  AuthService(this._apiService);

  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _apiService.postUrl(
        ApiConfig.loginUrl,
        data: {
          'email': email,
          'password': password,
        },
      );

      if (response.statusCode == 200) {
        final data = response.data is Map ? response.data as Map<String, dynamic> : null;
        if (data == null) {
          return {'success': false, 'message': 'Invalid response from server'};
        }
        final token = data['token']?.toString();
        final userData = data['user'];
        if (token == null || userData is! Map || (token.toString()).length == 0) {
          return {'success': false, 'message': 'Invalid response from server'};
        }
        
        await _apiService.saveToken(token);
        await BiometricService().saveTokenForBiometric(token);
        final user = User.fromJson(Map<String, dynamic>.from(userData as Map));
        
        return {
          'success': true,
          'token': token,
          'user': user,
        };
      } else {
        return {
          'success': false,
          'message': 'Login failed',
        };
      }
    } on DioException catch (e) {
      return {
        'success': false,
        'message': _parseDioError(e),
      };
    } catch (e) {
      return {
        'success': false,
        'message': e.toString(),
      };
    }
  }

  Future<Map<String, dynamic>> register(
    String name,
    String email,
    String password,
    String passwordConfirmation,
  ) async {
    try {
      final response = await _apiService.postUrl(
        ApiConfig.registerUrl,
        data: {
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = response.data is Map ? response.data as Map<String, dynamic> : null;
        if (data == null) {
          return {'success': false, 'message': 'Invalid response from server'};
        }
        final token = data['token']?.toString();
        final userData = data['user'];
        if (token == null || userData is! Map || (token.toString()).length == 0) {
          return {'success': false, 'message': 'Invalid response from server'};
        }
        
        await _apiService.saveToken(token);
        await BiometricService().saveTokenForBiometric(token);
        final user = User.fromJson(Map<String, dynamic>.from(userData as Map));
        
        return {
          'success': true,
          'token': token,
          'user': user,
        };
      } else {
        final errorData = response.data as Map<String, dynamic>?;
        final message = errorData?['message'] ?? 'Registration failed';
        return {
          'success': false,
          'message': message.toString(),
        };
      }
    } on DioException catch (e) {
      return {
        'success': false,
        'message': _parseDioError(e),
      };
    } catch (e) {
      return {
        'success': false,
        'message': e.toString(),
      };
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
    } catch (e) {
      // Even if logout fails on server, clear local token
    } finally {
      await _apiService.clearToken();
      await BiometricService().clearStoredToken();
    }
  }

  Future<User?> getCurrentUser() async {
    try {
      final response = await _apiService.get(ApiConfig.userUrl);
      if (response.statusCode == 200 && response.data is Map) {
        final data = response.data as Map<String, dynamic>;
        final userData = data['user'] ?? data;
        if (userData is Map) {
          return User.fromJson(Map<String, dynamic>.from(userData as Map));
        }
      }
      return null;
    } catch (e) {
      return null;
    }
  }
}
