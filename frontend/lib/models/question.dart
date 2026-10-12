import 'choice.dart';

class Question {
  final int id;
  final int questionNumber;
  final int part; // 5, 6, 7
  final int? passageId;
  final String content;
  final List<Choice> choices;

  Question({
    required this.id,
    required this.questionNumber,
    required this.part,
    this.passageId,
    required this.content,
    required this.choices,
  });

  factory Question.fromJson(Map<String, dynamic> json) {
    var rawChoices = json['LuaChon'] as List<dynamic>? ?? [];
    List<Choice> parsedChoices = rawChoices.map((c) => Choice.fromJson(c as Map<String, dynamic>)).toList();

    return Question(
      id: int.tryParse(json['MaCauHoi'].toString()) ?? 0,
      questionNumber: int.tryParse(json['SoThuTuCau'].toString()) ?? 0,
      part: int.tryParse(json['PhanThi'].toString()) ?? 5,
      passageId: json['MaDoanDoc'] != null ? int.tryParse(json['MaDoanDoc'].toString()) : null,
      content: json['NoiDungCauHoi']?.toString() ?? '',
      choices: parsedChoices,
    );
  }
}
