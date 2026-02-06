class WalletTransaction {
  final int id;
  final String type; // credit, debit
  final double amount;
  final double? balanceAfter;
  final String? reference;
  final String status; // pending, approved, rejected
  final DateTime? createdAt;

  WalletTransaction({
    required this.id,
    required this.type,
    required this.amount,
    this.balanceAfter,
    this.reference,
    required this.status,
    this.createdAt,
  });

  factory WalletTransaction.fromJson(Map<String, dynamic> json) {
    final amt = json['amount'];
    final ba = json['balance_after'];
    return WalletTransaction(
      id: (json['id'] ?? 0) is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      type: json['type']?.toString() ?? 'credit',
      amount: amt == null ? 0.0 : (amt is num ? amt.toDouble() : double.tryParse(amt.toString()) ?? 0.0),
      balanceAfter: ba == null ? null : (ba is num ? ba.toDouble() : double.tryParse(ba.toString())),
      reference: json['reference']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  bool get isCredit => type == 'credit';
  bool get isDebit => type == 'debit';
  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
}
