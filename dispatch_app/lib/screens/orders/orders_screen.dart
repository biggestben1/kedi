import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/api_config.dart';
import '../../config/app_theme.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../auth/login_screen.dart';
import 'order_detail_screen.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  final ApiService _apiService = ApiService();
  List<Order> _orders = [];
  bool _isLoading = true;
  String? _error;
  String? _userName;
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
      await Future.wait([_loadUser(), _loadOrders()]);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 && mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
        return;
      }
      if (e.response?.statusCode == 403 && mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
        return;
      }
      setState(() => _error = e.response?.data?['message']?.toString() ?? e.message ?? e.toString());
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _loadUser() async {
    final user = await AuthService(_apiService).getCurrentUser();
    if (user != null && mounted) setState(() => _userName = user.name);
  }

  Future<void> _loadOrders({String? search}) async {
    final params = <String, dynamic>{};
    if (search != null && search.trim().isNotEmpty) params['search'] = search.trim();

    final r = await _apiService.get(ApiConfig.driverOrdersUrl, queryParameters: params.isEmpty ? null : params);
    if (r.statusCode != 200 || r.data is! Map) return;

    final data = r.data as Map<String, dynamic>;
    final list = data['data'];
    if (list is! List) return;

    setState(() {
      _orders = list
          .map((e) => e is Map<String, dynamic> ? Order.fromJson(e) : null)
          .whereType<Order>()
          .toList();
    });
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'paid': return Colors.blue;
      case 'packed': return Colors.orange;
      case 'shipped': return Colors.teal;
      case 'delivered':
      case 'completed': return Colors.green;
      default: return AppColors.textMuted;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Dispash'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await AuthService(_apiService).logout();
              if (mounted) {
                Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                  (route) => false,
                );
              }
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
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
              : RefreshIndicator(
                  onRefresh: _loadData,
                  color: AppColors.primary,
                  child: CustomScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Hello, ${_userName ?? 'Driver'} 👋', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 12),
                              TextField(
                                controller: _searchController,
                                decoration: InputDecoration(
                                  hintText: 'Search by order#, tracking, customer...',
                                  prefixIcon: const Icon(Icons.search),
                                  filled: true,
                                  fillColor: Colors.white,
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                                ),
                                onSubmitted: (v) => _loadOrders(search: v),
                              ),
                              const SizedBox(height: 8),
                              Text('${_orders.length} orders', style: TextStyle(fontSize: 14, color: AppColors.textMuted)),
                            ],
                          ),
                        ),
                      ),
                      if (_orders.isEmpty)
                        const SliverFillRemaining(
                          child: Center(child: Text('No orders to deliver', style: TextStyle(color: AppColors.textMuted))),
                        )
                      else
                        SliverPadding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          sliver: SliverList(
                            delegate: SliverChildBuilderDelegate(
                              (_, i) {
                                final o = _orders[i];
                                return Card(
                                  margin: const EdgeInsets.only(bottom: 12),
                                  child: InkWell(
                                    onTap: () => Navigator.push(
                                      context,
                                      MaterialPageRoute(builder: (_) => OrderDetailScreen(order: o)),
                                    ).then((_) => _loadOrders()),
                                    borderRadius: BorderRadius.circular(12),
                                    child: Padding(
                                      padding: const EdgeInsets.all(16),
                                      child: Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Container(
                                            width: 48,
                                            height: 48,
                                            decoration: BoxDecoration(color: Color.lerp(_statusColor(o.status), Colors.white, 0.8), borderRadius: BorderRadius.circular(8)),
                                            child: Icon(Icons.receipt_long, color: _statusColor(o.status)),
                                          ),
                                          const SizedBox(width: 12),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(o.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16)),
                                                const SizedBox(height: 4),
                                                Text(o.fullAddress, style: TextStyle(fontSize: 13, color: AppColors.textMuted), maxLines: 2, overflow: TextOverflow.ellipsis),
                                                const SizedBox(height: 4),
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                  decoration: BoxDecoration(color: Color.lerp(_statusColor(o.status), Colors.white, 0.8), borderRadius: BorderRadius.circular(6)),
                                                  child: Text(o.statusLabel, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: _statusColor(o.status))),
                                                ),
                                              ],
                                            ),
                                          ),
                                          const Icon(Icons.chevron_right),
                                        ],
                                      ),
                                    ),
                                  ),
                                );
                              },
                              childCount: _orders.length,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }
}
