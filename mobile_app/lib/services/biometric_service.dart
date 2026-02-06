import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:local_auth/local_auth.dart';

import '../config/api_config.dart';

class BiometricService {
  static const _tokenStorageKey = 'biometric_auth_token';
  final LocalAuthentication _localAuth = LocalAuthentication();
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  /// Check if biometric (fingerprint/face) is available on this device
  Future<bool> canCheckBiometrics() async {
    if (kIsWeb) return false;
    try {
      return await _localAuth.canCheckBiometrics;
    } catch (_) {
      return false;
    }
  }

  /// Check if device has biometrics enrolled (fingerprint or face)
  Future<bool> isBiometricAvailable() async {
    if (kIsWeb) return false;
    try {
      final canCheck = await _localAuth.canCheckBiometrics;
      if (!canCheck) return false;
      final list = await _localAuth.getAvailableBiometrics();
      return list.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  /// Get biometric type for UI (Fingerprint, Face ID, etc.)
  Future<String> getBiometricType() async {
    if (kIsWeb) return 'Biometric';
    try {
      final list = await _localAuth.getAvailableBiometrics();
      if (list.contains(BiometricType.face)) return 'Face ID';
      if (list.contains(BiometricType.fingerprint)) return 'Fingerprint';
      if (list.contains(BiometricType.iris)) return 'Iris';
      return 'Biometric';
    } catch (_) {
      return 'Biometric';
    }
  }

  /// Save token for biometric login (call after successful password login)
  Future<void> saveTokenForBiometric(String token) async {
    if (kIsWeb) return;
    try {
      await _secureStorage.write(key: _tokenStorageKey, value: token);
    } catch (_) {}
  }

  /// Check if we have a stored token for biometric login
  Future<bool> hasStoredToken() async {
    if (kIsWeb) return false;
    try {
      final token = await _secureStorage.read(key: _tokenStorageKey);
      return token != null && token.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  /// Authenticate with fingerprint/face and return stored token if successful
  Future<String?> authenticateAndGetToken() async {
    if (kIsWeb) return null;
    try {
      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Authenticate to login',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );
      if (!authenticated) return null;
      return await _secureStorage.read(key: _tokenStorageKey);
    } catch (_) {
      return null;
    }
  }

  /// Clear stored biometric token (e.g. on logout)
  Future<void> clearStoredToken() async {
    if (kIsWeb) return;
    try {
      await _secureStorage.delete(key: _tokenStorageKey);
    } catch (_) {}
  }
}
