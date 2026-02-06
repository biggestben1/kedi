import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config/api_config.dart';
import '../../config/app_theme.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';

class OrderDetailScreen extends StatefulWidget {
  final Order order;

  const OrderDetailScreen({super.key, required this.order});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  late Order _order;
  final ApiService _apiService = ApiService();
  bool _isUpdating = false;
  String? _error;
  final _trackingController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _order = widget.order;
    _trackingController.text = _order.trackingNumber ?? '';
  }

  @override
  void dispose() {
    _trackingController.dispose();
    super.dispose();
  }

  Future<void> _loadDetail() async {
    try {
      final r = await _apiService.get(ApiConfig.driverOrderUrl(_order.id));
      if (r.statusCode == 200 && r.data is Map) {
        final data = (r.data as Map<String, dynamic>)['data'];
        if (data is Map<String, dynamic>) {
          setState(() => _order = Order.fromJson(data));
        }
      }
    } catch (_) {}
  }

  Future<void> _updateStatus(String status) async {
    setState(() {
      _isUpdating = true;
      _error = null;
    });
    try {
      final r = await _apiService.patch(ApiConfig.driverOrderStatusUrl(_order.id), data: {'status': status});
      if (r.statusCode == 200 && r.data is Map) {
        final data = (r.data as Map<String, dynamic>)['data'];
        if (data is Map) {
          setState(() {
            _order = Order.fromJson(Map<String, dynamic>.from(data as Map));
            _isUpdating = false;
            _error = null;
          });
        } else {
          setState(() => _isUpdating = false);
        }
      } else {
        setState(() {
          _isUpdating = false;
          _error = (r.data is Map<String, dynamic> ? (r.data as Map<String, dynamic>)['message'] : null)?.toString() ?? 'Failed to update';
        });
      }
    } on DioException catch (e) {
      setState(() {
        _isUpdating = false;
        _error = e.response?.data?['message']?.toString() ?? e.message ?? 'Failed to update';
      });
    } catch (e) {
      setState(() {
        _isUpdating = false;
        _error = e.toString();
      });
    }
  }

  Future<void> _updateTracking() async {
    setState(() {
      _isUpdating = true;
      _error = null;
    });
    try {
      final r = await _apiService.patch(ApiConfig.driverOrderTrackingUrl(_order.id), data: {
        'tracking_number': _trackingController.text.trim().isEmpty ? null : _trackingController.text.trim(),
      });
      if (r.statusCode == 200 && r.data is Map) {
        final data = (r.data as Map<String, dynamic>)['data'];
        if (data is Map<String, dynamic>) {
          setState(() {
            _order = Order.fromJson(data);
            _trackingController.text = _order.trackingNumber ?? '';
            _isUpdating = false;
            _error = null;
          });
        } else {
          setState(() => _isUpdating = false);
        }
      } else {
        setState(() {
          _isUpdating = false;
          _error = (r.data is Map<String, dynamic> ? (r.data as Map<String, dynamic>)['message'] : null)?.toString() ?? 'Failed to update';
        });
      }
    } on DioException catch (e) {
      setState(() {
        _isUpdating = false;
        _error = e.response?.data?['message']?.toString() ?? e.message ?? 'Failed to update';
      });
    } catch (e) {
      setState(() {
        _isUpdating = false;
        _error = e.toString();
      });
    }
  }

  void _launchMaps(String address) async {
    final uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(address)}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  void _launchPhone(String phone) async {
    final uri = Uri.parse('tel:${phone.replaceAll(RegExp(r'\s'), '')}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  bool _canMarkPacked() => _order.status == 'paid';
  bool _canMarkShipped() => _order.status == 'packed';
  bool _canMarkDelivered() => _order.status == 'shipped';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(_order.invoiceNumber),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_error != null) ...[
              Container(
                padding: const EdgeInsets.all(12),
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.red.shade200)),
                child: Row(
                  children: [
                    Icon(Icons.error_outline, color: Colors.red.shade700, size: 20),
                    const SizedBox(width: 8),
                    Expanded(child: Text(_error!, style: TextStyle(color: Colors.red.shade800, fontSize: 14))),
                  ],
                ),
              ),
            ],
            // Status badge
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Color.lerp(_statusColor(_order.status), Colors.white, 0.8)!,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(_order.statusLabel, style: TextStyle(fontWeight: FontWeight.w600, color: _statusColor(_order.status))),
            ),
            const SizedBox(height: 20),
            // Actions
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (_canMarkPacked())
                  ActionChip(
                    avatar: const Icon(Icons.inventory_2, size: 18, color: Colors.white),
                    label: const Text('Mark Packed'),
                    onPressed: _isUpdating ? null : () => _updateStatus('packed'),
                    backgroundColor: Colors.orange,
                    labelStyle: const TextStyle(color: Colors.white),
                  ),
                if (_canMarkShipped())
                  ActionChip(
                    avatar: const Icon(Icons.local_shipping, size: 18, color: Colors.white),
                    label: const Text('Mark Shipped'),
                    onPressed: _isUpdating ? null : () => _updateStatus('shipped'),
                    backgroundColor: Colors.teal,
                    labelStyle: const TextStyle(color: Colors.white),
                  ),
                if (_canMarkDelivered())
                  ActionChip(
                    avatar: const Icon(Icons.check_circle, size: 18, color: Colors.white),
                    label: const Text('Mark Delivered'),
                    onPressed: _isUpdating ? null : () => _updateStatus('delivered'),
                    backgroundColor: Colors.green,
                    labelStyle: const TextStyle(color: Colors.white),
                  ),
              ],
            ),
            const SizedBox(height: 20),
            // Tracking
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Tracking', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _trackingController,
                      decoration: InputDecoration(
                        hintText: 'Tracking number',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: _isUpdating ? null : _updateTracking,
                        icon: const Icon(Icons.save, size: 18),
                        label: const Text('Update Tracking'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            // Shipping address
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.location_on, color: AppColors.primary, size: 20),
                        const SizedBox(width: 8),
                        const Text('Delivery Address', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(_order.fullAddress, style: const TextStyle(fontSize: 14)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        IconButton(
                          icon: const Icon(Icons.map),
                          onPressed: () => _launchMaps(_order.fullAddress),
                          tooltip: 'Open in Maps',
                        ),
                        IconButton(
                          icon: const Icon(Icons.phone),
                          onPressed: () => _launchPhone(_order.shippingPhone),
                          tooltip: 'Call customer',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            // Items
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.receipt_long, color: AppColors.primary, size: 20),
                        const SizedBox(width: 8),
                        const Text('Items', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ],
                    ),
                    const SizedBox(height: 12),
                    ..._order.items.map((i) => Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(child: Text('${i.productName} x${i.quantity}', style: const TextStyle(fontSize: 14))),
                              Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(i.lineTotal)}', style: const TextStyle(fontWeight: FontWeight.w500)),
                            ],
                          ),
                        )),
                    const Divider(),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Subtotal', style: TextStyle(fontWeight: FontWeight.bold)),
                        Text('${ApiConfig.currencySymbol}${ApiConfig.formatPrice(_order.subtotal)}', style: const TextStyle(fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
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
}
