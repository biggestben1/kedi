import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../config/api_config.dart';
import '../../config/app_theme.dart';
import '../../models/category_model.dart';
import '../../models/product_model.dart';
import '../../providers/cart_provider.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../widgets/product_image.dart';
import '../auth/login_screen.dart';
import '../cart/cart_screen.dart';

class HomeScreen extends StatefulWidget {
  final VoidCallback? onCartTap;

  const HomeScreen({super.key, this.onCartTap});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final ApiService _apiService = ApiService();
  List<Product> _products = [];
  List<Category> _categories = [];
  String? _userName;
  bool _isLoading = true;
  String? _error;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      await Future.wait([_loadUser(), _loadCategories(), _loadProducts()]);
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

  Future<void> _loadUser() async {
    final user = await AuthService(_apiService).getCurrentUser();
    if (user != null && mounted) {
      setState(() => _userName = user.name);
    }
  }

  Future<void> _loadCategories() async {
    try {
      final r = await _apiService.get(ApiConfig.categoriesUrl);
      if (r.statusCode == 200 && r.data is Map) {
        final list = (r.data as Map)['data'];
        if (list is List) {
          setState(() {
            _categories = list
                .map((e) => e is Map ? Category.fromJson(Map<String, dynamic>.from(e as Map)) : null)
                .whereType<Category>()
                .toList();
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _loadProducts({String? search}) async {
    final queryParams = (search != null && search.trim().isNotEmpty)
        ? {'search': search.trim()}
        : null;
    final r = await _apiService.get(ApiConfig.productsUrl, queryParameters: queryParams);
    if (r.statusCode == 200 && r.data is Map) {
      final list = (r.data as Map)['data'];
      if (list is List) {
        setState(() {
          _products = list
              .map((e) => e is Map ? Product.fromJson(Map<String, dynamic>.from(e as Map)) : null)
              .whereType<Product>()
              .toList();
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(_error!, style: TextStyle(color: Colors.red[700]), textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        ElevatedButton(onPressed: _loadData, child: const Text('Retry')),
                      ],
                    ),
                  ),
                )
              : SafeArea(
                  child: RefreshIndicator(
                    onRefresh: _loadData,
                    child: SingleChildScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 16),
                          // Header: Hello, name + icons
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Hello, ${_userName ?? 'User'} 👋',
                                style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                              ),
                              Row(
                                children: [
                                  IconButton(icon: const Icon(Icons.notifications_outlined), onPressed: () {}),
                                  Consumer<CartProvider>(
                                    builder: (_, cart, __) => Stack(
                                      clipBehavior: Clip.none,
                                      children: [
                                        IconButton(
                                          icon: const Icon(Icons.shopping_cart_outlined),
                                          onPressed: () {
                                            if (widget.onCartTap != null) {
                                              widget.onCartTap!();
                                            } else {
                                              Navigator.push(context, MaterialPageRoute(builder: (_) => const CartScreen()));
                                            }
                                          },
                                        ),
                                        if (cart.count > 0)
                                          Positioned(
                                            right: 4,
                                            top: 4,
                                            child: Container(
                                              padding: const EdgeInsets.all(4),
                                              decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                                              child: Text('${cart.count}', style: const TextStyle(color: Colors.white, fontSize: 10)),
                                            ),
                                          ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          // Search bar
                          TextField(
                            controller: _searchController,
                            onSubmitted: (value) => _loadProducts(search: value),
                            onChanged: (value) {
                              if (value.isEmpty) _loadProducts();
                            },
                            decoration: InputDecoration(
                              hintText: 'Search products',
                              prefixIcon: const Icon(Icons.search),
                              suffixIcon: IconButton(
                                icon: const Icon(Icons.search),
                                onPressed: () => _loadProducts(search: _searchController.text),
                              ),
                              filled: true,
                              fillColor: Colors.white,
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                            ),
                          ),
                          const SizedBox(height: 20),
                          // Promo banner
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [AppColors.primary.withOpacity(0.15), AppColors.primaryLight.withOpacity(0.08)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text('OptimalConsult', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w600, color: AppColors.primary)),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Special offers on selected items',
                                        style: TextStyle(color: Colors.grey[800], fontSize: 14),
                                      ),
                                      const SizedBox(height: 12),
                                      ElevatedButton(
                                        onPressed: () {},
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: Colors.green.shade700,
                                          foregroundColor: Colors.white,
                                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                        ),
                                        child: const Text('Shop Now'),
                                      ),
                                    ],
                                  ),
                                ),
                                Icon(Icons.local_offer, size: 64, color: Colors.green.shade300),
                              ],
                            ),
                          ),
                          const SizedBox(height: 24),
                          // Categories
                          const Text('Categories', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 12),
                          SizedBox(
                            height: 100,
                            child: _categories.length == 0
                                ? Center(child: Text('No categories', style: TextStyle(color: Colors.grey[600])))
                                : ListView.builder(
                                    scrollDirection: Axis.horizontal,
                                    itemCount: _categories.length,
                                    itemBuilder: (_, i) {
                                      final c = _categories[i];
                                      return Padding(
                                        padding: const EdgeInsets.only(right: 12),
                                        child: GestureDetector(
                                          onTap: () {},
                                          child: Container(
                                            width: 80,
                                            padding: const EdgeInsets.all(12),
                                            decoration: BoxDecoration(
                                              color: Colors.white,
                                              borderRadius: BorderRadius.circular(12),
                                              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 2))],
                                            ),
                                            child: Column(
                                              mainAxisAlignment: MainAxisAlignment.center,
                                              children: [
                                                Icon(Icons.category, size: 32, color: AppColors.primary.withOpacity(0.7)),
                                                const SizedBox(height: 8),
                                                Text(c.name, style: const TextStyle(fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center),
                                              ],
                                            ),
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                          ),
                          const SizedBox(height: 24),
                          // Best Sellers
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Best Sellers', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                              IconButton(icon: const Icon(Icons.more_horiz), onPressed: () {}),
                            ],
                          ),
                          const SizedBox(height: 12),
                          SizedBox(
                            height: 300,
                            child: _products.length == 0
                                ? Center(child: Text('No products yet', style: TextStyle(color: Colors.grey[600])))
                                : ListView.builder(
                                    scrollDirection: Axis.horizontal,
                                    itemCount: _products.length,
                                    itemBuilder: (_, i) {
                                      final p = _products[i];
                                      return Container(
                                        width: 160,
                                        margin: const EdgeInsets.only(right: 16),
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(12),
                                          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 10, offset: const Offset(0, 2))],
                                        ),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            ClipRRect(
                                              borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                                              child: ProductImage(
                                                imageUrl: p.image,
                                                height: 120,
                                                borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                                              ),
                                            ),
                                            Padding(
                                              padding: const EdgeInsets.all(10),
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(p.displayName ?? p.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 14)),
                                                  const SizedBox(height: 4),
                                                  Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(p.price)}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                                                  const SizedBox(height: 2),
                                                  Text('Stock: ${p.stock}', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                                                  const SizedBox(height: 8),
                                                  SizedBox(
                                                    width: double.infinity,
                                                    child: ElevatedButton.icon(
                                                      onPressed: () {
                                                        context.read<CartProvider>().add(p);
                                                        ScaffoldMessenger.of(context).showSnackBar(
                                                          SnackBar(content: Text('Added ${p.displayName ?? p.name} to cart'), duration: const Duration(seconds: 1)),
                                                        );
                                                      },
                                                      icon: const Icon(Icons.add_shopping_cart, size: 18),
                                                      label: const Text('Add to Cart'),
                                                      style: ElevatedButton.styleFrom(
                                                        padding: const EdgeInsets.symmetric(vertical: 8),
                                                        backgroundColor: AppColors.primary,
                                                        foregroundColor: Colors.white,
                                                      ),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                      );
                                    },
                                  ),
                          ),
                          const SizedBox(height: 24),
                        ],
                      ),
                    ),
                  ),
                ),
    );
  }
}
