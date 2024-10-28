<?php

namespace app\commands;

use app\models\TimeEntries;
use app\models\User;
use app\services\interfaces\TsheetInterface;
use app\services\TsheetService;
use GuzzleHttp\Client;
use yii\console\Controller;
use yii\console\ExitCode;


class TsheetController extends Controller
{
    private TsheetInterface $tsheetService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->tsheetService = new TsheetService();
    }

    public function actionTimeEntries()
    {
        $date = date('Y-m-d');
        $date = '2020-01-06';

        $queryParams = [
            'date' => $date
        ];
        $result = $this->tsheetService->requestGet('time_off_request_entries', $queryParams);
        $imported = $this->tsheetService->handleTimeEntries($result);

        echo "Imported count: " . count($imported) . PHP_EOL;
        return ExitCode::OK;
    }

    /**
     * Locations list
     */
    public function actionIndex(): int
    {
        $result = $this->tsheetService->requestGet('locations');
        print_r($result);

        return ExitCode::OK;
    }

    /**
     * Add location to tsheets.com
     *
     * @return int
     */
    public function actionAdd(): int
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

        $result = $this->tsheetService->requestPost('locations', $params);
        print_r($result);

        return ExitCode::OK;
    }

    public function actionRefreshToken()
    {
        $this->tsheetService->refreshToken();

        echo "Ok." . PHP_EOL;
        return ExitCode::OK;
    }

}
