class Exam {
  final int id;
  final String title;
  final int durationMinutes;
  final int totalQuestions;
  final int actualQuestions;
  final String createdAt;

  Exam({
    required this.id,
    required this.title,
    required this.durationMinutes,
    required this.totalQuestions,
    required this.actualQuestions,
    required this.createdAt,
  });

  factory Exam.fromJson(Map<String, dynamic> json) {
    return Exam(
      id: int.tryParse(json['MaDeThi'].toString()) ?? 0,
      title: json['TenDeThi']?.toString() ?? 'Đề thi TOEIC',
      durationMinutes: int.tryParse(json['ThoiLuongPhut'].toString()) ?? 75,
      totalQuestions: int.tryParse(json['TongSoCau'].toString()) ?? 100,
      actualQuestions: int.tryParse(json['SoCauThucTe'].toString()) ?? 0,
      createdAt: json['NgayTao']?.toString() ?? '',
    );
  }
}
