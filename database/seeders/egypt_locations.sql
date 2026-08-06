-- ========================================
-- Egypt Location Seeder
-- Country + 27 Governorates + Cities + Districts
-- ========================================

-- 1. Country: Egypt
INSERT INTO countries (code, iso2, name, name_en, phone_code, is_active, created_at, updated_at)
VALUES ('EG', 'EG', 'مصر', 'Egypt', '+20', 1, datetime('now'), datetime('now'));

-- ========================================
-- 2. Governorates (27)
-- ========================================
INSERT INTO governorates (country_id, code, name, name_en, is_active, created_at, updated_at) VALUES
(1, 'CAI', 'القاهرة', 'Cairo', 1, datetime('now'), datetime('now')),
(1, 'ALX', 'الإسكندرية', 'Alexandria', 1, datetime('now'), datetime('now')),
(1, 'GIZ', 'الجيزة', 'Giza', 1, datetime('now'), datetime('now')),
(1, 'QLY', 'القليوبية', 'Qalyubia', 1, datetime('now'), datetime('now')),
(1, 'SHQ', 'الشرقية', 'Sharqia', 1, datetime('now'), datetime('now')),
(1, 'DKL', 'الدقهلية', 'Dakahlia', 1, datetime('now'), datetime('now')),
(1, 'BHR', 'البحيرة', 'Beheira', 1, datetime('now'), datetime('now')),
(1, 'KFS', 'كفر الشيخ', 'Kafr El Sheikh', 1, datetime('now'), datetime('now')),
(1, 'GHR', 'الغربية', 'Gharbia', 1, datetime('now'), datetime('now')),
(1, 'MNF', 'المنوفية', 'Monufia', 1, datetime('now'), datetime('now')),
(1, 'BNS', 'بني سويف', 'Beni Suef', 1, datetime('now'), datetime('now')),
(1, 'FYM', 'الفيوم', 'Fayoum', 1, datetime('now'), datetime('now')),
(1, 'MIN', 'المنيا', 'Minya', 1, datetime('now'), datetime('now')),
(1, 'AST', 'أسيوط', 'Assiut', 1, datetime('now'), datetime('now')),
(1, 'SHG', 'سوهاج', 'Sohag', 1, datetime('now'), datetime('now')),
(1, 'QEN', 'قنا', 'Qena', 1, datetime('now'), datetime('now')),
(1, 'LUX', 'الأقصر', 'Luxor', 1, datetime('now'), datetime('now')),
(1, 'ASM', 'أسوان', 'Aswan', 1, datetime('now'), datetime('now')),
(1, 'BAH', 'البحر الأحمر', 'Red Sea', 1, datetime('now'), datetime('now')),
(1, 'WAD', 'الوادي الجديد', 'New Valley', 1, datetime('now'), datetime('now')),
(1, 'MTN', 'مطروح', 'Matrouh', 1, datetime('now'), datetime('now')),
(1, 'SIN', 'شمال سيناء', 'North Sinai', 1, datetime('now'), datetime('now')),
(1, 'JSN', 'جنوب سيناء', 'South Sinai', 1, datetime('now'), datetime('now')),
(1, 'PTS', 'بورسعيد', 'Port Said', 1, datetime('now'), datetime('now')),
(1, 'SUZ', 'السويس', 'Suez', 1, datetime('now'), datetime('now')),
(1, 'ISM', 'الإسماعيلية', 'Ismailia', 1, datetime('now'), datetime('now')),
(1, 'DAM', 'دمياط', 'Damietta', 1, datetime('now'), datetime('now'));
