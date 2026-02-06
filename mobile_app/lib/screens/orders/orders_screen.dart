import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/app_theme.dart';
import 'package:intl/intl.dart';
import '../../config/api_config.dart';
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

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final r = await _apiService.get(ApiConfig.ordersUrl, queryParameters: {'per_page': 50});
      if (r.statusCode == 200 && r.data is Map) {
        final data = r.data as Map;
        final list = data['data'];
        if (list is List) {
          setState(() {
            _orders = list
                .map((e) => e is Map ? Order.fromJson(Map<String, dynamic>.from(e as Map)) : null)
                .whereType<Order>()
                .toList();
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
  void initState() {
    super.initState();
    _loadOrders();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('My Orders'),
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
                        ElevatedButton(onPressed: _loadOrders, child: const Text('Retry')),
                      ],
                    ),
                  ),
                )
              : _orders.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.receipt_long, size: 80, color: Colors.grey[400]),
                          const SizedBox(height: 16),
                          Text('No orders yet', style: TextStyle(fontSize: 18, color: Colors.grey[600])),
                          const SizedBox(height: 8),
                          Text('Your orders will appear here', style: TextStyle(color: Colors.grey[500])),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadOrders,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _orders.length,
                        itemBuilder: (_, i) {
                          final order = _orders[i];
                          return _OrderCard(order: order);
                        },
                      ),
                    ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  final Order order;

  const _OrderCard({required this.order});

  @override
  Widget build(BuildContext context) {
    final dateStr = order.createdAt != null
        ? DateFormat('MMM d, y').format(order.createdAt!)
        : '';
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => OrderDetailScreen(orderId: order.id)),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    order.invoiceNumber,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _statusColor(order.status).withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      order.statusLabel,
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: _statusColor(order.status)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                '${ApiConfig.currencySymbol}${ApiConfig.formatPrice(order.subtotal)}',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 4),
              Text(dateStr, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
              if (order.trackingNumber != null && order.trackingNumber!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.local_shipping, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 6),
                    Text('Track: ${order.trackingNumber}', style: TextStyle(fontSize: 12, color: Colors.grey[700])),
                  ],
                ),
              ],
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerRight,
                child: Text('View details →', style: TextStyle(fontSize: 13, color: AppColors.primary, fontWeight: FontWeight.w500)),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'delivered':
      case 'completed': return Colors.green;
      case 'shipped':
      case 'packed': return Colors.blue;
      case 'paid': return Colors.orange;
      case 'cancelled': return Colors.red;
      default: return Colors.grey;
    }
  }
}
