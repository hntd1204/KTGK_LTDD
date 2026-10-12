import 'package:flutter/material.dart';
import 'screens/exam_list_screen.dart';
import 'theme/app_theme.dart';

void main() {
  runApp(const ToeicMobileApp());
}

class ToeicMobileApp extends StatelessWidget {
  const ToeicMobileApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'TOEIC Reading Master',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const ExamListScreen(),
    );
  }
}
