<?php

$settings_validation_rules = [
    'localbackup_status' => [
        'search' => [
            'args' => ['settingsListEnums'],
            'msg' => 'Status Field should be one of the following values (%s) !!!'
        ],
        'required' => [
            'is_required' => true,
            'has_dependent' => false,
            'msg' => 'Status Field should be mandatory !!!'
        ]
    ],
    'localbackup_frequency' => [
        'isnumeric' => [
            'msg' => 'Frequency Field should be a number !!!'
        ],
        'positive' => [
            'msg' => 'Frequency Field value should be positive number !!!'
        ],
        'required' => [
            'is_required' => true,
            'has_dependent' => false,
            'msg' => 'Frequency Field should be mandatory !!!'
        ]
    ],
    'frequency_unit' => [
        'search' => [
            'args' => ['settingsListEnums'],
            'msg' => 'Frequency unit Field should be one of the following values (%s) !!!'
        ],
        'required' => [
            'is_required' => true,
            'has_dependent' => false,
            'msg' => 'Frequency Unit Field should be mandatory !!!'
        ]
    ],
    'specific_time' => [
        'regex' => [
            'regex' => '~^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$~m',
            'msg' => 'Specific Time Value is Misformated !!!'
        ],
        'required' => [
            'is_required' => false,
            'has_dependent' => true,
            'dependent_field' => 'frequency_unit',
            'dependent_value' => 'days',
            'msg' => 'Specific Time Field should be mandatory !!!'
        ]
    ],
    'localbackup_number' => [
        'isnumeric' => [
            'msg' => 'Max Backup Number Field should be a number !!!'
        ],
        'positive' => [
            'msg' => 'Max Backups Field value should be positive number !!!'
        ],
        'required' => [
            'is_required' => true,
            'has_dependent' => false,
            'msg' => 'Max Backups Field should be mandatory !!!'
        ]
    ],
    'localbackup_directory' => [
        'regex' => [
            'regex' => '~^(/[^/ ]*)+/$~m',
            'msg' => "Backup Directory should have at start/end the '/' character and the directory name should have no spaces !!!"
        ],
        'required' => [
            'is_required' => true,
            'has_dependent' => false,
            'msg' => 'Backup Directory Field should be mandatory !!!'
        ],
        'fileexists' => [
            'msg' => 'Provided Backup Directory Does not exists !!!'
        ]
    ],
    'emailreport_email' => [
        'email' => [
            'function' => 'filter_var',
            'const' => FILTER_VALIDATE_EMAIL,
            'msg' => 'Email Field is Misformated !!!'
        ]
    ],
    'emailreport_subject' => [
        'required' => [
            'is_required' => false,
            'has_dependent' => true,
            'dependent_field' => 'emailreport_email',
            'msg' => 'Subject Field is Mandatory !!!'
        ]
    ],
    'emailreport_body' => [
        'required' => [
            'is_required' => false,
            'has_dependent' => true,
            'dependent_field' => 'emailreport_email',
            'msg' => 'Body Field is Mandatory !!!'
        ]
    ],
];