import 'choice.dart';

class ExamResult {
  final int attemptId;
  final int examId;
  final String examTitle;
  final String candidateName;
  final int timeSpentSeconds;
  final int totalQuestions;
  final int correctCount;
  final int wrongCount;
  final int skippedCount;
  final double accuracyPercent;
  final int toeicScore;
  final List<QuestionResultDetail> details;

  ExamResult({
    required this.attemptId,
    required this.examId,
    required this.examTitle,
    required this.candidateName,
    required this.timeSpentSeconds,
    required this.totalQuestions,
    required this.correctCount,
    required this.wrongCount,
    required this.skippedCount,
    required this.accuracyPercent,
    required this.toeicScore,
    required this.details,
  });

  factory ExamResult.fromJson(Map<String, dynamic> json) {
    var examInfo = json['exam'] as Map<String, dynamic>? ?? {};
    var candidateInfo = json['candidate'] as Map<String, dynamic>? ?? {};
    var summary = json['score_summary'] as Map<String, dynamic>? ?? {};
    var rawDetails = json['details'] as List<dynamic>? ?? [];

    List<QuestionResultDetail> parsedDetails = rawDetails
        .map((d) => QuestionResultDetail.fromJson(d as Map<String, dynamic>))
        .toList();

    return ExamResult(
      attemptId: int.tryParse(json['attempt_id'].toString()) ?? 0,
      examId: int.tryParse(examInfo['id'].toString()) ?? 0,
      examTitle: examInfo['title']?.toString() ?? 'Đề thi TOEIC',
      candidateName: candidateInfo['name']?.toString() ?? 'Thí sinh',
      timeSpentSeconds: int.tryParse(candidateInfo['time_spent_seconds'].toString()) ?? 0,
      totalQuestions: int.tryParse(summary['total_questions'].toString()) ?? 0,
      correctCount: int.tryParse(summary['correct_count'].toString()) ?? 0,
      wrongCount: int.tryParse(summary['wrong_count'].toString()) ?? 0,
      skippedCount: int.tryParse(summary['skipped_count'].toString()) ?? 0,
      accuracyPercent: double.tryParse(summary['accuracy_percent'].toString()) ?? 0.0,
      toeicScore: int.tryParse(summary['toeic_score'].toString()) ?? 5,
      details: parsedDetails,
    );
  }
}

class QuestionResultDetail {
  final int questionId;
  final int questionNumber;
  final int part;
  final int? passageId;
  final String questionText;
  final int? selectedChoiceId;
  final String selectedSymbol;
  final int? correctChoiceId;
  final String correctSymbol;
  final String correctAnswerText;
  final bool isCorrect;
  final bool isSkipped;
  final List<Choice> choices;

  QuestionResultDetail({
    required this.questionId,
    required this.questionNumber,
    required this.part,
    this.passageId,
    required this.questionText,
    this.selectedChoiceId,
    required this.selectedSymbol,
    this.correctChoiceId,
    required this.correctSymbol,
    required this.correctAnswerText,
    required this.isCorrect,
    required this.isSkipped,
    required this.choices,
  });

  factory QuestionResultDetail.fromJson(Map<String, dynamic> json) {
    var rawChoices = json['choices'] as List<dynamic>? ?? [];
    List<Choice> parsedChoices = rawChoices
        .map((c) => Choice.fromJson(c as Map<String, dynamic>))
        .toList();

    return QuestionResultDetail(
      questionId: int.tryParse(json['question_id'].toString()) ?? 0,
      questionNumber: int.tryParse(json['question_number'].toString()) ?? 0,
      part: int.tryParse(json['part'].toString()) ?? 5,
      passageId: json['passage_id'] != null ? int.tryParse(json['passage_id'].toString()) : null,
      questionText: json['question_text']?.toString() ?? '',
      selectedChoiceId: json['selected_choice_id'] != null ? int.tryParse(json['selected_choice_id'].toString()) : null,
      selectedSymbol: json['selected_symbol']?.toString() ?? '',
      correctChoiceId: json['correct_choice_id'] != null ? int.tryParse(json['correct_choice_id'].toString()) : null,
      correctSymbol: json['correct_symbol']?.toString() ?? '',
      correctAnswerText: json['correct_answer_text']?.toString() ?? '',
      isCorrect: json['is_correct'] == true || json['is_correct'] == 1,
      isSkipped: json['is_skipped'] == true || json['is_skipped'] == 1,
      choices: parsedChoices,
    );
  }
}
