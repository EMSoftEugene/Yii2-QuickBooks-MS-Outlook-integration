<?php

// EVN enable
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

\Yii::$container->set('app\services\interfaces\MicrosoftInterface', 'app\services\MicrosoftService');

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
];
