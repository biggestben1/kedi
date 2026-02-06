import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../config/app_theme.dart';
import '../../config/api_config.dart';
import '../../services/api_service.dart';
import '../../utils/file_picker_helper.dart';
import '../auth/login_screen.dart';
import 'wallet_screen.dart';

class WalletTopupScreen extends StatefulWidget {
  const WalletTopupScreen({super.key});

  @override
  State<WalletTopupScreen> createState() => _WalletTopupScreenState();
}

class _WalletTopupScreenState extends State<WalletTopupScreen> {
  final ApiService _apiService = ApiService();
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _referenceController = TextEditingController();
  PickedProofFile? _proofFile;
  bool _isSubmitting = false;
  String? _error;

  @override
  void dispose() {
    _amountController.dispose();
    _referenceController.dispose();
    super.dispose();
  }

  Future<void> _pickFile() async {
    try {
      final file = await pickProofFile();
      if (file != null && mounted) {
        setState(() {
          _proofFile = file;
          _error = null;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _error = 'Could not pick file: $e');
      }
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final amount = double.tryParse(_amountController.text.trim());
    if (amount == null || amount < 1) {
      setState(() => _error = 'Please enter a valid amount (min ${ApiConfig.currencySymbol}1)');
      return;
    }
    if (_proofFile == null) {
      setState(() => _error = 'Proof of payment is required');
      return;
    }
    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      final f = _proofFile!;
      final formData = FormData.fromMap({
        'amount': amount,
        'reference': _referenceController.text.trim().isEmpty
            ? 'Top-up'
            : _referenceController.text.trim(),
        'proof': MultipartFile.fromBytes(f.bytes, filename: f.name),
      });
      final r = await _apiService.post(ApiConfig.walletTopupUrl, data: formData);
      if (r.statusCode == 201 && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Top-up request submitted. Your wallet will be credited after approval.')),
        );
        Navigator.pop(context, true);
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 && mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
        return;
      }
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message']?.toString()
          : e.message ?? e.toString();
      if (mounted) setState(() => _error = msg ?? 'Request failed');
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Top Up Wallet'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.black87,
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            onSelected: (value) {
              if (value == 'wallet') {
                Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const WalletScreen()));
              } else if (value == 'clear') {
                _amountController.clear();
                _referenceController.clear();
                setState(() {
                  _proofFile = null;
                  _error = null;
                });
              }
            },
            itemBuilder: (_) => [
              const PopupMenuItem(value: 'wallet', child: Text('Back to Wallet')),
              const PopupMenuItem(value: 'clear', child: Text('Clear form')),
            ],
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Add funds to your wallet to pay for orders at checkout.',
                style: TextStyle(color: Colors.grey[700], fontSize: 15),
              ),
              const SizedBox(height: 24),
              TextFormField(
                controller: _amountController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Amount (${ApiConfig.currencySymbol})',
                  hintText: 'e.g. 5000.00',
                  prefixIcon: const Icon(Icons.currency_exchange),
                  border: const OutlineInputBorder(),
                ),
                validator: (v) {
                  final n = double.tryParse(v?.trim() ?? '');
                  if (n == null || n < 1) return 'Enter at least ${ApiConfig.currencySymbol}1';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _referenceController,
                decoration: const InputDecoration(
                  labelText: 'Payment reference (optional)',
                  hintText: 'Bank transfer reference',
                  prefixIcon: Icon(Icons.tag),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Proof of payment (required)',
                style: TextStyle(fontWeight: FontWeight.w500, fontSize: 14),
              ),
              const SizedBox(height: 4),
              Text(
                'Image (jpg, png) or PDF',
                style: TextStyle(color: Colors.grey[600], fontSize: 12),
              ),
              const SizedBox(height: 8),
              InkWell(
                onTap: _isSubmitting ? null : _pickFile,
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey[300]!),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.upload_file, color: Colors.grey[600], size: 32),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Text(
                          _proofFile != null ? _proofFile!.name : 'Tap to attach proof (image or PDF) - required',
                          style: TextStyle(color: _proofFile != null ? Colors.black87 : Colors.grey[600]),
                        ),
                      ),
                      if (_proofFile != null)
                        IconButton(
                          icon: const Icon(Icons.close, color: Colors.red),
                          onPressed: () => setState(() => _proofFile = null),
                        ),
                    ],
                  ),
                ),
              ),
              if (_error != null) ...[
                const SizedBox(height: 16),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: _isSubmitting ? null : _submit,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                ),
                child: _isSubmitting
                    ? const SizedBox(
                        height: 24,
                        width: 24,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('Submit Top-up Request'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
