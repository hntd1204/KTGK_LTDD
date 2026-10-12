import 'package:flutter_test/flutter_test.dart';
import 'package:toeic_mobile_app/main.dart';

void main() {
  testWidgets('App smoke test - verifies title renders', (WidgetTester tester) async {
    await tester.pumpWidget(const ToeicMobileApp());
    expect(find.text('TOEIC READING'), findsOneWidget);
  });
}
