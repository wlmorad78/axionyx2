<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // ─── Modules ───
    \App\Modules\Customer\Providers\ModuleServiceProvider::class,
    \App\Modules\Distribution\Providers\ModuleServiceProvider::class,
];
