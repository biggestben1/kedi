/// Platform-agnostic file picker for proof of payment.
/// Uses native HTML file input on web (avoids file_picker LateInitializationError).
/// Uses file_picker on mobile/desktop.
import 'file_picker_helper_stub.dart'
    if (dart.library.io) 'file_picker_helper_io.dart'
    if (dart.library.html) 'file_picker_helper_web.dart' as impl;

/// Picked file with bytes and name. Use bytes for upload.
class PickedProofFile {
  final List<int> bytes;
  final String name;

  PickedProofFile({required this.bytes, required this.name});
}

/// Pick an image or PDF file. Returns null if user cancels.
Future<PickedProofFile?> pickProofFile() => impl.pickProofFile();
