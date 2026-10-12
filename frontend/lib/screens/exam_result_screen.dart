import 'package:flutter/material.dart';
import '../models/exam_result.dart';
import '../models/passage.dart';
import '../theme/app_theme.dart';
import 'exam_list_screen.dart';

class ExamResultScreen extends StatefulWidget {
  final ExamResult result;
  final Map<int, Passage> passagesMap;

  const ExamResultScreen({
    super.key,
    required this.result,
    required this.passagesMap,
  });

  @override
  State<ExamResultScreen> createState() => _ExamResultScreenState();
}

class _ExamResultScreenState extends State<ExamResultScreen> {
  String _filter = 'ALL'; // 'ALL', 'CORRECT', 'WRONG', 'SKIPPED'

  @override
  Widget build(BuildContext context) {
    final r = widget.result;

    final filteredDetails = r.details.where((d) {
      if (_filter == 'CORRECT') return d.isCorrect;
      if (_filter == 'WRONG') return !d.isCorrect && !d.isSkipped;
      if (_filter == 'SKIPPED') return d.isSkipped;
      return true;
    }).toList();

    // Phân tích thống kê theo Part 5, 6, 7 (Figma)
    int p5Total = 0, p5Correct = 0, p5Wrong = 0, p5Skipped = 0;
    int p6Total = 0, p6Correct = 0, p6Wrong = 0, p6Skipped = 0;
    int p7Total = 0, p7Correct = 0, p7Wrong = 0, p7Skipped = 0;

    for (var d in r.details) {
      if (d.part == 5) {
        p5Total++;
        if (d.isCorrect) {
          p5Correct++;
        } else if (d.isSkipped) {
          p5Skipped++;
        } else {
          p5Wrong++;
        }
      } else if (d.part == 6) {
        p6Total++;
        if (d.isCorrect) {
          p6Correct++;
        } else if (d.isSkipped) {
          p6Skipped++;
        } else {
          p6Wrong++;
        }
      } else {
        p7Total++;
        if (d.isCorrect) {
          p7Correct++;
        } else if (d.isSkipped) {
          p7Skipped++;
        } else {
          p7Wrong++;
        }
      }
    }

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('Kết quả bài thi'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_rounded, size: 18),
          onPressed: () {
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (context) => const ExamListScreen()),
              (route) => false,
            );
          },
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.more_horiz_rounded),
            onPressed: () {},
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        children: [
          // 1. Trophy & Tiêu đề Hoàn thành xuất sắc! (Figma)
          Center(
            child: Column(
              children: [
                Container(
                  width: 56,
                  height: 56,
                  decoration: const BoxDecoration(
                    color: AppTheme.primaryLight,
                    shape: BoxShape.circle,
                  ),
                  child: const Center(
                    child: Icon(Icons.emoji_events_outlined, color: AppTheme.primary, size: 28),
                  ),
                ),
                const SizedBox(height: 12),
                const Text(
                  'Hoàn thành xuất sắc!',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    color: AppTheme.textDark,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  r.examTitle,
                  style: const TextStyle(fontSize: 13, color: AppTheme.textMuted),
                ),
                const SizedBox(height: 16),

                // Điểm số lớn (Figma: 82 / 100)
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      '${r.correctCount}',
                      style: const TextStyle(
                        fontSize: 48,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.primary,
                        letterSpacing: -1,
                      ),
                    ),
                    Text(
                      ' / ${r.totalQuestions}',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.textMuted,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                const Text(
                  'Điểm bài luyện • 1 điểm/câu',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.textDark),
                ),
                Text(
                  '(Quy đổi TOEIC Reading ước tính: ${r.toeicScore} / 495 điểm)',
                  style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // 2. Ba thẻ thống kê: Đúng (Xanh) | Sai (Đỏ) | Bỏ qua (Xám) (Figma)
          Row(
            children: [
              Expanded(
                child: _buildFigmaStatCard('${r.correctCount}', 'Đúng', AppTheme.success, AppTheme.successLight),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildFigmaStatCard('${r.wrongCount}', 'Sai', AppTheme.danger, AppTheme.dangerLight),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildFigmaStatCard('${r.skippedCount}', 'Bỏ qua', AppTheme.textMuted, const Color(0xFFF1F5F9)),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // 3. Tỷ lệ đúng & Thanh tiến trình xếp lớp (Figma)
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppTheme.borderLight),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Tỷ lệ đúng',
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppTheme.textDark),
                    ),
                    Text(
                      '${r.accuracyPercent}%',
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppTheme.primary),
                    ),
                  ],
                ),
                const SizedBox(height: 10),

                // Thanh tiến trình phân đoạn
                ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: Row(
                    children: [
                      if (r.correctCount > 0)
                        Expanded(
                          flex: r.correctCount,
                          child: Container(height: 8, color: AppTheme.success),
                        ),
                      if (r.wrongCount > 0)
                        Expanded(
                          flex: r.wrongCount,
                          child: Container(height: 8, color: AppTheme.danger),
                        ),
                      if (r.skippedCount > 0)
                        Expanded(
                          flex: r.skippedCount,
                          child: Container(height: 8, color: AppTheme.textLight),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  '${r.correctCount} đúng • ${r.wrongCount} sai • ${r.skippedCount} bỏ qua = ${r.totalQuestions} câu',
                  style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // 4. Phân tích theo Part (Figma)
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppTheme.borderLight),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Phân tích theo Part',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppTheme.textDark),
                ),
                const SizedBox(height: 4),
                const Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Part / Số câu', style: TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                    Text('Đúng - Sai - Bỏ qua', style: TextStyle(fontSize: 11, color: AppTheme.textMuted)),
                  ],
                ),
                const Divider(height: 16, color: AppTheme.borderLight),

                _buildPartAnalysisRow('Part 5', '101-130 • $p5Total câu', p5Correct, p5Wrong, p5Skipped, p5Total),
                const SizedBox(height: 10),
                _buildPartAnalysisRow('Part 6', '131-146 • $p6Total câu', p6Correct, p6Wrong, p6Skipped, p6Total),
                const SizedBox(height: 10),
                _buildPartAnalysisRow('Part 7', '147-200 • $p7Total câu', p7Correct, p7Wrong, p7Skipped, p7Total),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // 5. Bộ lọc câu hỏi để xem lại chi tiết
          const Text(
            'Chi tiết từng câu hỏi',
            style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppTheme.textDark),
          ),
          const SizedBox(height: 10),
          _buildFilterChips(r),
          const SizedBox(height: 12),

          // Danh sách chi tiết
          ...filteredDetails.map((d) => _buildDetailCard(d)),

          const SizedBox(height: 24),

          // 6. Nút Quay về danh sách
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (context) => const ExamListScreen()),
                  (route) => false,
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: const Text('Quay về danh sách đề thi', style: TextStyle(fontWeight: FontWeight.bold)),
            ),
          ),
          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildFigmaStatCard(String value, String label, Color color, Color bg) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: color),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: color),
          ),
        ],
      ),
    );
  }

  Widget _buildPartAnalysisRow(String partTitle, String subtitle, int correct, int wrong, int skipped, int total) {
    int percent = total > 0 ? ((correct / total) * 100).toInt() : 0;

    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  partTitle,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.textDark),
                ),
                Text(subtitle, style: const TextStyle(fontSize: 10, color: AppTheme.textMuted)),
              ],
            ),
            Row(
              children: [
                Text(
                  '$correct - $wrong - $skipped',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.textDark),
                ),
                const SizedBox(width: 8),
                Text(
                  '$percent%',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: AppTheme.primary),
                ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 6),
        ClipRRect(
          borderRadius: BorderRadius.circular(3),
          child: LinearProgressIndicator(
            value: total > 0 ? (correct / total) : 0,
            backgroundColor: AppTheme.borderLight,
            color: AppTheme.primary,
            minHeight: 5,
          ),
        ),
      ],
    );
  }

  Widget _buildFilterChips(ExamResult r) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _buildChip('ALL', 'Tất cả (${r.totalQuestions})'),
          const SizedBox(width: 8),
          _buildChip('CORRECT', 'Đúng (${r.correctCount})', activeColor: AppTheme.success),
          const SizedBox(width: 8),
          _buildChip('WRONG', 'Sai (${r.wrongCount})', activeColor: AppTheme.danger),
          const SizedBox(width: 8),
          _buildChip('SKIPPED', 'Bỏ qua (${r.skippedCount})', activeColor: AppTheme.textMuted),
        ],
      ),
    );
  }

  Widget _buildChip(String key, String label, {Color activeColor = AppTheme.primary}) {
    final isSelected = _filter == key;
    return GestureDetector(
      onTap: () {
        setState(() {
          _filter = key;
        });
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? activeColor : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: isSelected ? Colors.white : AppTheme.textMuted,
          ),
        ),
      ),
    );
  }

  Widget _buildDetailCard(QuestionResultDetail d) {
    final passage = d.passageId != null ? widget.passagesMap[d.passageId!] : null;

    Color badgeColor = AppTheme.success;
    String badgeText = 'ĐÚNG';
    if (d.isSkipped) {
      badgeColor = AppTheme.textMuted;
      badgeText = 'BỎ QUA';
    } else if (!d.isCorrect) {
      badgeColor = AppTheme.danger;
      badgeText = 'SAI';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Câu ${d.questionNumber} (Part ${d.part})',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.textDark),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: badgeColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  badgeText,
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: badgeColor),
                ),
              ),
            ],
          ),
          if (passage != null) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                passage.content.length > 180 ? '${passage.content.substring(0, 180)}...' : passage.content,
                style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
              ),
            ),
          ],
          if (d.questionText.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              d.questionText,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppTheme.textDark),
            ),
          ],
          const SizedBox(height: 10),

          // Lựa chọn A, B, C, D
          Column(
            children: d.choices.map((c) {
              final isUserPick = d.selectedSymbol == c.symbol;
              final isCorrectPick = d.correctSymbol == c.symbol;

              Color rowBg = Colors.white;
              Color rowBorder = AppTheme.borderLight;
              Color textCol = AppTheme.textDark;

              if (isCorrectPick) {
                rowBg = AppTheme.successLight;
                rowBorder = AppTheme.success;
                textCol = const Color(0xFF065F46);
              } else if (isUserPick && !d.isCorrect) {
                rowBg = AppTheme.dangerLight;
                rowBorder = AppTheme.danger;
                textCol = const Color(0xFF991B1B);
              }

              return Container(
                margin: const EdgeInsets.only(bottom: 4),
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: rowBg,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: rowBorder),
                ),
                child: Row(
                  children: [
                    Text(
                      '${c.symbol}.',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: textCol),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        c.content,
                        style: TextStyle(fontSize: 12, color: textCol, fontWeight: isCorrectPick ? FontWeight.w700 : FontWeight.normal),
                      ),
                    ),
                    if (isCorrectPick)
                      const Text('✓ Đáp án đúng', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppTheme.success)),
                    if (isUserPick && !d.isCorrect)
                      const Text('✗ Bạn chọn', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppTheme.danger)),
                  ],
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}
