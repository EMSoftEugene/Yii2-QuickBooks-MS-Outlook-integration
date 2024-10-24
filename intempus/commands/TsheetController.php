<?php

namespace app\commands;

use app\models\User;
use GuzzleHttp\Client;
use yii\console\Controller;
use yii\console\ExitCode;


class TsheetController extends Controller
{

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex()
    {
        $user = User::findOne(['is_admin' => 1]);
        $tsheetConfig = \Yii::$app->params['tsheet'];

        $baseUri = 'https://rest.tsheets.com/api/v1/';
        $client = new Client(['base_uri' => $baseUri]);
        $headers = [
            'Authorization' => 'Bearer ' . $user->tsheets_access_token,
            'Accept' => 'application/json',
        ];

        $queryParams = [
        ];
        $response = $client->request('GET', 'locations', [
            'headers' => $headers,
            'query' => $queryParams
        ]);

        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);

        print_r($result);
        die;


    }

    public function actionAdd()
    {
        $user = User::findOne(['is_admin' => 1]);
        $baseUri = 'https://rest.tsheets.com/api/v1/';
        $client = new Client(['base_uri' => $baseUri]);
        $headers = [
            'Authorization' => 'Bearer ' . $user->tsheets_access_token,
            'Accept' => 'application/json',
        ];

        $params = [];
        $params['data'] = [];
        $params['data'][] = (object)[
            'addr1' => '1170 FOSTER CITY Blvd #302, Foster City, CA, 94404',
            'addr2' => '',
            'city' => 'FOSTER CITY',
            'state' => 'CA',
            'zip' => '94404',
            'country' => 'USA',
        ];

        $response = $client->request('POST', 'locations', [
            'headers' => $headers,
            'form_params' => $params
        ]);

        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);

        print_r($result);
        die;


    }
}
