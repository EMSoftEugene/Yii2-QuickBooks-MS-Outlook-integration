<?php

return [
    'authorizationRequestUrl' => 'https://appcenter.intuit.com/connect/oauth2',
    'tokenEndPointUrl' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
    'client_id' => $_ENV['INTUIT_CLIENT_ID'], //'Enter the clietID from Developer Portal',
    'client_secret' => $_ENV['INTUIT_SECRET'], // 'Enter the clientSecret from Developer Portal',
    'oauth_scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
    'oauth_redirect_uri' => 'https://outlook.rentbypro.com/intuit/callback',
];
