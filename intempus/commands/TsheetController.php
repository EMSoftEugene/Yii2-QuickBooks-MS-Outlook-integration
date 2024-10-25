<?php

namespace app\commands;

use app\models\User;
use GuzzleHttp\Client;
use yii\console\Controller;
use yii\console\ExitCode;


class TsheetController extends Controller
{
    private Client $client;
    private array $headers;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->auth();
    }

    private function auth()
    {
        $user = User::findOne(['is_admin' => 1]);
        $baseUri = 'https://rest.tsheets.com/api/v1/';
        $this->client = new Client(['base_uri' => $baseUri]);
        $this->headers = [
            'Authorization' => 'Bearer ' . $user->tsheets_access_token,
            'Accept' => 'application/json',
        ];
    }

    public function actionTimeEntries()
    {
        $date = date('Y-m-d');

        $queryParams = [
            'date' => '2020-01-06,'
        ];
        $response = $this->client->request('GET', 'time_off_request_entries', [
            'headers' => $this->headers,
            'query' => $queryParams
        ]);
        $result = json_decode($response->getBody()->getContents(), true);

        print_r($result);
        die;


    }

    public function actionIndex()
    {
        $queryParams = [
        ];
        $response = $this->client->request('GET', 'locations', [
            'headers' => $this->headers,
            'query' => $queryParams
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        print_r($result);
        die;
    }

    public function actionAdd()
    {
        $params = [];
        $params['data'] = [];
        $params['data'][] = (object)[
            'addr1' => '14833 Hillside Trl',
            'addr2' => '',
            'city' => 'Savage',
            'state' => 'MN',
            'zip' => '55378',
            'country' => 'USA',
        ];

        $response = $this->client->request('POST', 'locations', [
            'headers' => $this->headers,
            'form_params' => $params
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        print_r($result);
        die;


    }
}
