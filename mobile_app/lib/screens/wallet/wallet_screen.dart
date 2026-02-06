import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/app_theme.dart';
import 'package:intl/intl.dart';
import '../../config/api_config.dart';
import '../../models/wallet_transaction_model.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../auth/login_screen.dart';
import 'wallet_topup_screen.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final ApiService _apiService = ApiService();
  double _balance = 0;
  List<WalletTransaction> _transactions = [];
  bool _isLoading = true;
  String? _error;

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      await Future.wait([_loadBalance(), _loadTransactions()]);
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

  Future<void> _loadBalance() async {
    final r = await _apiService.get(ApiConfig.walletUrl);
    if (r.statusCode == 200 && r.data is Map) {
      final b = (r.data as Map)['balance'];
      if (mounted) {
        setState(() {
          _balance = b == null ? 0.0 : (b is num ? b.toDouble() : double.tryParse(b.toString()) ?? 0.0);
        });
      }
    }
  }

  Future<void> _loadTransactions() async {
    final r = await _apiService.get(ApiConfig.walletTransactionsUrl);
    if (r.statusCode == 200 && r.data is Map) {
      final list = (r.data as Map)['data'];
      if (list is List && mounted) {
        setState(() {
          _transactions = list
              .map((e) => e is Map ? WalletTransaction.fromJson(Map<String, dynamic>.from(e as Map)) : null)
              .whereType<WalletTransaction>()
              .toList();
        });
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Wallet'),
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
                        ElevatedButton(onPressed: _loadData, child: const Text('Retry')),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadData,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Balance card
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [AppColors.primary, AppColors.primaryLight],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primary.withOpacity(0.3),
                                blurRadius: 12,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(Icons.account_balance_wallet, color: Colors.white.withOpacity(0.9), size: 28),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Available Balance',
                                    style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 14),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Text(
                                '${ApiConfig.currencySymbol}${ApiConfig.formatPrice(_balance)}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 32,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),
                        // Top Up button
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: () async {
                              final result = await Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const WalletTopupScreen()),
                              );
                              if (result == true && mounted) _loadData();
                            },
                            icon: const Icon(Icons.add),
                            label: const Text('Top Up Wallet'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              backgroundColor: AppColors.primary,
                              foregroundColor: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(height: 24),
                        const Text('Recent Transactions', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 12),
                        if (_transactions.isEmpty)
                          Container(
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 2))],
                            ),
                            child: Center(
                              child: Text(
                                'No transactions yet. Top up your wallet to get started.',
                                style: TextStyle(color: Colors.grey[600]),
                                textAlign: TextAlign.center,
                              ),
                            ),
                          )
                        else
                          ListView.separated(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: _transactions.length,
                            separatorBuilder: (_, __) => const SizedBox(height: 8),
                            itemBuilder: (_, i) {
                              final tx = _transactions[i];
                              return _TransactionTile(transaction: tx);
                            },
                          ),
                      ],
                    ),
                  ),
                ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  final WalletTransaction transaction;

  const _TransactionTile({required this.transaction});

  @override
  Widget build(BuildContext context) {
    final isCredit = transaction.isCredit;
    final dateStr = transaction.createdAt != null
        ? DateFormat('MMM d, y • h:mm a').format(transaction.createdAt!)
        : '';
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: (isCredit ? Colors.green : Colors.orange).withOpacity(0.15),
              shape: BoxShape.circle,
            ),
            child: Icon(
              isCredit ? Icons.arrow_downward : Icons.arrow_upward,
              color: isCredit ? Colors.green.shade700 : Colors.orange.shade700,
              size: 22,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  transaction.reference ?? (isCredit ? 'Top-up' : 'Payment'),
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Text(
                  dateStr,
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
                if (transaction.isPending)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      'Pending',
                      style: TextStyle(color: Colors.orange.shade700, fontSize: 12, fontWeight: FontWeight.w500),
                    ),
                  ),
              ],
            ),
          ),
          Text(
            '${isCredit ? '+' : '-'}${ApiConfig.currencySymbol}${ApiConfig.formatPrice(transaction.amount)}',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: isCredit ? Colors.green.shade700 : Colors.black87,
            ),
          ),
        ],
      ),
    );
  }
}
