class OrderItem {
  final String itemCode;
  final String productName;
  final int quantity;
  final double unitPrice;
  final double lineTotal;

  OrderItem({
    required this.itemCode,
    required this.productName,
    required this.quantity,
    required this.unitPrice,
    required this.lineTotal,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    final up = json['unit_price'];
    final lt = json['line_total'];
    return OrderItem(
      itemCode: json['item_code']?.toString() ?? '',
      productName: json['product_name']?.toString() ?? '',
      quantity: json['quantity'] is int ? json['quantity'] as int : int.tryParse(json['quantity']?.toString() ?? '0') ?? 0,
      unitPrice: up == null ? 0.0 : (up is num ? up.toDouble() : double.tryParse(up.toString()) ?? 0.0),
      lineTotal: lt == null ? 0.0 : (lt is num ? lt.toDouble() : double.tryParse(lt.toString()) ?? 0.0),
    );
  }
}

class Order {
  final int id;
  final String invoiceNumber;
  final String? trackingNumber;
  final String? deliveryCourier;
  final String status;
  final String paymentMethod;
  final double subtotal;
  final String shippingAddress;
  final String shippingCity;
  final String? shippingState;
  final String? shippingPostalCode;
  final String shippingPhone;
  final DateTime? createdAt;
  final List<OrderItem> items;
  final String? customerName;
  final String? customerEmail;
  final String? customerPhone;

  Order({
    required this.id,
    required this.invoiceNumber,
    this.trackingNumber,
    this.deliveryCourier,
    required this.status,
    required this.paymentMethod,
    required this.subtotal,
    required this.shippingAddress,
    required this.shippingCity,
    this.shippingState,
    this.shippingPostalCode,
    required this.shippingPhone,
    this.createdAt,
    this.items = const [],
    this.customerName,
    this.customerEmail,
    this.customerPhone,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    final sub = json['subtotal'];
    final itemsList = json['items'];
    return Order(
      id: (json['id'] ?? 0) is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      invoiceNumber: json['invoice_number']?.toString() ?? 'ORD-${json['id']}',
      trackingNumber: json['tracking_number']?.toString(),
      deliveryCourier: json['delivery_courier']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      paymentMethod: json['payment_method']?.toString() ?? 'pay_on_delivery',
      subtotal: sub == null ? 0.0 : (sub is num ? sub.toDouble() : double.tryParse(sub.toString()) ?? 0.0),
      shippingAddress: json['shipping_address']?.toString() ?? '',
      shippingCity: json['shipping_city']?.toString() ?? '',
      shippingState: json['shipping_state']?.toString(),
      shippingPostalCode: json['shipping_postal_code']?.toString(),
      shippingPhone: json['shipping_phone']?.toString() ?? '',
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
      items: itemsList is List
          ? itemsList.map((e) => e is Map ? OrderItem.fromJson(Map<String, dynamic>.from(e as Map)) : null).whereType<OrderItem>().toList()
          : [],
      customerName: json['customer_name']?.toString(),
      customerEmail: json['customer_email']?.toString(),
      customerPhone: json['customer_phone']?.toString(),
    );
  }

  String get statusLabel {
    switch (status) {
      case 'pending': return 'Pending';
      case 'paid': return 'Paid';
      case 'packed': return 'Packed';
      case 'shipped': return 'Shipped';
      case 'delivered': return 'Delivered';
      case 'completed': return 'Completed';
      case 'cancelled': return 'Cancelled';
      default: return status;
    }
  }

  String get displayTracking => trackingNumber ?? invoiceNumber;

  String get fullAddress {
    final parts = [shippingAddress, shippingCity, if (shippingState != null && shippingState!.trim().isNotEmpty) shippingState!, if (shippingPostalCode != null && shippingPostalCode!.trim().isNotEmpty) shippingPostalCode!];
    return parts.where((e) => e.trim().isNotEmpty).join(', ');
  }
}
