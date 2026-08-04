<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$patterns = ['%LOCATIONS%', '%DEROGATIONS%', '%CHANGEMENTS DE METHODES%', '%AFFECTATION%'];

DB::table('ref_codes_edi')
    ->where(function ($query) use ($patterns) {
        foreach ($patterns as $pattern) {
            $query->orWhere('tableau', 'like', $pattern);
        }
    })
    ->orderBy('tableau')
    ->orderBy('id')
    ->get(['code_edi', 'tableau', 'libelle', 'col1', 'col2', 'col3'])
    ->each(fn ($r) => print(json_encode($r, JSON_UNESCAPED_UNICODE).PHP_EOL));
