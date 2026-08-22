part2 = fopen('D:/axionyx/lib/features/dashboard/presentation/screens/_part2.dart', 'w');
fwrite($part2, '
  Widget _buildMiddleRow(List salesChart, Map target, List topCustomers, List alerts) {
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Expanded(flex: 3, child: _buildSalesChart(salesChart)),
      const SizedBox(width: 12),
      Expanded(flex: 2, child: _buildTargetGauge(target)),
      const SizedBox(width: 12),
      Expanded(flex: 3, child: _buildTopCustomers(topCustomers)),
      const SizedBox(width: 12),
      Expanded(flex: 3, child: _buildAlerts(alerts)),
    ]);
  }

  Widget _buildSalesChart(List chart) {
    if (chart.isEmpty) return const SizedBox.shrink();
    final actuals = chart.map((e) => _toDouble(e["actual"])).toList();
    final targets = chart.map((e) => _toDouble(e["target"])).toList();
    final maxVal = [...actuals, ...targets].fold<double>(0, (m, v) => v > m ? v : m);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: AppColors.cardBg, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Text("\u0623\u062f\u0627\u0621 \u0627\u0644\u0645\u0628\u064a\u0639\u0627\u062a", style: TextStyle(color: AppColors.textPrimary, fontSize: 13, fontWeight: FontWeight.w700)),
          const Spacer(),
          Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: AppColors.inputBg, borderRadius: BorderRadius.circular(6)), child: Text("\u0622\u062e\u0631 30 \u064a\u0648\u0645", style: TextStyle(color: AppColors.textMuted, fontSize: 9))),
        ]),
        const SizedBox(height: 12),
        Row(children: [_legendDot("\u0627\u0644\u0645\u0628\u064a\u0639\u0627\u062a \u0627\u0644\u0641\u0639\u0644\u064a\u0629", AppColors.primary), const SizedBox(width: 12), _legendDot("\u0627\u0644\u0645\u0633\u062a\u0647\u062f\u0641", AppColors.success)]),
        const SizedBox(height: 12),
        SizedBox(height: 140, child: CustomPaint(size: Size.infinite, painter: _LineChartPainter(actuals: actuals, targets: targets, maxVal: maxVal, primaryColor: AppColors.primary, targetColor: AppColors.success, borderColor: AppColors.border, textColor: AppColors.textMuted))),
      ]),
    );
  }

  Widget _legendDot(String label, Color color) {
    return Row(mainAxisSize: MainAxisSize.min, children: [Container(width: 8, height: 3, decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(2))), const SizedBox(width: 4), Text(label, style: TextStyle(color: AppColors.textMuted, fontSize: 9))]);
  }

  Widget _buildTargetGauge(Map target) {
    final percent = _toInt(target["percent"] ?? 0);
    final achieved = _toDouble(target["achieved"]);
    final targetVal = _toDouble(target["target"]);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: AppColors.cardBg, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.border)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.center, children: [
        Text("\u062a\u062d\u0642\u0642 \u0627\u0644\u0645\u0633\u062a\u0647\u062f\u0641", style: TextStyle(color: AppColors.textPrimary, fontSize: 13, fontWeight: FontWeight.w700)),
        const SizedBox(height: 16),
        SizedBox(width: 120, height: 120, child: CustomPaint(painter: _GaugePainter(percent: percent, color: AppColors.primary, bgColor: AppColors.border))),
        const SizedBox(height: 12),
        Text(Formatters.compact(achieved), style: TextStyle(color: AppColors.textPrimary, fontSize: 16, fontWeight: FontWeight.w800)),
        Text("/ ${Formatters.compact(targetVal)}", style: TextStyle(color: AppColors.textMuted, fontSize: 11)),
        const SizedBox(height: 8),
        Text("\u0627\u0644\u0645\u0633\u062a\u0647\u062f\u0641 \u0627\u0644\u0634\u0647\u0631\u064a", style: TextStyle(color: AppColors.textMuted, fontSize: 9)),
        Text("${Formatters.compact(targetVal)} \u062c.\u0645", style: TextStyle(color: AppColors.textSecondary, fontSize: 10, fontWeight: FontWeight.w600)),
      ]),
    );
  }
');
fclose($part2);
echo "Part2 done\n";
