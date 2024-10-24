<?php

return [
    'authorizationRequestUrl' => 'https://rest.tsheets.com/api/v1/authorize',
    'tokenEndPointUrl' => 'https://rest.tsheets.com/api/v1/grant', // POST
    'client_id' => $_ENV['TSHEET_CLIENT_ID'], //'Enter the clietID from Developer Portal',
    'client_secret' => $_ENV['TSHEET_SECRET'], // 'Enter the clientSecret from Developer Portal',
    'oauth_scope' => '',
    'oauth_redirect_uri' => 'https://outlook.rentbypro.com/tsheet/callback',
];
