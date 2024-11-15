<?php


namespace app\modules\timeTracker\services;

use app\models\TimeEntries;
use app\modules\timeTracker\models\ApiAuth;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use GuzzleHttp\Client;
use yii\db\Exception;
use yii\helpers\Url;

class TsheetService implements ApiInterface
{
    const NAME = 'tsheet';
    private ?Client $client;
    private array $headers = [];
    private array $params = [];
    private ApiAuth $apiAuth;

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params['tsheet'];
        $this->apiAuth = ApiAuth::getOrSetApiAuth(self::NAME);
        $this->client = $this->getClient();
    }

    public function getAuthUrl(): string
    {
        return $this->params['authorizationRequestUrl'] .
            str_replace('/' . $this->params['moduleUrl'] . '/' . self::NAME, '', Url::toRoute(['',
                    'response_type' => 'code',
                    'client_id' => $this->params['client_id'],
                    'redirect_uri' => $this->params['redirect_uri'],
                    'state' => '12345',
                ]
            ));
    }

    public function exchangeAuthCode(string $code)
    {
        $client = new Client();
        $response = $client->request('POST', $this->params['tokenEndPointUrl'], [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->params['client_id'],
                'client_secret' => $this->params['client_secret'],
                'code' => $code,
                'redirect_uri' => $this->params['redirect_uri'],
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws Exception
     */
    public function updateUserAuth($data): bool
    {
        $this->apiAuth->access_token = $data['access_token'] ?? '';
        $this->apiAuth->refresh_token = $data['refresh_token'] ?? '';
        $this->apiAuth->expires_in = $data['expires_in'] ?? '';
        $this->apiAuth->realm_id = $data['company_id'] ?? '';
        $result = $this->apiAuth->save();
        $this->apiAuth = ApiAuth::getOrSetApiAuth(self::NAME);
        return $result;
    }

    public function refreshToken(): bool
    {
        $response = $this->client->request('POST', $this->params['tokenEndPointUrl'], [
            'headers' => $this->headers,
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->params['client_id'],
                'client_secret' => $this->params['client_secret'],
                'refresh_token' => $this->apiAuth->refresh_token,
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $this->updateUserAuth($result);
    }

    public function getClient(): ?Client
    {
        if ($this->apiAuth->access_token) {
            $this->client = new Client(['base_uri' => $this->params['base_api_url']]);
            $this->headers = [
                'Authorization' => 'Bearer ' . $this->apiAuth->access_token,
                'Accept' => 'application/json',
            ];
            return $this->client;
        }
        return null;
    }

    public function requestGet($url, $queryParams = [])
    {
        $response = $this->client->request('GET', $url, [
            'headers' => $this->headers,
            'query' => $queryParams
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function requestPost($url, $params = [])
    {
        $response = $this->client->request('GET', $url, [
            'headers' => $this->headers,
            'form_params' => $params
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

}