<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    | محرك القراءة الافتراضي: tesseract (محلي) أو google (Cloud Vision API).
    | عند فشل Google يرجع تلقائياً إلى Tesseract.
    */

    'provider' => env('OCR_PROVIDER', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Vision
    |--------------------------------------------------------------------------
    | مفتاح API يُسجَّل في .env على الخادم فقط (لا يُرفع للريبو):
    |   GOOGLE_VISION_API_KEY=...
    */

    'google_api_key' => env('GOOGLE_VISION_API_KEY'),

    // لغات الصورة تُرسل كتلميح لـ Vision (ara = عربية)
    'google_language_hints' => ['ara', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Tesseract
    |--------------------------------------------------------------------------
    | مسار تنفيذ Tesseract ولغاته. يُثبَّت على خادم الإنتاج عبر:
    |   sudo apt install -y tesseract-ocr tesseract-ocr-ara
    */

    'binary' => env('TESSERACT_BINARY', 'tesseract'),

    'languages' => env('TESSERACT_LANGUAGES', 'ara+eng'),

    // 4 = تلقائي حسب الصفحة، مناسب لصور البطاقات
    'psm' => (int) env('TESSERACT_PSM', 4),

    // أقصى ضلع بالبكسل بعد التصغير (كلما زاد زادت الدقة لكن بطء أكثر)
    'max_dimension' => (int) env('OCR_MAX_DIMENSION', 2400),

];
