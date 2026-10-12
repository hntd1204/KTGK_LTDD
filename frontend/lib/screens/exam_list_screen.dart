import 'package:flutter/material.dart';
import '../models/exam.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'exam_instruction_screen.dart';

class ExamListScreen extends StatefulWidget {
  const ExamListScreen({super.key});

  @override
  State<ExamListScreen> createState() => _ExamListScreenState();
}

class _ExamListScreenState extends State<ExamListScreen> {
  late Future<List<Exam>> _examsFuture;
  final TextEditingController _searchController = TextEditingController();
  final TextEditingController _nameController = TextEditingController(text: 'Huỳnh Nguyễn Thành Đạt');
  String _activeTab = 'ALL'; // 'ALL', 'DONE'
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _loadExams();
  }

  void _loadExams() {
    setState(() {
      _examsFuture = ApiService.fetchExams();
    });
  }

  void _showApiConfigDialog() {
    final controller = TextEditingController(text: ApiService.baseUrl);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Cấu hình Backend API', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Địa chỉ API kết nối cơ sở dữ liệu MySQL (XAMPP):',
              style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: InputDecoration(
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                hintText: 'http://localhost/KTGK_LTDD/backend/api',
                isDense: true,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              '• macOS / Chrome Web: http://localhost/KTGK_LTDD/backend/api\n• Android Emulator: http://10.0.2.2/KTGK_LTDD/backend/api',
              style: TextStyle(fontSize: 11, color: AppTheme.textMuted),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Hủy'),
          ),
          ElevatedButton(
            onPressed: () {
              if (controller.text.trim().isNotEmpty) {
                ApiService.setBaseUrl(controller.text.trim());
                Navigator.pop(ctx);
                _loadExams();
              }
            },
            child: const Text('Lưu'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AppTheme.primaryLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.menu_book_rounded, color: AppTheme.primary, size: 18),
            ),
            const SizedBox(width: 8),
            const Text(
              'TOEIC READING',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 16,
                letterSpacing: 0.5,
                color: AppTheme.textDark,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined, color: AppTheme.textMuted, size: 20),
            tooltip: 'Cấu hình API',
            onPressed: _showApiConfigDialog,
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppTheme.primary, size: 20),
            tooltip: 'Làm mới',
            onPressed: _loadExams,
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => _loadExams(),
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          children: [
            // Tiêu đề & Lời chào
            const Text(
              'Sẵn sàng luyện đọc?',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w900,
                color: AppTheme.textDark,
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Chọn một đề và bắt đầu hành trình của bạn.',
              style: TextStyle(
                fontSize: 13,
                color: AppTheme.textMuted,
              ),
            ),
            const SizedBox(height: 16),

            // Thanh tìm kiếm
            Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppTheme.borderLight),
              ),
              child: TextField(
                controller: _searchController,
                onChanged: (val) {
                  setState(() {
                    _searchQuery = val.trim().toLowerCase();
                  });
                },
                decoration: const InputDecoration(
                  icon: Padding(
                    padding: EdgeInsets.only(left: 12),
                    child: Icon(Icons.search_rounded, color: AppTheme.textLight, size: 20),
                  ),
                  border: InputBorder.none,
                  hintText: 'Tìm kiếm đề thi...',
                  hintStyle: TextStyle(fontSize: 13, color: AppTheme.textLight),
                  contentPadding: EdgeInsets.symmetric(vertical: 12),
                  isDense: true,
                ),
              ),
            ),
            const SizedBox(height: 14),

            // Tab lọc: Tất cả | Đã làm
            Row(
              children: [
                _buildPillTab('ALL', 'Tất cả'),
                const SizedBox(width: 10),
                _buildPillTab('DONE', 'Đã làm'),
              ],
            ),
            const SizedBox(height: 20),

            // Subheader: "X đề thi Reading" • "Mới nhất ▾"
            FutureBuilder<List<Exam>>(
              future: _examsFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(vertical: 40),
                    child: Center(
                      child: CircularProgressIndicator(),
                    ),
                  );
                }

                if (snapshot.hasError) {
                  return _buildErrorCard(snapshot.error.toString());
                }

                final allExams = snapshot.data ?? [];
                final filteredExams = allExams.where((e) {
                  if (_searchQuery.isNotEmpty && !e.title.toLowerCase().contains(_searchQuery)) {
                    return false;
                  }
                  return true;
                }).toList();

                return Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${filteredExams.length} đề thi Reading',
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.textDark,
                          ),
                        ),
                        const Row(
                          children: [
                            Text(
                              'Mới nhất',
                              style: TextStyle(fontSize: 12, color: AppTheme.textMuted, fontWeight: FontWeight.w600),
                            ),
                            Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: AppTheme.textMuted),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    if (filteredExams.isEmpty)
                      _buildEmptyCard()
                    else
                      ...filteredExams.map((exam) => _buildFigmaExamCard(exam)),
                  ],
                );
              },
            ),

            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildPillTab(String key, String label) {
    final isActive = _activeTab == key;
    return GestureDetector(
      onTap: () {
        setState(() {
          _activeTab = key;
        });
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
        decoration: BoxDecoration(
          color: isActive ? AppTheme.primary : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: isActive ? Colors.white : AppTheme.textMuted,
          ),
        ),
      ),
    );
  }

  Widget _buildFigmaExamCard(Exam exam) {
    final candidateName = _nameController.text.trim().isNotEmpty
        ? _nameController.text.trim()
        : 'Thí sinh';
    final totalQ = exam.actualQuestions > 0 ? exam.actualQuestions : exam.totalQuestions;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.borderLight),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Icon tài liệu màu tím/xanh
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppTheme.primaryLight,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Center(
              child: Icon(Icons.description_outlined, color: AppTheme.primary, size: 22),
            ),
          ),
          const SizedBox(width: 14),

          // Tên đề & Thông tin
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  exam.title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.textDark,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.timer_outlined, size: 12, color: AppTheme.textMuted),
                    const SizedBox(width: 3),
                    Text(
                      '${exam.durationMinutes} phút',
                      style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                    ),
                    const SizedBox(width: 8),
                    const Text('•', style: TextStyle(color: AppTheme.textMuted, fontSize: 10)),
                    const SizedBox(width: 8),
                    Text(
                      '$totalQ câu',
                      style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                // Badge Chưa làm
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    'Chưa làm',
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: AppTheme.textMuted),
                  ),
                ),
              ],
            ),
          ),

          // Nút Làm bài (Solid Blue Pill)
          ElevatedButton(
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ExamInstructionScreen(
                    exam: exam,
                    candidateName: candidateName,
                  ),
                ),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primary,
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              elevation: 0,
            ),
            child: const Text(
              'Làm bài',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorCard(String error) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.dangerLight,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.danger.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          const Icon(Icons.wifi_off_rounded, color: AppTheme.danger, size: 36),
          const SizedBox(height: 10),
          const Text(
            'Không thể kết nối Backend MySQL',
            style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.danger, fontSize: 14),
          ),
          const SizedBox(height: 6),
          Text(
            error,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 11, color: AppTheme.textDark),
          ),
          const SizedBox(height: 12),
          ElevatedButton(
            onPressed: _showApiConfigDialog,
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger),
            child: const Text('Cấu hình lại API URL', style: TextStyle(fontSize: 12)),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyCard() {
    return Container(
      padding: const EdgeInsets.all(30),
      child: const Center(
        child: Column(
          children: [
            Icon(Icons.folder_open_rounded, size: 40, color: AppTheme.textLight),
            SizedBox(height: 8),
            Text(
              'Chưa có đề thi nào trong CSDL',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }
}
