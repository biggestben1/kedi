class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? role;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.role,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    dynamic roleVal = json['role'];
    String? roleStr = roleVal is String ? roleVal : (roleVal is Map ? (roleVal['name']?.toString()) : null);
    return User(
      id: (json['id'] ?? 0) is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString(),
      role: roleStr,
    );
  }

  bool get isDispatch => role?.toLowerCase() == 'dispatch';
}
