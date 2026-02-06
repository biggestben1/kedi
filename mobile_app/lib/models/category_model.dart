class Category {
  final int id;
  final String name;
  final String slug;
  final int? sortOrder;

  Category({
    required this.id,
    required this.name,
    required this.slug,
    this.sortOrder,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: (json['id'] ?? 0) is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      sortOrder: json['sort_order'] as int?,
    );
  }
}
