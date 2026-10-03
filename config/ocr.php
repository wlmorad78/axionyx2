<?php

return [

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
