import 'dart:async';
import 'dart:html' as html;
import 'dart:typed_data';

import 'file_picker_helper.dart';

Future<PickedProofFile?> pickProofFile() async {
  final input = html.FileUploadInputElement();
  input.accept = '.jpg,.jpeg,.png,.pdf';
  input.multiple = false;
  input.style.display = 'none';
  html.document.body?.append(input);

  final completer = Completer<PickedProofFile?>();
  Timer? timeoutTimer;
  void cleanup() {
    timeoutTimer?.cancel();
    input.remove();
  }

  // If user cancels, change never fires - timeout after 2 min
  timeoutTimer = Timer(const Duration(minutes: 2), () {
    if (!completer.isCompleted) {
      completer.complete(null);
      cleanup();
    }
  });

  void onChange(html.Event e) {
    if (completer.isCompleted) return;
    final files = input.files;
    if (files != null && files.isNotEmpty) {
      final file = files.first;
      final reader = html.FileReader();
      reader.onLoadEnd.listen((_) {
        if (completer.isCompleted) return;
        if (reader.readyState == html.FileReader.DONE) {
          final result = reader.result;
          if (result != null && result is ByteBuffer) {
            completer.complete(PickedProofFile(
              bytes: result.asUint8List().toList(),
              name: file.name,
            ));
          } else {
            completer.complete(null);
          }
        }
        cleanup();
      });
      reader.readAsArrayBuffer(file);
    } else {
      completer.complete(null);
      cleanup();
    }
  }

  input.addEventListener('change', onChange);
  input.click();

  return completer.future;
}
