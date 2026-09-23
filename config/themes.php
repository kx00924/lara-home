<?php

/*
 * Presentation metadata for the site themes. A theme is any folder under
 * resources/views/themes plus "default"; entries here are optional and only
 * improve how the admin Themes page describes and previews each one.
 */
return [
    'default' => [
        'label' => 'Calm',
        'description' => 'The original look: warm neutrals, serif headlines, dark or light mode with a visitor-adjustable accent.',
        'swatches' => ['#111113', '#1d1d1f', '#ecebe8', '#ffffff'],
        'font' => 'Noto Serif',
        'mode' => 'Dark by default, light available',
    ],
    'neon' => [
        'label' => 'Neon',
        'description' => 'Ported from the profile site: near-black surfaces, cyan borders and glow, Inter and JetBrains Mono, pill filters and masthead pages.',
        'swatches' => ['#05080c', '#0b1219', '#e6f1f5', '#22d3ee'],
        'font' => 'Inter',
        'mode' => 'Dark only',
    ],
];
