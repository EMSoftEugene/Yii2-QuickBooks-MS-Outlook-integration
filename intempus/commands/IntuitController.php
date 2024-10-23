<?php

namespace app\commands;

use app\models\User;
use GuzzleHttp\Client;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;
use yii\console\Controller;
use yii\console\ExitCode;


class IntuitController extends Controller
{
    /**
     * @return int Exit code
     */
    public function actionIndex()
    {
//        $user = User::findOne(['is_admin' => 1]);
//
//        $client = new Client(['base_uri' => 'https://rest.tsheets.com/']);
//        $headers = [
//            'Authorization' => 'Bearer ' . $user->intuit_access_token,
//            'Accept'        => 'application/json',
//        ];
//        $response = $client->request('GET', 'api/v1/users', [
//            'headers' => $headers,
//        ]);
//
//        print_r($response->getBody());
//
//        echo PHP_EOL;
//        echo "Done.";
//        die;



        $intuitConfig = \Yii::$app->params['intuit'];
        $user = User::findOne(['is_admin' => 1]);

        $clientID = $intuitConfig['client_id'];
        $clientSecret = $intuitConfig['client_secret'];
        $accessTokenKey = $user->intuit_access_token;
        $refresh_token = $user->intuit_refresh_token;
        $accessTokenExpiresAt = $user->intuit_expires_in;
        $refreshTokenExpiresAt = $user->intuit_x_refresh_token_expires_in;
        $tokenType = "bearer";
        $realm_id = $user->intuit_realm_id;

//        $token = new OAuth2AccessToken($clientID,$clientSecret,$accessTokenKey,$refresh_token,$accessTokenExpiresAt,$refreshTokenExpiresAt, $tokenType);

        $accessToken = new OAuth2AccessToken($clientID, $clientSecret, $accessTokenKey, $refresh_token);
        $accessToken->setRealmId($company_id);

        $intuitConfig = \Yii::$app->params['intuit'];
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $intuitConfig['client_id'],
            'ClientSecret' =>  $intuitConfig['client_secret'],
            'RedirectURI' => $intuitConfig['oauth_redirect_uri'],
            'scope' => $intuitConfig['oauth_scope'],
            'baseUrl' => "development",
//            'refreshTokenKey' => $refresh_token,
//            'QBORealmId' => $config['realm_id'],
        ));

        // The first parameter of OAuth2LoginHelper is the ClientID, the second parameter is the client Secret
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $accessToken = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($refresh_token);
        $dataService->updateOAuth2Token($accessToken);

        $companyInfo = $dataService->getCompanyInfo();
        $address = "QBO API call Successful!! Response Company name: " . $companyInfo->CompanyName . " Company Address: " . $companyInfo->CompanyAddr->Line1 . " " . $companyInfo->CompanyAddr->City . " " . $companyInfo->CompanyAddr->PostalCode;
        print_r($address);

//        $OAuth2LoginHelper->getAccessToken();

        return ExitCode::OK;

    }
}
