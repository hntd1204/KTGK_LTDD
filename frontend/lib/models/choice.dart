class Choice {
  final int id;
  final String symbol; // 'A', 'B', 'C', 'D'
  final String content;
  final bool? isCorrect;

  Choice({
    required this.id,
    required this.symbol,
    required this.content,
    this.isCorrect,
  });

  factory Choice.fromJson(Map<String, dynamic> json) {
    return Choice(
      id: int.tryParse(json['MaLuaChon'].toString()) ?? 0,
      symbol: json['KyHieu']?.toString() ?? '',
      content: json['NoiDung']?.toString() ?? json['NoiDungLuaChon']?.toString() ?? '',
      isCorrect: json['LaDapAnDung'] != null ? (json['LaDapAnDung'] == 1 || json['LaDapAnDung'] == true) : null,
    );
  }
}
