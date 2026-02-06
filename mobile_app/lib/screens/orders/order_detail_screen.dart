import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/app_theme.dart';
import 'package:intl/intl.dart';
import '../../config/api_config.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../auth/login_screen.dart';

class OrderDetailScreen extends StatefulWidget {
  final int orderId;

  const OrderDetailScreen({super.key, required this.orderId});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  final ApiService _apiService = ApiService();
  Order? _order;
  bool _isLoading = true;
  String? _error;

  Future<void> _loadOrder() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final r = await _apiService.get('${ApiConfig.ordersUrl}/${widget.orderId}');
      if (r.statusCode == 200 && r.data is Map) {
        final data = (r.data as Map)['data'];
        if (data is Map) {
          setState(() => _order = Order.fromJson(Map<String, dynamic>.from(data as Map)));
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
      setState(() => _error = e.response?.data is Map
          ? (e.response!.data as Map)['message']?.toString()
          : e.message ?? e.toString());
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  void initState() {
    super.initState();
    _loadOrder();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Order Details'),
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
                        ElevatedButton(onPressed: _loadOrder, child: const Text('Retry')),
                      ],
                    ),
                  ),
                )
              : _order == null
                  ? const Center(child: Text('Order not found'))
                  : SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildStatusCard(),
                          const SizedBox(height: 16),
                          _buildDeliveryStatusCard(),
                          const SizedBox(height: 16),
                          _buildTrackingCard(),
                          const SizedBox(height: 16),
                          _buildItemsCard(),
                          const SizedBox(height: 16),
                          _buildShippingCard(),
                        ],
                      ),
                    ),
    );
  }

  Widget _buildStatusCard() {
    final o = _order!;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(o.invoiceNumber, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _statusColor(o.status).withOpacity(0.2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(o.statusLabel, style: TextStyle(fontWeight: FontWeight.w600, color: _statusColor(o.status))),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(o.subtotal)}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            if (o.createdAt != null)
              Text(DateFormat('MMMM d, y • h:mm a').format(o.createdAt!), style: TextStyle(fontSize: 12, color: Colors.grey[600])),
          ],
        ),
      ),
    );
  }

  static const _deliveryStages = [
    ('pending', 'Order Pending', Icons.schedule, 'Awaiting confirmation'),
    ('paid', 'Order Confirmed', Icons.check_circle_outline, 'Payment received'),
    ('packed', 'Picked from Store', Icons.inventory_2_outlined, 'Ready for dispatch'),
    ('shipped', 'On the Way to You', Icons.local_shipping_outlined, 'Out for delivery'),
    ('delivered', 'Delivered', Icons.home_outlined, 'Package received'),
    ('completed', 'Completed', Icons.done_all, 'Order complete'),
  ];

  int _stageIndex(String status) {
    final idx = _deliveryStages.indexWhere((s) => s.$1 == status);
    if (idx >= 0) return idx;
    if (status == 'cancelled') return -1;
    return 0; // default to first stage
  }

  Widget _buildDeliveryStatusCard() {
    final o = _order!;
    if (o.status == 'cancelled') {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Icon(Icons.cancel, color: Colors.red[700], size: 28),
              const SizedBox(width: 12),
              Text('Order Cancelled', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Colors.red[700])),
            ],
          ),
        ),
      );
    }
    final currentIdx = _stageIndex(o.status);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.timeline, color: AppColors.primary),
                const SizedBox(width: 8),
                const Text('Delivery Status', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 20),
            ...List.generate(_deliveryStages.length, (i) {
              final stage = _deliveryStages[i];
              final isCompleted = i <= currentIdx;
              final isCurrent = i == currentIdx;
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Column(
                      children: [
                        Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: isCompleted ? AppColors.primary : Colors.grey[200],
                            shape: BoxShape.circle,
                            border: isCurrent ? Border.all(color: AppColors.primary, width: 3) : null,
                          ),
                          child: Icon(stage.$3, size: 18, color: isCompleted ? Colors.white : Colors.grey[500]),
                        ),
                        if (i < _deliveryStages.length - 1)
                          Container(
                            width: 2,
                            height: 24,
                            color: isCompleted ? AppColors.primary : Colors.grey[200],
                          ),
                      ],
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            stage.$2,
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: isCurrent ? FontWeight.bold : FontWeight.w500,
                              color: isCompleted ? Colors.black87 : Colors.grey[600],
                            ),
                          ),
                          Text(
                            stage.$4,
                            style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Widget _buildTrackingCard() {
    final o = _order!;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.local_shipping, color: AppColors.primary),
                const SizedBox(width: 8),
                const Text('Tracking', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              o.displayTracking,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, letterSpacing: 1.2),
            ),
            if (o.trackingNumber != null && o.trackingNumber!.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  'Use this number to track your delivery',
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildItemsCard() {
    final o = _order!;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Items', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            ...o.items.map((item) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.productName, style: const TextStyle(fontWeight: FontWeight.w500)),
                            Text('Qty: ${item.quantity} × ${ApiConfig.currencySymbol}${ApiConfig.formatPrice(item.unitPrice)}', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                          ],
                        ),
                      ),
                      Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(item.lineTotal)}', style: const TextStyle(fontWeight: FontWeight.w600)),
                    ],
                  ),
                )),
            const Divider(),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Subtotal', style: TextStyle(fontWeight: FontWeight.w600)),
                Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(o.subtotal)}', style: const TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildShippingCard() {
    final o = _order!;
    final address = [
      o.shippingAddress,
      o.shippingCity,
      if (o.shippingState != null && o.shippingState!.isNotEmpty) o.shippingState,
      if (o.shippingPostalCode != null && o.shippingPostalCode!.isNotEmpty) o.shippingPostalCode,
    ].where((e) => e != null && e.toString().isNotEmpty).join(', ');
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.location_on, color: AppColors.primary, size: 20),
                const SizedBox(width: 8),
                const Text('Shipping Address', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 12),
            Text(address, style: const TextStyle(fontSize: 14)),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.phone, size: 16, color: Colors.grey[600]),
                const SizedBox(width: 6),
                Text(o.shippingPhone, style: TextStyle(fontSize: 14, color: Colors.grey[700])),
              ],
            ),
            const SizedBox(height: 8),
            Text('Payment: ${o.paymentMethod == 'wallet' ? 'Wallet' : 'Pay on Delivery'}', style: TextStyle(fontSize: 13, color: Colors.grey[700])),
          ],
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
