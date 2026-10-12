import 'package:flutter/material.dart';
import '../models/exam.dart';
import '../theme/app_theme.dart';
import 'exam_taking_screen.dart';

class ExamInstructionScreen extends StatelessWidget {
  final Exam exam;
  final String candidateName;

  const ExamInstructionScreen({
    super.key,
    required this.exam,
    required this.candidateName,
  });

  @override
  Widget build(BuildContext context) {
    int totalQuestions = exam.actualQuestions > 0 ? exam.actualQuestions : exam.totalQuestions;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Hướng dẫn làm bài'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_rounded, size: 18),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.more_horiz_rounded),
            onPressed: () {},
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                children: [
                  // Card tóm tắt đề thi
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryLight,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppTheme.primaryBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          exam.title,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: AppTheme.textDark,
                          ),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(color: AppTheme.primaryBorder),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.timer_outlined, size: 14, color: AppTheme.primary),
                                  const SizedBox(width: 4),
                                  Text(
                                    '⏱ ${exam.durationMinutes} phút - 75:00',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: AppTheme.primary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 10),
                            Text(
                              '$totalQuestions câu hỏi',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppTheme.primary,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Cấu trúc bài thi
                  const Text(
                    'Cấu trúc bài thi',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.textDark,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _buildPartCard('Part 5', 'Hoàn thành câu', 'Câu 101-130 • 30 câu'),
                  const SizedBox(height: 10),
                  _buildPartCard('Part 6', 'Hoàn thành đoạn văn', 'Câu 131-146 • 16 câu'),
                  const SizedBox(height: 10),
                  _buildPartCard('Part 7', 'Đọc hiểu', 'Câu 147-200 • 54 câu'),

                  const SizedBox(height: 24),

                  // Trước khi bắt đầu
                  const Text(
                    'Trước khi bắt đầu',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.textDark,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _buildRuleItem(
                    Icons.check_circle_outline_rounded,
                    'Chọn một đáp án A-D cho mỗi câu. Bạn có thể đổi hoặc hủy lựa chọn trước khi nộp.',
                  ),
                  _buildRuleItem(
                    Icons.bookmark_border_rounded,
                    'Đánh dấu xem lại: mở sơ đồ câu để tìm câu chưa làm (□) hoặc câu cần rà soát lại (★).',
                  ),
                  _buildRuleItem(
                    Icons.alarm_rounded,
                    'Đồng hồ đếm ngược từ 75:00. Bài được tự động thu và khóa quyền chọn khi hết thời gian.',
                  ),
                  _buildRuleItem(
                    Icons.send_outlined,
                    'Chọn Nộp bài để kiểm tra tổng số câu. Sau khi nộp, hệ thống chấm điểm và không thể sửa đáp án.',
                  ),
                ],
              ),
            ),

            // Nút Bắt đầu làm bài
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(
                        builder: (context) => ExamTakingScreen(
                          examId: exam.id,
                          examTitle: exam.title,
                          durationMinutes: exam.durationMinutes,
                          candidateName: candidateName,
                        ),
                      ),
                    );
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Bắt đầu làm bài',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      SizedBox(width: 8),
                      Icon(Icons.arrow_forward_rounded, size: 20),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPartCard(String partCode, String partName, String detail) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: AppTheme.primaryLight,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              partCode,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 12,
                color: AppTheme.primary,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  partName,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    color: AppTheme.textDark,
                  ),
                ),
                Text(
                  detail,
                  style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRuleItem(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppTheme.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                fontSize: 13,
                height: 1.45,
                color: Color(0xFF334155),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
