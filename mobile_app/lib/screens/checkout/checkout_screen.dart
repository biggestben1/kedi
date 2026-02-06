import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/app_theme.dart';
import 'package:provider/provider.dart';
import '../../config/api_config.dart';
import '../../models/user_model.dart';
import '../../providers/cart_provider.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../auth/login_screen.dart';
import '../main/main_screen.dart';
import '../wallet/wallet_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final ApiService _apiService = ApiService();
  final _formKey = GlobalKey<FormState>();
  final _addressController = TextEditingController();
  final _cityController = TextEditingController();
  final _stateController = TextEditingController();
  final _postalController = TextEditingController();
  final _phoneController = TextEditingController();

  String _paymentMethod = 'pay_on_delivery';
  User? _user;
  double _walletBalance = 0;
  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  @override
  void dispose() {
    _addressController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _postalController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _loadUser() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final user = await AuthService(_apiService).getCurrentUser();
      if (user != null) {
        _walletBalance = user.walletBalance ?? 0;
        setState(() {
          _user = user;
          _phoneController.text = user.phone ?? '';
        });
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 && mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
        return;
      }
      setState(() => _error = e.message ?? e.toString());
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  List<Map<String, dynamic>> _buildOrderItems(CartProvider cart) {
    final items = <Map<String, dynamic>>[];
    for (final entry in cart.items.entries) {
      final item = entry.value;
      final code = item.product.itemCode;
      if (code != null && code.isNotEmpty && item.quantity > 0) {
        items.add({'item_code': code, 'quantity': item.quantity});
      }
    }
    return items;
  }

  Future<void> _placeOrder(CartProvider cart) async {
    if (!_formKey.currentState!.validate()) return;

    final items = _buildOrderItems(cart);
    if (items.isEmpty) {
      setState(() => _error = 'No valid items in cart. Products must have item codes.');
      return;
    }

    final subtotal = cart.subtotal;
    if (_paymentMethod == 'wallet' && _walletBalance < subtotal) {
      setState(() => _error = 'Insufficient wallet balance. Top up your wallet first.');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      final r = await _apiService.post(ApiConfig.ordersUrl, data: {
        'items': items,
        'payment_method': _paymentMethod,
        'shipping_address': _addressController.text.trim(),
        'shipping_city': _cityController.text.trim(),
        'shipping_state': _stateController.text.trim().isEmpty ? null : _stateController.text.trim(),
        'shipping_postal_code': _postalController.text.trim().isEmpty ? null : _postalController.text.trim(),
        'shipping_phone': _phoneController.text.trim(),
      });
      if (r.statusCode == 201 && mounted) {
        cart.clear();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Order placed successfully!')),
        );
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const MainScreen()),
          (route) => false,
        );
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 && mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
        return;
      }
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message']?.toString()
          : e.message ?? e.toString();
      if (mounted) setState(() => _error = msg ?? 'Order failed');
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Checkout'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.black87,
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, _) {
          if (cart.count == 0 && !_isSubmitting) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('Your cart is empty'),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Back to Cart'),
                  ),
                ],
              ),
            );
          }

          if (_isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final subtotal = cart.subtotal;
          final canPayWithWallet = _walletBalance >= subtotal;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Order summary
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Subtotal', style: TextStyle(fontSize: 16)),
                        Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(subtotal)}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  const Text('Shipping Address', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _addressController,
                    decoration: const InputDecoration(
                      labelText: 'Street address',
                      border: OutlineInputBorder(),
                    ),
                    maxLines: 2,
                    validator: (v) => (v?.trim().isEmpty ?? true) ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _cityController,
                          decoration: const InputDecoration(
                            labelText: 'City',
                            border: OutlineInputBorder(),
                          ),
                          validator: (v) => (v?.trim().isEmpty ?? true) ? 'Required' : null,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          controller: _stateController,
                          decoration: const InputDecoration(
                            labelText: 'State',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _postalController,
                    decoration: const InputDecoration(
                      labelText: 'Postal code (optional)',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phoneController,
                    decoration: const InputDecoration(
                      labelText: 'Contact phone',
                      border: OutlineInputBorder(),
                    ),
                    keyboardType: TextInputType.phone,
                    validator: (v) => (v?.trim().isEmpty ?? true) ? 'Required' : null,
                  ),
                  const SizedBox(height: 24),
                  const Text('Payment Method', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  RadioListTile<String>(
                    title: const Text('Pay on Delivery'),
                    subtitle: const Text('Pay when you receive your order'),
                    value: 'pay_on_delivery',
                    groupValue: _paymentMethod,
                    onChanged: (v) => setState(() => _paymentMethod = v!),
                  ),
                  RadioListTile<String>(
                    title: const Text('Wallet'),
                    subtitle: Text(
                      canPayWithWallet
                          ? 'Balance: ${ApiConfig.currencySymbol}${ApiConfig.formatPrice(_walletBalance)}'
                          : 'Insufficient balance (${ApiConfig.currencySymbol}${ApiConfig.formatPrice(_walletBalance)}). Top up to use.',
                    ),
                    value: 'wallet',
                    groupValue: _paymentMethod,
                    onChanged: canPayWithWallet
                        ? (v) => setState(() => _paymentMethod = v!)
                        : null,
                  ),
                  if (!canPayWithWallet)
                    Padding(
                      padding: const EdgeInsets.only(left: 16),
                      child: TextButton.icon(
                        onPressed: () => Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const WalletScreen()),
                        ),
                        icon: const Icon(Icons.add),
                        label: const Text('Top Up Wallet'),
                      ),
                    ),
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: _isSubmitting ? null : () => _placeOrder(cart),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                    ),
                    child: _isSubmitting
                        ? const SizedBox(
                            height: 24,
                            width: 24,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Place Order'),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
