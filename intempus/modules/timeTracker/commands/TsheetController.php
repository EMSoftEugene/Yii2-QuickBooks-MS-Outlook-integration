<?php

namespace app\modules\timeTracker\commands;

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\services\TsheetDataService;
use Yii;
use yii\console\ExitCode;
use yii\console\Controller;

/**
 * Class TsheetController
 */
class TsheetController extends Controller
{
    private TsheetDataService $apiDataService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->apiDataService = new TsheetDataService();
    }

    public function actionUsers()
    {
        $users = $this->apiDataService->getUsers();

        $addedNewUsers = 0;
        if ($users) {
            $addedNewUsers = $this->apiDataService->saveNewUsers($users);
        }

        echo "Successful added $addedNewUsers new Users\n";
        return ExitCode::OK;
    }

    public function actionGeolocations()
    {
        $date = '2024-11-08';
        $date = new \DateTime();
        $date = $date->format('Y-m-d');

        $geolocations = $this->apiDataService->getGeolocations($date);

        $addedNewGeolocations = 0;
        if ($geolocations) {
            $addedNewGeolocations = $this->apiDataService->saveNewGeolocations($geolocations);
        }

        echo "Successful added $addedNewGeolocations new Geolocations\n";
        return ExitCode::OK;
    }

    public function actionUsersRaw()
    {
        $users = $this->apiDataService->getUsersRaw();

        $addedNewUsers = 0;
        if ($users) {
            $addedNewUsers = $this->apiDataService->saveNewUsersRaw($users);
        }

        echo "Successful added $addedNewUsers new Users\n";
        return ExitCode::OK;
    }

}
