import 'package:flutter/material.dart';
import '../config/api_config.dart';

class ProductImage extends StatelessWidget {
  final String? imageUrl;
  final double width;
  final double height;
  final BoxFit fit;
  final BorderRadius? borderRadius;

  const ProductImage({
    super.key,
    this.imageUrl,
    this.width = double.infinity,
    this.height = 120,
    this.fit = BoxFit.cover,
    this.borderRadius,
  });

  String get _fullUrl {
    if (imageUrl == null || imageUrl!.trim().isEmpty) return '';
    final url = imageUrl!.trim();
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    final base = ApiConfig.storageBaseUrl;
    return url.startsWith('/') ? '$base$url' : '$base/$url';
  }

  @override
  Widget build(BuildContext context) {
    if (_fullUrl.isEmpty) {
      return _placeholder();
    }
    return ClipRRect(
      borderRadius: borderRadius ?? BorderRadius.zero,
      child: Image.network(
        _fullUrl,
        width: width,
        height: height,
        fit: fit,
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return Container(
            width: width,
            height: height,
            color: Colors.grey[200],
            child: Center(
              child: CircularProgressIndicator(
                value: loadingProgress.expectedTotalBytes != null
                    ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                    : null,
              ),
            ),
          );
        },
        errorBuilder: (_, __, ___) => _placeholder(),
      ),
    );
  }

  Widget _placeholder() {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: borderRadius,
      ),
      child: const Icon(Icons.shopping_bag, size: 48, color: Colors.grey),
    );
  }
}
