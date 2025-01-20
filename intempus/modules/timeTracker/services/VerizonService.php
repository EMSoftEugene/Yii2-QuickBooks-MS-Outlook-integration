<?php


namespace app\modules\timeTracker\services;

use app\models\TimeEntries;
use app\modules\timeTracker\models\ApiAuth;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use GuzzleHttp\Client;
use yii\db\Exception;
use yii\helpers\Url;

class VerizonService implements ApiInterface
{
    const NAME = 'verizon';
    private ?Client $client;
    private array $headers = [];
    private array $params = [];
    private ApiAuth $apiAuth;

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params['verizon'];
        $this->apiAuth = ApiAuth::getOrSetApiAuth(self::NAME);
        $this->client = $this->getClient();

        $this->exchangeAuthCode('');
    }

    public function getAuthUrl(): string
    {
        return '';
    }

    public function exchangeAuthCode(string $code)
    {
        $curDate = date("Y-m-d H:i:s");
        $this->apiAuth->access_token;
        if (!$this->apiAuth->access_token || $this->apiAuth->expires_in < $curDate) {
            $client = new Client();
            $headers = [
                'Authorization' => 'Basic ' . base64_encode($this->params['client_id'] . ':' . $this->params['client_secret']),
                'Accept' => 'application/json',
            ];

            $response = $client->request('GET', $this->params['tokenEndPointUrl'], [
                'headers' => $headers,
            ]);

            $token = $response->getBody()->getContents();
            if ($token) {
                $this->updateUserAuth(['access_token' => $token]);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function updateUserAuth($data): bool
    {
        $this->apiAuth->access_token = $data['access_token'] ?? '';
        $this->apiAuth->refresh_token = $data['refresh_token'] ?? '';
        $this->apiAuth->expires_in = date("Y-m-d H:i:s", strtotime("+20 minutes"));
        $this->apiAuth->realm_id = $data['company_id'] ?? '';
        $result = $this->apiAuth->save();
        $this->apiAuth = ApiAuth::getOrSetApiAuth(self::NAME);
        return $result;
    }

    public function refreshToken(): bool
    {
        $this->exchangeAuthCode('');
        return true;
    }

    public function getClient(): ?Client
    {
        if ($this->apiAuth->access_token) {
            $this->client = new Client(['base_uri' => $this->params['base_api_url']]);
            $this->headers = [
                'Authorization' => 'Atmosphere atmosphere_app_id=' . $this->params['atmosphere_app_id'] . ', Bearer ' . $this->apiAuth->access_token,
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