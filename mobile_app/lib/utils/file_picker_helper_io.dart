import 'dart:io';

import 'package:file_picker/file_picker.dart';

import 'file_picker_helper.dart';

Future<PickedProofFile?> pickProofFile() async {
  final result = await FilePicker.platform.pickFiles(
    type: FileType.custom,
    allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
    withData: true,
  );
  if (result == null || result.files.isEmpty) return null;
  final f = result.files.first;
  if (f.bytes != null) {
    return PickedProofFile(bytes: f.bytes!, name: f.name);
  }
  if (f.path != null) {
    final file = File(f.path!);
    final bytes = await file.readAsBytes();
    return PickedProofFile(bytes: bytes, name: f.name);
  }
  return null;
}
