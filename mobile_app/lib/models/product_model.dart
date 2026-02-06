class Product {
  final int id;
  final String? itemCode;
  final String name;
  final String? packSize;
  final String? displayName;
  final int? categoryId;
  final Map<String, dynamic>? category;
  final double price;
  final double bv;
  final double pv;
  final int stock;
  final String? image;

  Product({
    required this.id,
    this.itemCode,
    required this.name,
    this.packSize,
    this.displayName,
    this.categoryId,
    this.category,
    required this.price,
    this.bv = 0,
    this.pv = 0,
    this.stock = 0,
    this.image,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    final price = json['price'];
    final bv = json['bv'];
    final pv = json['pv'];
    return Product(
      id: (json['id'] ?? 0) is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      itemCode: json['item_code']?.toString(),
      name: json['name']?.toString() ?? 'Product',
      packSize: json['pack_size']?.toString(),
      displayName: json['display_name']?.toString(),
      categoryId: json['category_id'] as int?,
      category: json['category'] is Map ? Map<String, dynamic>.from(json['category'] as Map) : null,
      price: price == null ? 0.0 : (price is num ? price.toDouble() : double.tryParse(price.toString()) ?? 0.0),
      bv: bv == null ? 0.0 : (bv is num ? bv.toDouble() : double.tryParse(bv.toString()) ?? 0.0),
      pv: pv == null ? 0.0 : (pv is num ? pv.toDouble() : double.tryParse(pv.toString()) ?? 0.0),
      stock: json['stock'] is int ? json['stock'] as int : int.tryParse(json['stock']?.toString() ?? '0') ?? 0,
      image: json['image']?.toString(),
    );
  }
}
