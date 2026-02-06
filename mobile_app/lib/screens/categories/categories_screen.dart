import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../config/app_theme.dart';
import '../../config/api_config.dart';
import '../../models/category_model.dart';
import '../../providers/cart_provider.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../auth/login_screen.dart';
import '../cart/cart_screen.dart';
import '../home/products_screen.dart';

class CategoriesScreen extends StatefulWidget {
  final VoidCallback? onCartTap;

  const CategoriesScreen({super.key, this.onCartTap});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  final ApiService _apiService = ApiService();
  List<Category> _categories = [];
  List<Category> _filteredCategories = [];
  bool _isLoading = true;
  String? _error;
  final TextEditingController _searchController = TextEditingController();

  static const _pastelColors = [
    Color(0xFFEF9A9A), // red/coral - Heart & Cardiovascular
    Color(0xFFF8BBD9), // light pink - Women's Health
    Color(0xFFFFAB91), // peach - Blood Support
    Color(0xFFE1BEE7), // light purple - Digestive Health
    Color(0xFFB3E5FC), // light blue - Eye Support
    Color(0xFFFFCC80), // orange - Bone & Joint
    Color(0xFF81D4FA), // lighter blue - Diabetes Support
    Color(0xFFCE93D8), // purple - Immune & Wellness
    Color(0xFFA5D6A7), // mint green - Immune/Herbal
    Color(0xFFC5E1A5), // light green - Herbal & Specialty
  ];

  static IconData _iconForCategory(Category c) {
    final name = c.name.toLowerCase();
    if (name.contains('heart') || name.contains('cardiovascular')) return Icons.favorite;
    if (name.contains('women')) return Icons.self_improvement;
    if (name.contains('blood')) return Icons.water_drop;
    if (name.contains('digestive')) return Icons.air;
    if (name.contains('eye')) return Icons.visibility;
    if (name.contains('bone') || name.contains('joint')) return Icons.accessible;
    if (name.contains('diabetes')) return Icons.monitor_heart;
    if (name.contains('immune') || name.contains('wellness')) return Icons.health_and_safety;
    if (name.contains('hormonal')) return Icons.psychology;
    if (name.contains('herbal') || name.contains('specialty')) return Icons.spa;
    return Icons.category;
  }

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _filterCategories(String query) {
    setState(() {
      if (query.trim().isEmpty) {
        _filteredCategories = List.from(_categories);
      } else {
        final q = query.trim().toLowerCase();
        _filteredCategories = _categories.where((c) => c.name.toLowerCase().contains(q)).toList();
      }
    });
  }

  Future<void> _loadCategories() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final r = await _apiService.get(ApiConfig.categoriesUrl);
      if (r.statusCode == 200 && r.data is Map) {
        final list = (r.data as Map)['data'];
        if (list is List) {
          final cats = list
              .map((e) => e is Map ? Category.fromJson(Map<String, dynamic>.from(e as Map)) : null)
              .whereType<Category>()
              .toList();
          setState(() {
            _categories = cats;
            _filteredCategories = List.from(cats);
          });
        }
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
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
                        ElevatedButton(onPressed: _loadCategories, child: const Text('Retry')),
                      ],
                    ),
                  ),
                )
              : SafeArea(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 16),
                      // Header: title + icons
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'OptimalConsult',
                              style: TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primary,
                              ),
                            ),
                            Row(
                              children: [
                                IconButton(
                                  icon: Icon(Icons.notifications_outlined, color: AppColors.textMuted),
                                  onPressed: () {},
                                ),
                                Consumer<CartProvider>(
                                  builder: (_, cart, __) => Stack(
                                    clipBehavior: Clip.none,
                                    children: [
                                      IconButton(
                                        icon: Icon(Icons.shopping_cart_outlined, color: AppColors.textMuted),
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
                      ),
                      const SizedBox(height: 16),
                      // Search bar
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: TextField(
                          controller: _searchController,
                          onChanged: _filterCategories,
                          decoration: InputDecoration(
                            hintText: 'Search products',
                            prefixIcon: Icon(Icons.search, color: Colors.grey[600]),
                            suffixIcon: Icon(Icons.tune, color: Colors.grey[600]),
                            filled: true,
                            fillColor: Colors.white,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(color: Colors.grey[300]!),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(color: Colors.grey[300]!),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      // Category grid
                      Expanded(
                        child: RefreshIndicator(
                          onRefresh: _loadCategories,
                          child: _filteredCategories.isEmpty
                              ? SingleChildScrollView(
                                  physics: const AlwaysScrollableScrollPhysics(),
                                  child: SizedBox(
                                    height: MediaQuery.of(context).size.height * 0.5,
                                    child: Center(
                                      child: Text('No categories match your search', style: TextStyle(color: Colors.grey[600])),
                                    ),
                                  ),
                                )
                              : GridView.builder(
                                  padding: const EdgeInsets.symmetric(horizontal: 16),
                                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                    crossAxisCount: 2,
                                    crossAxisSpacing: 12,
                                    mainAxisSpacing: 12,
                                    childAspectRatio: 0.85,
                                  ),
                                  itemCount: _filteredCategories.length,
                                  itemBuilder: (_, i) {
                                    final c = _filteredCategories[i];
                                    final color = _pastelColors[i % _pastelColors.length];
                                    final icon = _iconForCategory(c);
                                    return _CategoryCard(
                                      category: c,
                                      color: color,
                                      icon: icon,
                                      onTap: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) => ProductsScreen(categoryId: c.id, categoryName: c.name),
                                          ),
                                        );
                                      },
                                    );
                                  },
                                ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}

class _CategoryCard extends StatelessWidget {
  final Category category;
  final Color color;
  final IconData icon;
  final VoidCallback onTap;

  const _CategoryCard({
    required this.category,
    required this.color,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: color.withOpacity(0.4),
                blurRadius: 8,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Stack(
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      category.name,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        height: 1.2,
                      ),
                    ),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
              Positioned(
                right: 12,
                bottom: 12,
                child: Icon(icon, size: 56, color: Colors.white.withOpacity(0.9)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
