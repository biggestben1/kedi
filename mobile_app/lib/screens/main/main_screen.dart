import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../config/app_theme.dart';
import '../home/home_screen.dart';
import '../categories/categories_screen.dart';
import '../cart/cart_screen.dart';
import '../wallet/wallet_screen.dart';
import '../profile/profile_screen.dart';
import '../../providers/cart_provider.dart';

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _currentIndex = 0;

  late final List<Widget> _screens;

  @override
  void initState() {
    super.initState();
    _screens = [
      HomeScreen(onCartTap: () => setState(() => _currentIndex = 2)),
      CategoriesScreen(onCartTap: () => setState(() => _currentIndex = 2)),
      const CartScreen(),
      const WalletScreen(),
      const ProfileScreen(),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, -2)),
          ],
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (i) => setState(() => _currentIndex = i),
          type: BottomNavigationBarType.fixed,
          selectedItemColor: AppColors.primary,
          unselectedItemColor: AppColors.textMuted,
          items: [
            const BottomNavigationBarItem(icon: Icon(Icons.home_outlined), activeIcon: Icon(Icons.home), label: 'Home'),
            const BottomNavigationBarItem(icon: Icon(Icons.category_outlined), activeIcon: Icon(Icons.category), label: 'Categories'),
            BottomNavigationBarItem(
              icon: Consumer<CartProvider>(
                builder: (_, cart, __) => Badge(
                  label: Text('${cart.count}'),
                  isLabelVisible: cart.count > 0,
                  child: const Icon(Icons.shopping_cart_outlined),
                ),
              ),
              activeIcon: Consumer<CartProvider>(
                builder: (_, cart, __) => Badge(
                  label: Text('${cart.count}'),
                  isLabelVisible: cart.count > 0,
                  child: const Icon(Icons.shopping_cart),
                ),
              ),
              label: 'Cart',
            ),
            const BottomNavigationBarItem(icon: Icon(Icons.account_balance_wallet_outlined), activeIcon: Icon(Icons.account_balance_wallet), label: 'Wallet'),
            const BottomNavigationBarItem(icon: Icon(Icons.person_outline), activeIcon: Icon(Icons.person), label: 'Profile'),
          ],
        ),
      ),
    );
  }
}
