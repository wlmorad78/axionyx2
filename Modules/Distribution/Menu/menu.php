<?php

return [
    'key' => 'distribution',
    'label' => 'التوزيع',
    'label_en' => 'Distribution',
    'icon' => 'truck',
    'items' => [
        [
            'key' => 'distribution.salesmen',
            'label' => 'المناديب',
            'label_en' => 'Salesmen',
            'route' => 'distribution.salesmen.index',
            'icon' => 'users',
            'permission' => 'distribution.salesmen.view',
        ],
        [
            'key' => 'distribution.teams',
            'label' => 'فرق المبيعات',
            'label_en' => 'Sales Teams',
            'route' => 'distribution.teams.index',
            'icon' => 'user-friends',
            'permission' => 'distribution.teams.view',
        ],
        [
            'key' => 'distribution.routes',
            'label' => 'خطوط التوزيع',
            'label_en' => 'Distribution Routes',
            'route' => 'distribution.routes.index',
            'icon' => 'route',
            'permission' => 'distribution.routes.view',
        ],
        [
            'key' => 'distribution.assignments',
            'label' => 'تعيينات المندوبين',
            'label_en' => 'Salesman Assignments',
            'route' => 'distribution.assignments.index',
            'icon' => 'clipboard-list',
            'permission' => 'distribution.assignments.view',
        ],
    ],
];
