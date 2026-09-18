<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Menu Visibility Rules
    |--------------------------------------------------------------------------
    |
    | Use this file to control who can see each menu button.
    | - admin: role = admin
    | - levels: values from AppUserAuthController LEVEL_* constants
    |
    */
    'buttons' => [
        'menuKpiDept' => [
            'route' => 'kpi.summary.dept',
            'label' => [
                'th' => 'สรุปรายงาน',
                'en' => 'Report Summary',
            ],
            'visible_to' => [
                'admin' => true,
                'levels' => [4],
                'positions' => [
                    'assist manager',
                    'manager',
                    'deputy general manager',
                    'general manager',
                ],
            ],
        ],
        'menuOkrDept' => [
            'route' => 'okr.summary.dept',
            'label' => [
                'th' => 'สรุปภาพรวมคะแนน',
                'en' => 'Overall Score Summary',
            ],
            'visible_to' => [
                'admin' => true,
                'levels' => [4],
            ],


        ],
        'menuOkrAllDept' => [
            'route' => 'okr.summary.all',
            'label' => [
                'th' => 'สรุปคะแนน OKRs',
                'en' => 'OKRs Score Summary',
            ],
            'visible_to' => [
                'admin' => true,
                'levels' => [5],
            ],
        ],
        'menuKpiInput' => [
            'route' => 'kpi.input',
            'label' => [
                'th' => 'เน€เธเธดเนเธกเธเนเธญเธกเธนเธฅ KPIs',
                'en' => 'Add KPIs',
            ],
            'visible_to' => [
                'admin' => false,
                'levels' => [2, 3, 4],
            ],
            // Optional override for KPI Input level-3 shared department targets.
            // กำหนดคนเห็นรายงานทั้ง ลำดับชั้นที่ 3 และลงรายงานของแผนกนั้นๆ
            'level3_department_targets_by_employee' => [
                '62079' => ['QC', 'IQ', 'SQ', 'QA',],
                '70766' => ['PE', 'QAN', 'PD', 'ML'],
                '64511' => ['PDA1', 'PDW'],
                '68060' => ['Manufacturing', 'EX', 'IM1', 'IM2', 'IM3', 'MMT', 'PET', 'BM'],
                '64694' => ['Manufacturing', 'MT1', 'MT2', 'LGD', 'LGE', 'SL', 'ST1', 'ST2', 'QC', 'Resident', 'IQ', 'SQ', 'QA', 'EX', 'IM1', 'IM2', 'IM3', 'MMT', 'PET', 'PF', 'PDA1', 'PDA2', 'PC', 'AME'],
            ],
        ],
    ],
];
