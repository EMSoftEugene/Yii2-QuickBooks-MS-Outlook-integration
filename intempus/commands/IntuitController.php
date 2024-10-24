<?php

namespace app\commands;

use app\models\User;
use GuzzleHttp\Client;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\Data\IPPDepartment;
use QuickBooksOnline\API\Data\IPPPreferences;
use QuickBooksOnline\API\DataService\DataService;
use yii\console\Controller;
use yii\console\ExitCode;


class IntuitController extends Controller
{
    private $dataService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->getDataService();
    }

    public function actionStep2()
    {

        $departments = $this->dataService->Query("SELECT * FROM Department");
        print_r($departments);

        $newDepartment = new IPPDepartment();
        $newDepartment->Name = '8901 Duxbury Rd';
        $newDepartment->FullyQualifiedName = '8901 Duxbury Rd';
        $newDepartment->Active = true;
        $newDepartment->Address = '8901 Duxbury Rd';

        $this->dataService->Add($newDepartment);
        $this->handleError();

        $departments = $this->dataService->Query("SELECT * FROM Department");


        print_r($departments);
        die;
    }

    public function actionStep1()
    {
        $dataService = $this->getDataService();

        // try $query
//        $preferences = $dataService->Query("SELECT * FROM Preferences");

        $preference = $dataService->getCompanyPreferences();

        $updatingPreference = new IPPPreferences(['Id' => $preference->Id], true);
        $updatingPreference->id = $preference->Id;
        $updatingPreference->SyncToken = $preference->SyncToken;
        $updatingPreference->sparse = false;
        $updatingPreference->AccountingInfoPrefs = $preference->AccountingInfoPrefs;
        $updatingPreference->AccountingInfoPrefs->TrackDepartments = true;
//        $updatingPreference->SalesFormsPrefs = $preference->SalesFormsPrefs;
//        $updatingPreference->SalesFormsPrefs->DefaultCustomerMessage = 'asdasdsa';
//        $preference->SalesFormsPrefs->DefaultCustomerMessage = 'asdasdsa';

//        $updatingPreference->ProductAndServicesPrefs = $preference->ProductAndServicesPrefs;
//        $updatingPreference->ProductAndServicesPrefs->ForPurchase = false;
//        $updatingPreference->ProductAndServicesPrefs->ForSales = false;
//
//        unset($updatingPreference->SalesFormsPrefs->CustomField);
        print_r($updatingPreference);

//        $updateObject = (object)[
//            'id' => $preference->Id,
//            'SyncToken' => $preference->SyncToken,
//            'AccountingInfoPrefs' => (object)['TrackDepartments' => true],
//            'sparse' => false,
//        ];

        $dataService->Update($updatingPreference);
        $error = $dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }

        $preference = $dataService->getCompanyPreferences();
        print_r($preference);
        die;

        return ExitCode::OK;
    }

    /**
     * @return int Exit code
     */
    public function actionIndex()
    {
        $intuitConfig = \Yii::$app->params['intuit'];
        $user = User::findOne(['is_admin' => 1]);
//        print_r($user->toArray());
//        print_r($intuitConfig);

        $clientID = $intuitConfig['client_id'];
        $clientSecret = $intuitConfig['client_secret'];
        $accessTokenKey = $user->intuit_access_token;
        $refresh_token = $user->intuit_refresh_token;
        $accessTokenExpiresAt = $user->intuit_expires_in;
        $refreshTokenExpiresAt = $user->intuit_x_refresh_token_expires_in;
        $tokenType = "bearer";
        $realm_id = $user->intuit_realm_id;

//        $accessToken = new OAuth2AccessToken($clientID, $clientSecret, $accessTokenKey, $refresh_token);
//        $accessToken->setRealmId($realm_id);

//        $intuitConfig = \Yii::$app->params['intuit'];
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $intuitConfig['client_id'],
            'ClientSecret' => $intuitConfig['client_secret'],
            'RedirectURI' => $intuitConfig['oauth_redirect_uri'],
            'scope' => $intuitConfig['oauth_scope'],
            'baseUrl' => "development",
            'accessTokenKey' => $accessTokenKey,
            'refreshTokenKey' => $refresh_token,
            'QBORealmId' => $realm_id,
        ));

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        try {
            $accessToken = $OAuth2LoginHelper->refreshToken();
            $accessToken->setRealmId($realm_id);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            die;
        }
        $dataService->updateOAuth2Token($accessToken);
        $error = $dataService->getLastError();
        if ($error) {
            echo "111The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }


        // try $query
        $preferences = $dataService->Query("SELECT * FROM Preferences");
        print_r($preferences);
        die;

        // Iterate through all Accounts, even if it takes multiple pages
        $i = 1;
        while (1) {
            $allAccounts = $dataService->FindAll('Location', $i, 500);
            $error = $dataService->getLastError();
            if ($error) {
                echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
                echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
                echo "The Response message is: " . $error->getResponseBody() . "\n";
                exit();
            }

            if (!$allAccounts || (0 == count($allAccounts))) {
                break;
            }

            foreach ($allAccounts as $oneAccount) {
                print_r($oneAccount);
                echo "Account[" . ($i++) . "]: {$oneAccount->Name}\n";
                echo "\t * Id: [{$oneAccount->Id}]\n";
                echo "\t * AccountType: [{$oneAccount->AccountType}]\n";
                echo "\t * AccountSubType: [{$oneAccount->AccountSubType}]\n";
                echo "\t * Active: [{$oneAccount->Active}]\n";
                echo "\n";
            }
        }


        die;
        ///////222222222

        $allCompanies = $dataService->FindAll('CompanyInfo');
        foreach ($allCompanies as $oneCompany) {
            $oneCompanyReLookedUp = $dataService->FindById($oneCompany);
            echo "Company Name: {$oneCompanyReLookedUp->CompanyName}\n";
        }

        die;

        $oauthLoginHelper = $dataService->getOAuth2LoginHelper();
        $companyInfo = $dataService->getCompanyInfo();
        $error = $dataService->getLastError();
        if ($error) {
            echo "111The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }
        echo "Вот это да:" . PHP_EOL;
        print_r($companyInfo);
        die;
    }

    private function getDataService()
    {
        $intuitConfig = \Yii::$app->params['intuit'];
        $user = User::findOne(['is_admin' => 1]);

        $accessTokenKey = $user->intuit_access_token;
        $refresh_token = $user->intuit_refresh_token;
        $realm_id = $user->intuit_realm_id;

        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $intuitConfig['client_id'],
            'ClientSecret' => $intuitConfig['client_secret'],
            'RedirectURI' => $intuitConfig['oauth_redirect_uri'],
            'scope' => $intuitConfig['oauth_scope'],
            'baseUrl' => "development",
            'accessTokenKey' => $accessTokenKey,
            'refreshTokenKey' => $refresh_token,
            'QBORealmId' => $realm_id,
        ));

        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

        try {
            $accessToken = $OAuth2LoginHelper->refreshToken();
            $accessToken->setRealmId($realm_id);
        } catch (\Exception $e) {
            echo "Error refresh token";
            print_r($e->getMessage());
            die;
        }

        $dataService->updateOAuth2Token($accessToken);
        $error = $dataService->getLastError();
        if ($error) {
            echo "Error update token " . "\n";
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }

        $this->dataService = $dataService;
    }

    private function handleError()
    {
        $error = $this->dataService->getLastError();
        if ($error) {
            echo "Error update token " . "\n";
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
            exit();
        }
    }


    private function tsheets1()
    {
        $user = User::findOne(['is_admin' => 0]);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'MYAPPCLIENTID',
            'redirect_uri' => 'https://somedomain.com/callback',
            'state' => 'MYSTATE',
        ];

        $baseUri = 'https://rest.tsheets.com/api/v1/';
        $client = new Client(['base_uri' => $baseUri]);
        $headers = [
            //'Authorization' => 'Bearer ' . $user->intuit_access_token,
//            'Accept' => 'application/json',
        ];
        $response = $client->request('GET', 'authorize', [
            'headers' => $headers,
            'query' => $queryParams
        ]);

        print_r($response->getBody());

        echo PHP_EOL;
        echo "Done.";
        die;

        $request = new HttpRequest();
        $request->setUrl('');
        $request->setMethod(HTTP_METH_GET);

        $request->setQueryData(array());

        $request->setHeaders(array());

        try {
            $response = $request->send();

            echo $response->getBody();
        } catch (HttpException $ex) {
            echo $ex;
        }

        // https://rest.tsheets.com/api/v1

    }
}
