<?php

return [
    'show_custom_fields' => true,
    'custom_fields' => [
        'custom_field_1' => [
            'type' => 'text',
            'label' => 'Trendyol ID',
            'placeholder' => 'Trendyol ID',
            'required' => true,
            'rules' => 'required|string|max:255',
        ],
        'custom_field_2' => [
            'type' => 'password',
            'label' => 'Trendyol Token',
            'placeholder' => 'Trendyol Token',
            'required' => true,
            'rules' => 'required|string|max:255',
        ],
    ]
];
