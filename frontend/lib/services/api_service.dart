import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/exam.dart';
import '../models/passage.dart';
import '../models/question.dart';
import '../models/exam_result.dart';

class ApiService {
  // Mặc định kết nối XAMPP Apache trên macOS / Web
  // Có thể tự động fallback nếu chạy trên Android emulator (10.0.2.2)
  static String baseUrl = 'http://localhost/KTGK_LTDD/backend/api';

  static void setBaseUrl(String url) {
    baseUrl = url.endsWith('/') ? url.substring(0, url.length - 1) : url;
  }

  /// Lấy danh sách đề thi (GET /exams.php)
  static Future<List<Exam>> fetchExams() async {
    final uri = Uri.parse('$baseUrl/exams.php');
    try {
      final response = await http.get(uri).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = json.decode(utf8.decode(response.bodyBytes));
        if (data['status'] == 'success') {
          final List list = data['data'] ?? [];
          return list.map((e) => Exam.fromJson(e)).toList();
        }
      }
      throw Exception('Không thể tải danh sách đề thi (Mã lỗi: ${response.statusCode})');
    } catch (e) {
      // Thử fallback sang localhost:8000 nếu XAMPP htdocs khác cổng
      if (!baseUrl.contains(':8000')) {
        try {
          final fallbackUri = Uri.parse('http://localhost:8000/api/exams.php');
          final response = await http.get(fallbackUri).timeout(const Duration(seconds: 4));
          if (response.statusCode == 200) {
            baseUrl = 'http://localhost:8000/api';
            final data = json.decode(utf8.decode(response.bodyBytes));
            if (data['status'] == 'success') {
              final List list = data['data'] ?? [];
              return list.map((e) => Exam.fromJson(e)).toList();
            }
          }
        } catch (_) {}
      }
      throw Exception('Lỗi kết nối máy chủ backend: $e\nVui lòng đảm bảo Apache/MySQL trong XAMPP đang bật.');
    }
  }

  /// Lấy chi tiết đề thi gồm danh sách câu hỏi và đoạn văn (GET /exam-detail.php?id=...)
  static Future<Map<String, dynamic>> fetchExamDetail(int examId) async {
    final uri = Uri.parse('$baseUrl/exam-detail.php?id=$examId');
    try {
      final response = await http.get(uri).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final data = json.decode(utf8.decode(response.bodyBytes));
        if (data['status'] == 'success') {
          final examInfo = Exam.fromJson(data['exam']);
          final rawPassages = data['passages'] as List<dynamic>? ?? [];
          final passages = rawPassages.map((p) => Passage.fromJson(p)).toList();

          final rawQuestions = data['questions'] as List<dynamic>? ?? [];
          final questions = rawQuestions.map((q) => Question.fromJson(q)).toList();

          return {
            'exam': examInfo,
            'passages': passages,
            'questions': questions,
          };
        }
      }
      throw Exception('Lỗi nạp chi tiết đề thi: ${response.body}');
    } catch (e) {
      throw Exception('Lỗi nạp câu hỏi: $e');
    }
  }

  /// Nộp bài thi và nhận kết quả chấm tự động (POST /submit.php)
  static Future<ExamResult> submitExam({
    required int examId,
    required String candidateName,
    required int timeSpentSeconds,
    required Map<int, String> answers, // Map [MaCauHoi => KyHieu A/B/C/D]
  }) async {
    final uri = Uri.parse('$baseUrl/submit.php');

    final List<Map<String, dynamic>> answersList = [];
    answers.forEach((qId, symbol) {
      if (symbol.isNotEmpty) {
        answersList.add({
          'question_id': qId,
          'choice_symbol': symbol,
        });
      }
    });

    final payload = {
      'exam_id': examId,
      'candidate_name': candidateName,
      'time_spent_seconds': timeSpentSeconds,
      'answers': answersList,
    };

    try {
      final response = await http.post(
        uri,
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          'Accept': 'application/json',
        },
        body: json.encode(payload),
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final data = json.decode(utf8.decode(response.bodyBytes));
        if (data['status'] == 'success') {
          return ExamResult.fromJson(data);
        } else {
          throw Exception(data['message'] ?? 'Lỗi không xác định khi chấm bài');
        }
      }
      throw Exception('Lỗi server khi nộp bài (${response.statusCode}): ${response.body}');
    } catch (e) {
      throw Exception('Lỗi nộp bài thi: $e');
    }
  }
}
