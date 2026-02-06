import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class ApiService {
  late Dio _dio;

  ApiService() {
    _dio = Dio(
      BaseOptions(
        baseUrl: ApiConfig.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // Bypass SSL verification for development (hostname mismatch, self-signed certs)
    if (ApiConfig.skipSslVerification) {
      _dio.httpClientAdapter = IOHttpClientAdapter(
        createHttpClient: () {
          final client = HttpClient();
          client.badCertificateCallback = (cert, host, port) => true;
          return client;
        },
      );
    }
    
    // Add interceptor to include auth token in requests
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) {
          // Handle 401 unauthorized - token expired
          if (error.response?.statusCode == 401) {
            _clearToken();
          }
          return handler.next(error);
        },
      ),
    );
  }
  
  // Get stored auth token
  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(ApiConfig.tokenKey);
  }
  
  // Save auth token
  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(ApiConfig.tokenKey, token);
  }
  
  // Clear auth token
  Future<void> _clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(ApiConfig.tokenKey);
  }
  
  // Clear token (public method for logout)
  Future<void> clearToken() async {
    await _clearToken();
  }
  
  // GET request
  Future<Response> get(String path, {Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.get(path, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }
  
  // POST request
  Future<Response> post(String path, {dynamic data, Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.post(path, data: data, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }

  // POST with full URL (bypasses baseUrl - use for auth endpoints)
  Future<Response> postUrl(String url, {dynamic data}) async {
    try {
      return await _dio.post(url, data: data);
    } catch (e) {
      rethrow;
    }
  }
  
  // PUT request
  Future<Response> put(String path, {dynamic data, Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.put(path, data: data, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }
  
  // DELETE request
  Future<Response> delete(String path, {Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.delete(path, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }
  
  // Test API connection
  Future<Map<String, dynamic>> testConnection() async {
    try {
      // Try to hit a simple endpoint (like categories or products)
      final response = await get(ApiConfig.categories);
      return {
        'success': response.statusCode == 200,
        'statusCode': response.statusCode,
        'message': 'Connected successfully',
      };
    } on DioException catch (e) {
      String errorMessage = 'Unknown error';
      if (e.type == DioExceptionType.connectionTimeout) {
        errorMessage = 'Connection timeout - server not responding';
      } else if (e.type == DioExceptionType.receiveTimeout) {
        errorMessage = 'Receive timeout - server too slow';
      } else if (e.type == DioExceptionType.connectionError) {
        errorMessage = 'Connection error - cannot reach server';
      } else if (e.response != null) {
        errorMessage = 'Server error: ${e.response?.statusCode} - ${e.response?.statusMessage}';
      } else {
        errorMessage = e.message ?? 'Network error';
      }
      return {
        'success': false,
        'statusCode': e.response?.statusCode,
        'message': errorMessage,
        'error': e.toString(),
      };
    } catch (e) {
      return {
        'success': false,
        'message': 'Unexpected error: ${e.toString()}',
        'error': e.toString(),
      };
    }
  }
}
