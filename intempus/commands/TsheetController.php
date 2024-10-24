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

    public function actionAdd()
    {
        $user = User::findOne(['is_admin' => 1]);
        $tsheetConfig = \Yii::$app->params['tsheet'];

        $baseUri = 'https://rest.tsheets.com/api/v1/';
        $client = new Client(['base_uri' => $baseUri]);
        $headers = [
            'Authorization' => 'Bearer ' . $user->tsheets_access_token,
            'Accept' => 'application/json',
        ];
        $response = $client->request('GET', 'locations', [
            'headers' => $headers,
        ]);

        print_r($response->getBody());
        die;


    }
}
