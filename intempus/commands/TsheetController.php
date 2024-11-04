<?php

namespace app\commands;

use app\modules\timeTracker\services\interfaces\AuthInterface;
use app\modules\timeTracker\services\TsheetService;
use yii\console\Controller;
use yii\console\ExitCode;


class TsheetController extends Controller
{
    private AuthInterface $tsheetService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->tsheetService = new TsheetService();
    }

    public function actionTimeEntries()
    {
        $date = date('Y-m-d');
        $date = '2024-10-28';

        $queryParams = [
            'start_date' => $date,
//            'end_date' => $date,
        ];
        $result = $this->tsheetService->requestGet('timesheets', $queryParams);

        foreach($result['results']['timesheets'] as $timesheet){
            $state = $timesheet['state'] ?? '';
            $jobcode_id = $timesheet['jobcode_id'] ?? '';
            $user_id = $timesheet['user_id'] ?? '';
            $notes = $timesheet['notes'] ?? '';
            if ($state == 'SUBMITTED'){
                $queryParams = [
                    'ids' => $user_id,
                ];
                $usersData = $this->tsheetService->requestGet('users', $queryParams);
                $user = $usersData['results']['users'][$user_id];
                $first_name = $user['first_name'] ?? '';
                $last_name = $user['last_name'] ?? '';


                $queryParams = [
                    'ids' => $jobcode_id,
                ];
                $jobsData = $this->tsheetService->requestGet('jobcodes', $queryParams);
                $job = $jobsData['results']['jobcodes'][$jobcode_id];
                $locations = $job['locations'] ?? '';
                $location_id = $locations[0] ?? '';

                if ($location_id){
                    $queryParams = [
                        'ids' => $location_id,
                    ];
                    $locationsData = $this->tsheetService->requestGet('locations', $queryParams);
                    $location = $locationsData['results']['locations'][$location_id];

                }

                print_r($timesheet);
                print_r($user);
                print_r($job);
                print_r($location);
                die;
            }
        }
        print_r($result);
        die;
        
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
