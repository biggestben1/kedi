import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/app_theme.dart';
import 'package:provider/provider.dart';
import '../../config/api_config.dart';
import '../../models/product_model.dart';
import '../../providers/cart_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/product_image.dart';

class ProductsScreen extends StatefulWidget {
  final int categoryId;
  final String categoryName;

  const ProductsScreen({super.key, required this.categoryId, required this.categoryName});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  final ApiService _apiService = ApiService();
  List<Product> _products = [];
  bool _isLoading = true;
  String? _error;

  Future<void> _loadProducts() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final r = await _apiService.get(
        ApiConfig.productsUrl,
        queryParameters: {'category_id': widget.categoryId},
      );
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
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: Text(widget.categoryName),
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.black87,
      ),
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
                        ElevatedButton(onPressed: _loadProducts, child: const Text('Retry')),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadProducts,
                  child: _products.isEmpty
                      ? SingleChildScrollView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          child: SizedBox(
                            height: MediaQuery.of(context).size.height * 0.6,
                            child: Center(
                              child: Text('No products in this category', style: TextStyle(color: Colors.grey[600])),
                            ),
                          ),
                        )
                      : GridView.builder(
                          padding: const EdgeInsets.all(16),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                            childAspectRatio: 0.72,
                          ),
                          itemCount: _products.length,
                          itemBuilder: (_, i) {
                            final p = _products[i];
                            return _ProductCard(product: p);
                          },
                        ),
                ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  final Product product;

  const _ProductCard({required this.product});

  @override
  Widget build(BuildContext context) {
    final p = product;
    return Container(
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
              width: double.infinity,
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
  }
}
