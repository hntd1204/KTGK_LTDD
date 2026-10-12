import 'dart:async';
import 'package:flutter/material.dart';
import '../models/passage.dart';
import '../models/question.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'exam_result_screen.dart';

class ExamTakingScreen extends StatefulWidget {
  final int examId;
  final String examTitle;
  final int durationMinutes;
  final String candidateName;

  const ExamTakingScreen({
    super.key,
    required this.examId,
    required this.examTitle,
    required this.durationMinutes,
    required this.candidateName,
  });

  @override
  State<ExamTakingScreen> createState() => _ExamTakingScreenState();
}

class _ExamTakingScreenState extends State<ExamTakingScreen> {
  bool _isLoading = true;
  String? _errorMessage;

  List<Question> _questions = [];
  Map<int, Passage> _passagesMap = {};

  // Lưu đáp án tạm thời của thí sinh: [MaCauHoi => 'A'|'B'|'C'|'D']
  final Map<int, String> _userAnswers = {};

  // Danh sách các câu được thí sinh đánh dấu xem lại (Bookmark / Flag)
  final Set<int> _flaggedQuestions = {};

  int _currentIndex = 0;
  int _remainingSeconds = 0;
  Timer? _timer;
  bool _isLocked = false;
  bool _warned5Min = false;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _remainingSeconds = widget.durationMinutes * 60;
    _loadExamData();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _loadExamData() async {
    try {
      final data = await ApiService.fetchExamDetail(widget.examId);
      final List<Passage> passages = data['passages'] ?? [];
      final List<Question> questions = data['questions'] ?? [];

      if (!mounted) return;

      setState(() {
        _questions = questions;
        _passagesMap = {for (var p in passages) p.id: p};
        _isLoading = false;
      });

      _startTimer();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds <= 1) {
        timer.cancel();
        setState(() {
          _remainingSeconds = 0;
          _isLocked = true;
        });
        _handleTimeExpired();
      } else {
        setState(() {
          _remainingSeconds--;
        });

        if (_remainingSeconds == 300 && !_warned5Min) {
          _warned5Min = true;
          _show5MinWarningDialog();
        }
      }
    });
  }

  void _show5MinWarningDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        icon: const Icon(Icons.warning_amber_rounded, color: AppTheme.warning, size: 44),
        title: const Text('Thời gian sắp hết!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
        content: const Text(
          'Thời gian làm bài chỉ còn dưới 5 phút!\nVui lòng kiểm tra lại các câu chưa làm và chuẩn bị nộp bài.',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 13),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primary),
            child: const Text('Đã hiểu, tiếp tục làm bài'),
          ),
        ],
      ),
    );
  }

  void _handleTimeExpired() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        icon: const Icon(Icons.alarm_off_rounded, color: AppTheme.danger, size: 48),
        title: const Text('HẾT THỜI GIAN LÀM BÀI', style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.danger)),
        content: const Text(
          'Thời gian 75 phút đã kết thúc!\nQuyền chọn đáp án đã bị khóa.\nHệ thống đang tự động thu bài và chấm điểm.',
          textAlign: TextAlign.center,
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _submitExam(autoSubmit: true);
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger),
            child: const Text('Xem Kết Quả Thi Ngay'),
          ),
        ],
      ),
    );
  }

  String _formatTime(int seconds) {
    int m = seconds ~/ 60;
    int s = seconds % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  void _selectChoice(int questionId, String symbol) {
    if (_isLocked) return;
    setState(() {
      if (_userAnswers[questionId] == symbol) {
        _userAnswers.remove(questionId);
      } else {
        _userAnswers[questionId] = symbol;
      }
    });
  }

  void _toggleFlag(int questionId) {
    setState(() {
      if (_flaggedQuestions.contains(questionId)) {
        _flaggedQuestions.remove(questionId);
      } else {
        _flaggedQuestions.add(questionId);
      }
    });
  }

  // Chuyển sang Part 5, 6, 7
  void _jumpToPart(int partNum) {
    for (int i = 0; i < _questions.length; i++) {
      if (_questions[i].part == partNum) {
        setState(() {
          _currentIndex = i;
        });
        break;
      }
    }
  }

  int _getCurrentPart() {
    if (_questions.isEmpty || _currentIndex >= _questions.length) return 5;
    return _questions[_currentIndex].part;
  }

  // Modal Xác Nhận Nộp Bài (Figma Screen 7)
  void _confirmSubmit() {
    int total = _questions.length;
    int answered = _userAnswers.length;
    int unanswered = total - answered;
    int flagged = _flaggedQuestions.length;

    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        backgroundColor: Colors.white,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Icon máy bay giấy trong vòng tròn xanh (Figma)
              Container(
                width: 52,
                height: 52,
                decoration: const BoxDecoration(
                  color: AppTheme.primaryLight,
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Icon(Icons.send_rounded, color: AppTheme.primary, size: 24),
                ),
              ),
              const SizedBox(height: 14),

              const Text(
                'Bạn muốn nộp bài?',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.textDark,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${widget.examTitle}\nHãy kiểm tra trước khi hoàn tất.',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 12, color: AppTheme.textMuted, height: 1.3),
              ),
              const SizedBox(height: 18),

              // Thống kê: Đã trả lời, Chưa làm, Đánh dấu xem lại
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppTheme.borderLight),
                ),
                child: Column(
                  children: [
                    _buildModalStatRow(
                      Icons.check_circle_rounded,
                      AppTheme.primary,
                      'Đã trả lời',
                      '$answered/$total',
                      isBold: true,
                    ),
                    const Divider(height: 16, color: AppTheme.borderLight),
                    _buildModalStatRow(
                      Icons.check_box_outline_blank_rounded,
                      unanswered > 0 ? AppTheme.warning : AppTheme.textMuted,
                      'Chưa làm',
                      '$unanswered câu',
                      valueColor: unanswered > 0 ? AppTheme.warning : AppTheme.textMuted,
                    ),
                    if (flagged > 0) ...[
                      const Divider(height: 16, color: AppTheme.borderLight),
                      _buildModalStatRow(
                        Icons.star_rounded,
                        AppTheme.warning,
                        'Đánh dấu xem lại',
                        '$flagged câu',
                        valueColor: AppTheme.warning,
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // Cảnh báo màu vàng (Figma)
              if (unanswered > 0)
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.warningLight,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppTheme.warning.withValues(alpha: 0.3)),
                  ),
                  child: Text(
                    'Sau khi nộp, bạn không thể sửa đáp án. $unanswered câu chưa làm sẽ được tính là bỏ qua.',
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFFB45309),
                      height: 1.35,
                    ),
                  ),
                ),
              const SizedBox(height: 20),

              // Hai nút hành động
              Column(
                children: [
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(ctx),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Quay lại làm bài', style: TextStyle(fontWeight: FontWeight.w600)),
                    ),
                  ),
                  const SizedBox(height: 10),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.pop(ctx);
                        _submitExam(autoSubmit: false);
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.primary,
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Xác nhận nộp bài', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildModalStatRow(IconData icon, Color iconColor, String label, String value, {bool isBold = false, Color? valueColor}) {
    return Row(
      children: [
        Icon(icon, size: 16, color: iconColor),
        const SizedBox(width: 8),
        Text(label, style: const TextStyle(fontSize: 13, color: Color(0xFF334155))),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: isBold ? FontWeight.w800 : FontWeight.w600,
            color: valueColor ?? AppTheme.textDark,
          ),
        ),
      ],
    );
  }

  Future<void> _submitExam({bool autoSubmit = false}) async {
    setState(() {
      _isSubmitting = true;
    });

    try {
      int totalSeconds = widget.durationMinutes * 60;
      int timeSpent = totalSeconds - _remainingSeconds;

      final result = await ApiService.submitExam(
        examId: widget.examId,
        candidateName: widget.candidateName,
        timeSpentSeconds: timeSpent,
        answers: _userAnswers,
      );

      _timer?.cancel();

      if (!mounted) return;

      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (context) => ExamResultScreen(
            result: result,
            passagesMap: _passagesMap,
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isSubmitting = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Lỗi nộp bài: $e'),
          backgroundColor: AppTheme.danger,
        ),
      );
    }
  }

  // BottomSheet Bảng danh sách câu hỏi 1-100 (Figma)
  void _showQuestionPalette() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.65,
        minChildSize: 0.4,
        maxChildSize: 0.9,
        expand: false,
        builder: (_, scrollController) => Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppTheme.borderLight,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(height: 14),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Sơ đồ câu hỏi',
                    style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppTheme.textDark),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryLight,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '${_userAnswers.length}/${_questions.length} câu',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.primary),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              // Chú thích
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _buildLegend(AppTheme.primary, 'Đã làm'),
                  const SizedBox(width: 14),
                  _buildLegend(Colors.white, 'Chưa làm', borderColor: AppTheme.borderLight),
                  const SizedBox(width: 14),
                  _buildLegend(AppTheme.warning, 'Đánh dấu xem lại'),
                ],
              ),
              const SizedBox(height: 12),
              const Divider(height: 1, color: AppTheme.borderLight),
              const SizedBox(height: 12),
              Expanded(
                child: GridView.builder(
                  controller: scrollController,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 6,
                    mainAxisSpacing: 8,
                    crossAxisSpacing: 8,
                    childAspectRatio: 1.1,
                  ),
                  itemCount: _questions.length,
                  itemBuilder: (context, idx) {
                    final q = _questions[idx];
                    final isAnswered = _userAnswers.containsKey(q.id);
                    final isCurrent = idx == _currentIndex;
                    final isFlagged = _flaggedQuestions.contains(q.id);

                    Color bg = Colors.white;
                    Color textCol = AppTheme.textDark;
                    Border border = Border.all(color: AppTheme.borderLight);

                    if (isCurrent) {
                      bg = AppTheme.primary;
                      textCol = Colors.white;
                      border = Border.all(color: AppTheme.primaryDark, width: 2);
                    } else if (isFlagged) {
                      bg = AppTheme.warningLight;
                      textCol = const Color(0xFFB45309);
                      border = Border.all(color: AppTheme.warning);
                    } else if (isAnswered) {
                      bg = AppTheme.primaryLight;
                      textCol = AppTheme.primary;
                      border = Border.all(color: AppTheme.primaryBorder);
                    }

                    return InkWell(
                      onTap: () {
                        setState(() {
                          _currentIndex = idx;
                        });
                        Navigator.pop(ctx);
                      },
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        decoration: BoxDecoration(
                          color: bg,
                          border: border,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Center(
                          child: Text(
                            '${q.questionNumber > 0 ? q.questionNumber : idx + 1}',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 12,
                              color: textCol,
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLegend(Color color, String text, {Color? borderColor}) {
    return Row(
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            border: Border.all(color: borderColor ?? color),
            borderRadius: BorderRadius.circular(3),
          ),
        ),
        const SizedBox(width: 4),
        Text(text, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted)),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Bài thi Reading')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_errorMessage != null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Lỗi')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, color: AppTheme.danger, size: 48),
                const SizedBox(height: 12),
                Text(_errorMessage!, textAlign: TextAlign.center),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Quay lại'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (_questions.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('Bài thi Reading')),
        body: const Center(child: Text('Đề thi này không có câu hỏi nào.')),
      );
    }

    final currentQ = _questions[_currentIndex];
    final passage = currentQ.passageId != null ? _passagesMap[currentQ.passageId!] : null;
    final isLastQuestion = _currentIndex == _questions.length - 1;
    final currentPart = _getCurrentPart();
    final isFlagged = _flaggedQuestions.contains(currentQ.id);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Bài thi Reading',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: AppTheme.textDark),
            ),
            Text(
              widget.examTitle,
              style: const TextStyle(fontSize: 11, color: AppTheme.textMuted, fontWeight: FontWeight.normal),
            ),
          ],
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_rounded, size: 18),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          // Đồng hồ đếm ngược (Figma: chip xanh nhạt góc phải)
          Container(
            margin: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: _remainingSeconds <= 300 ? AppTheme.dangerLight : AppTheme.primaryLight,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: _remainingSeconds <= 300 ? AppTheme.danger : AppTheme.primaryBorder,
              ),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.alarm_rounded,
                  size: 14,
                  color: _remainingSeconds <= 300 ? AppTheme.danger : AppTheme.primary,
                ),
                const SizedBox(width: 4),
                Text(
                  _formatTime(_remainingSeconds),
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: _remainingSeconds <= 300 ? AppTheme.danger : AppTheme.primary,
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.more_horiz_rounded),
            onPressed: () {},
          ),
        ],
      ),
      body: _isSubmitting
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // 1. Part Tabs: [Part 5] | [Part 6] | [Part 7] (Figma)
                _buildPartTabs(currentPart),

                // 2. Sub-bar: "Đã trả lời X/100 câu" • "Nộp bài"
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Đã trả lời ${_userAnswers.length}/${_questions.length} câu',
                        style: const TextStyle(fontSize: 12, color: AppTheme.textMuted, fontWeight: FontWeight.w500),
                      ),
                      GestureDetector(
                        onTap: _confirmSubmit,
                        child: const Text(
                          'Nộp bài',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.primary,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1, color: AppTheme.borderLight),

                // 3. Nội dung bài đọc & câu hỏi
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    children: [
                      // Tag phân đoạn (Figma: chip xanh nhạt)
                      _buildSectionTag(currentQ, passage),
                      const SizedBox(height: 12),

                      // Nếu có bài đọc (Part 6 hoặc Part 7)
                      if (passage != null) _buildFigmaPassageCard(passage),

                      // Khối câu hỏi chính
                      _buildFigmaQuestionSection(currentQ, isFlagged),

                      const SizedBox(height: 40),
                    ],
                  ),
                ),

                // 4. Thanh điều hướng trước/sau & dải sơ đồ câu hỏi (Figma)
                _buildFigmaBottomBar(isLastQuestion),
              ],
            ),
    );
  }

  Widget _buildPartTabs(int activePart) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          _buildPartPill(5, 'Part 5', activePart == 5),
          const SizedBox(width: 8),
          _buildPartPill(6, 'Part 6', activePart == 6),
          const SizedBox(width: 8),
          _buildPartPill(7, 'Part 7', activePart == 7),
        ],
      ),
    );
  }

  Widget _buildPartPill(int partNum, String label, bool isActive) {
    return Expanded(
      child: GestureDetector(
        onTap: () => _jumpToPart(partNum),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isActive ? AppTheme.primary : const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Center(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: isActive ? Colors.white : AppTheme.textMuted,
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTag(Question q, Passage? passage) {
    String tagText = 'Hoàn thành câu • Câu 101-130';
    if (q.part == 6) {
      tagText = 'Hoàn thành đoạn văn • Câu 131-146';
    } else if (q.part == 7) {
      tagText = '${passage?.type ?? "Đọc hiểu"} • Câu 147-200';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.primaryLight,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.bookmark_outline_rounded, size: 14, color: AppTheme.primary),
          const SizedBox(width: 6),
          Text(
            tagText,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppTheme.primary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFigmaPassageCard(Passage passage) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (passage.type != null) ...[
            Text(
              '${passage.type}:',
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 13,
                color: AppTheme.textDark,
              ),
            ),
            const SizedBox(height: 8),
          ],
          SelectableText(
            passage.content,
            style: const TextStyle(
              fontSize: 13,
              height: 1.5,
              color: Color(0xFF334155),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFigmaQuestionSection(Question q, bool isFlagged) {
    final selectedSymbol = _userAnswers[q.id];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Dòng tiêu đề câu + Nút Đánh dấu xem lại
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Câu ${q.questionNumber > 0 ? q.questionNumber : (_currentIndex + 1)}',
              style: const TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w800,
                color: AppTheme.textDark,
              ),
            ),
            GestureDetector(
              onTap: () => _toggleFlag(q.id),
              child: Row(
                children: [
                  Icon(
                    isFlagged ? Icons.star_rounded : Icons.star_border_rounded,
                    size: 16,
                    color: isFlagged ? AppTheme.warning : AppTheme.textMuted,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    isFlagged ? 'Đã đánh dấu' : 'Đánh dấu xem lại',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: isFlagged ? AppTheme.warning : AppTheme.textMuted,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),

        // Hướng dẫn nhỏ
        Text(
          q.part == 6
              ? 'Chọn đáp án phù hợp với chỗ trống [${q.questionNumber}].'
              : 'Chọn từ phù hợp nhất để hoàn thành câu.',
          style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
        ),
        const SizedBox(height: 12),

        // Nội dung câu hỏi
        if (q.content.trim().isNotEmpty) ...[
          SelectableText(
            q.content,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: AppTheme.textDark,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 16),
        ],

        // Danh sách 4 đáp án A, B, C, D (Figma: thẻ bo góc, khi chọn viền xanh và tick icon)
        Column(
          children: q.choices.map((choice) {
            final isChosen = selectedSymbol == choice.symbol;

            return Container(
              margin: const EdgeInsets.only(bottom: 10),
              decoration: BoxDecoration(
                color: isChosen ? AppTheme.primaryLight : Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isChosen ? AppTheme.primary : AppTheme.borderLight,
                  width: isChosen ? 1.8 : 1,
                ),
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: _isLocked ? null : () => _selectChoice(q.id, choice.symbol),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    child: Row(
                      children: [
                        // Ký hiệu A, B, C, D
                        Container(
                          width: 28,
                          height: 28,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: isChosen ? AppTheme.primary : const Color(0xFFF1F5F9),
                          ),
                          child: Center(
                            child: Text(
                              choice.symbol,
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                color: isChosen ? Colors.white : AppTheme.textDark,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            choice.content,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: isChosen ? FontWeight.w700 : FontWeight.w500,
                              color: isChosen ? AppTheme.primaryDark : AppTheme.textDark,
                            ),
                          ),
                        ),
                        if (isChosen)
                          const Icon(Icons.check_rounded, color: AppTheme.primary, size: 20),
                      ],
                    ),
                  ),
                ),
              ),
            );
          }).toList(),
        ),

        // Helper text báo trạng thái đã chọn (Figma)
        if (selectedSymbol != null) ...[
          const SizedBox(height: 4),
          Row(
            children: [
              const Icon(Icons.check_circle_outline_rounded, size: 14, color: AppTheme.primary),
              const SizedBox(width: 6),
              Text(
                '✓ Đã chọn $selectedSymbol • Có thể đổi trước khi nộp',
                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.primary),
              ),
            ],
          ),
        ],
      ],
    );
  }

  Widget _buildFigmaBottomBar(bool isLastQuestion) {
    // Lấy dải 6 câu hỏi gần câu hiện tại để hiển thị mini strip
    int startIdx = (_currentIndex ~/ 6) * 6;
    int endIdx = (startIdx + 6).clamp(0, _questions.length);
    List<Question> stripQuestions = _questions.sublist(startIdx, endIdx);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: AppTheme.borderLight)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            // Nút điều hướng Trước / Sau
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _currentIndex > 0
                        ? () {
                            setState(() {
                              _currentIndex--;
                            });
                          }
                        : null,
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: const Text('← Trước', style: TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: !isLastQuestion
                        ? () {
                            setState(() {
                              _currentIndex++;
                            });
                          }
                        : null,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primary,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: const Text('Câu sau →', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Sơ đồ câu strip (Figma)
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Sơ đồ câu • ${stripQuestions.first.questionNumber}-${stripQuestions.last.questionNumber}',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppTheme.textMuted),
                ),
                GestureDetector(
                  onTap: _showQuestionPalette,
                  child: const Icon(Icons.grid_view_rounded, size: 16, color: AppTheme.primary),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Dải câu hỏi mini strip
            Row(
              children: stripQuestions.map((q) {
                int qIdx = _questions.indexOf(q);
                bool isCurrent = qIdx == _currentIndex;
                bool isAnswered = _userAnswers.containsKey(q.id);
                bool isFlag = _flaggedQuestions.contains(q.id);

                Color bg = const Color(0xFFF8FAFC);
                Color textCol = AppTheme.textDark;
                Border border = Border.all(color: AppTheme.borderLight);

                if (isCurrent) {
                  bg = AppTheme.primary;
                  textCol = Colors.white;
                  border = Border.all(color: AppTheme.primaryDark);
                } else if (isFlag) {
                  bg = AppTheme.warningLight;
                  textCol = const Color(0xFFB45309);
                  border = Border.all(color: AppTheme.warning);
                } else if (isAnswered) {
                  bg = AppTheme.primaryLight;
                  textCol = AppTheme.primary;
                  border = Border.all(color: AppTheme.primaryBorder);
                }

                return Expanded(
                  child: GestureDetector(
                    onTap: () {
                      setState(() {
                        _currentIndex = qIdx;
                      });
                    },
                    child: Container(
                      margin: const EdgeInsets.symmetric(horizontal: 2),
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      decoration: BoxDecoration(
                        color: bg,
                        border: border,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Center(
                        child: Text(
                          isFlag ? '★ ${q.questionNumber}' : '${q.questionNumber}',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: textCol,
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }
}
