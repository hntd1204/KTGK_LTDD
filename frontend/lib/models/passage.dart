class Passage {
  final int id;
  final int examId;
  final int part;
  final String? type;
  final String content;
  final String? imagePath;

  Passage({
    required this.id,
    required this.examId,
    required this.part,
    this.type,
    required this.content,
    this.imagePath,
  });

  factory Passage.fromJson(Map<String, dynamic> json) {
    return Passage(
      id: int.tryParse(json['MaDoanDoc'].toString()) ?? 0,
      examId: int.tryParse(json['MaDeThi'].toString()) ?? 0,
      part: int.tryParse(json['PhanThi'].toString()) ?? 7,
      type: json['LoaiDoan']?.toString(),
      content: json['NoiDung']?.toString() ?? '',
      imagePath: json['DuongDanHinh']?.toString(),
    );
  }
}
