<?php

// EVN enable
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

\Yii::$container->set('app\services\interfaces\MicrosoftInterface', 'app\services\MicrosoftService');

$intuit = require __DIR__ . '/intuit.php';
$tsheet = require __DIR__ . '/tsheet.php';


return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'intuit' => $intuit,
    'tsheet' => $tsheet,
];
