import 'package:flutter/material.dart';

/// Dispash brand colors (driver app)
class AppColors {
  static const Color primary = Color(0xFF233AC5);
  static const Color primaryLight = Color(0xFF4B5FD4);
  static const Color accent = Color(0xFF13BFA6);
  static const Color background = Color(0xFFF6F6FB);
  static const Color textDark = Color(0xFF282F53);
  static const Color textMuted = Color(0xFF76839A);
}

class AppTheme {
  static ThemeData get theme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        primary: AppColors.primary,
        secondary: AppColors.accent,
        surface: Colors.white,
      ),
      scaffoldBackgroundColor: AppColors.background,
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: AppColors.textDark,
        titleTextStyle: TextStyle(color: AppColors.textDark, fontSize: 18, fontWeight: FontWeight.w600),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }
}
