<?php

namespace app\modules\timeTracker\services;

use app\modules\timeTracker\models\ApiAuth;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use GuzzleHttp\Client;
use yii\db\Exception;
use yii\helpers\Url;

class MicrosoftService implements ApiInterface
{
    const NAME = 'microsoft';
    private ?Client $client = null;
    private array $headers = [];
    public array $params = [];
    private ApiAuth $apiAuth;

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params[self::NAME];
        $this->apiAuth = ApiAuth::getOrSetApiAuth(self::NAME);
        $this->client = $this->getClient();
    }

    public function getAuthUrl(): string
    {
        $url = $this->params['authorizationRequestUrl'];
        $url = str_replace('{tenant}', $this->params['tenant_id'], $url);
        return $url . str_replace('/' . $this->params['moduleUrl'] . '/' . self::NAME, '', Url::toRoute(['',
                    'client_id' => $this->params['client_id'],
                    'response_type' => 'code',
                    'redirect_uri' => $this->params['redirect_uri'],
                    'response_mode' => 'query',
                    'scope' => 'offline_access User.Read Group.Read.All',
                    'state' => '12345',
                ]
            ));
    }

    public function exchangeAuthCode(string $code)
    {
        $url = $this->params['tokenEndPointUrl'];
        $url = str_replace('{tenant}', $this->params['tenant_id'], $url);
        $client = new Client();
        $response = $client->request('POST', $url, [
            'form_params' => [
                'client_id' => $this->params['client_id'],
                'scope' => 'offline_access User.Read Group.Read.All',
                'code' => $code,
                'redirect_uri' => $this->params['redirect_uri'],
                'grant_type' => 'authorization_code',
                'client_secret' => $this->params['client_secret'],
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
        $this->apiAuth->refresh_token_expires_in = $data['ext_expires_in'] ?? '';
        $result = $this->apiAuth->save();
        $this->apiAuth = ApiAuth::getOrSetApiAuth(self::NAME);
        return $result;
    }

    public function refreshToken(): bool
    {
        $url = $this->params['tokenEndPointUrl'];
        $url = str_replace('{tenant}', $this->params['tenant_id'], $url);
        $client = new Client();
        $response = $client->request('POST', $url, [
            'form_params' => [
                'client_id' => $this->params['client_id'],
                'scope' => 'offline_access User.Read Group.Read.All',
                'refresh_token' => $this->apiAuth->refresh_token,
                'grant_type' => 'refresh_token',
                'client_secret' => $this->params['client_secret'],
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