<?php


namespace app\modules\timeTracker\services;

use app\models\AuthApi;
use app\models\TimeEntries;
use app\models\User;
use app\modules\timeTracker\services\interfaces\ApiInterface;
use GuzzleHttp\Client;
use yii\db\Exception;
use yii\helpers\Url;

class TsheetService implements ApiInterface
{
    const NAME = 'tsheet';
    private ?Client $client = null;
    private array $headers = [];
    private array $params = [];
    private AuthApi $authApi;

    public function __construct()
    {
        $module = \Yii::$app->getModule('timeTracker');
        $this->params = $module->params['tsheet'];
        $this->authApi = AuthApi::getOrSetAuthApi(self::NAME);
        $this->client = $this->getClient();
    }

    public function getAuthUrl(): string
    {
        return $this->params['authorizationRequestUrl'] .
            str_replace('/'. $this->params['moduleUrl'] . '/'.self::NAME, '', Url::toRoute(['',
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
        $this->authApi->access_token = $data['access_token'] ?? '';
        $this->authApi->refresh_token = $data['refresh_token'] ?? '';
        $this->authApi->expires_in = $data['expires_in'] ?? '';
        $this->authApi->realm_id = $data['company_id'] ?? '';
        $result = $this->authApi->save();
        $this->authApi = AuthApi::getOrSetAuthApi(self::NAME);
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
                'refresh_token' => $this->authApi->refresh_token,
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $this->updateUserAuth($result);
    }

    public function getClient(): ?Client
    {
        if ($this->client) {
            return $this->client;
        }
        if ($this->authApi->access_token) {
            $this->client = new Client(['base_uri' => $this->params['base_api_url']]);
            $this->headers = [
                'Authorization' => 'Bearer ' . $this->authApi->access_token,
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


    public function handleTimeSheet($data): array
    {
        $imported = [];
        $timesheets = $data['results']['timesheets'] ?? [];

        foreach ($timesheets as $timesheet) {
            $timesheet_id = $timesheet['id'];
            $existsRow = TimeEntries::findOne(['time_off_request_id' => $timesheet_id]);
            if ($existsRow) {
                continue;
            }
            $state = $timesheet['state'] ?? '';
            $jobcode_id = $timesheet['jobcode_id'] ?? '';
            $user_id = $timesheet['user_id'] ?? '';
            $notes = $timesheet['notes'] ?? '';
            $duration = $timesheet['duration'] ?? '';
            $date = $timesheet['date'] ?? '';

            if ($state == 'SUBMITTED') {
                if ($user_id) {
                    $queryParams = [
                        'ids' => $user_id,
                    ];
                    $usersData = $this->requestGet('users', $queryParams);
                    $tUser = $usersData['results']['users'][$user_id] ?? null;
                    $first_name = $tUser['first_name'] ?? '';
                    $last_name = $tUser['last_name'] ?? '';
                }

                if ($jobcode_id) {
                    $queryParams = [
                        'ids' => $jobcode_id,
                    ];
                    $jobsData = $this->requestGet('jobcodes', $queryParams);
                    $job = $jobsData['results']['jobcodes'][$jobcode_id] ?? null;
                    $locations = $job['locations'] ?? '';
                }
                $location_id = $locations[0] ?? '';
                $location = null;

                if ($location_id) {
                    $queryParams = [
                        'ids' => $location_id,
                    ];
                    $locationsData = $this->requestGet('locations', $queryParams);
                    $location = $locationsData['results']['locations'][$location_id] ?? null;
                }

                $timeEntries = new TimeEntries();
                $timeEntries->time_off_request_id = $timesheet_id;
                $timeEntries->date = $date;
                $timeEntries->duration = $duration;
                $timeEntries->approver_id = 1;
                $timeEntries->approver_last_name = '-';
                $timeEntries->approver_first_name = '-';
                $timeEntries->user_id = $user_id;
                $timeEntries->user_last_name = $last_name;
                $timeEntries->user_first_name = $first_name;
                $timeEntries->timesheet_notes = $notes;
                $timeEntries->location_addr = $location ? trim($location['addr1'] . ' ' . $location['addr2']) : '';
                $timeEntries->location_city = $location ? $location['city'] : '';
                $timeEntries->location_state = $location ? $location['state'] : '';
                $timeEntries->location_zip = $location ? $location['zip'] : '';
                $timeEntries->location_country = $location ? $location['country'] : '';

                if ($timeEntries->save()) {
                    $imported[] = $timesheet_id;
                }
            }
        }

        return $imported;
    }

    public function handleTimeEntries($data): array
    {
        $imported = [];
        $time_off_request_entries = $data['results']['time_off_request_entries'];
        $supplemental_data = $data['supplemental_data'];
        $users = $supplemental_data['users'] ?? null;
        $timesheets = $supplemental_data['timesheets'] ?? null;
        $jobcodes = $supplemental_data['jobcodes'] ?? null;
        foreach ($time_off_request_entries as $time_off_request_entry) {
            $time_off_request_id = $time_off_request_entry['time_off_request_id'];
            $existsRow = TimeEntries::findOne(['time_off_request_id' => $time_off_request_id]);
            if ($existsRow || !$users) {
                continue;
            }

            $duration = $time_off_request_entry['duration'];
            $status = $time_off_request_entry['status'];
            $approver_user_id = $time_off_request_entry['approver_user_id'];
            $user_id = $time_off_request_entry['user_id'];
            $approved_timesheet_id = $time_off_request_entry['approved_timesheet_id'];
            $date = $time_off_request_entry['date'];
            $jobcode_id = $time_off_request_entry['jobcode_id'];

            if ($status == 'approved') {
                $approver = $users[$approver_user_id];
                $tUser = $users[$user_id];
                $timesheet = $timesheets[$approved_timesheet_id] ?? null;
                $jobcode = $jobcodes[$jobcode_id] ?? null;
                $locations = $jobcode['locations'] ?? null;
                $location = $locations[0] ?? null;

                $timeEntries = new TimeEntries();
                $timeEntries->time_off_request_id = $time_off_request_id;
                $timeEntries->date = $date;
                $timeEntries->duration = $duration;
                $timeEntries->approver_id = $approver['id'];
                $timeEntries->approver_last_name = $approver['last_name'];
                $timeEntries->approver_first_name = $approver['first_name'];
                $timeEntries->user_id = $tUser['id'];
                $timeEntries->user_last_name = $tUser['last_name'];
                $timeEntries->user_first_name = $tUser['first_name'];
                $timeEntries->timesheet_notes = $timesheet['notes'] ?? '';
                $timeEntries->location_addr = $location ? trim($location['addr1'] . ' ' . $location['addr2']) : '';
                $timeEntries->location_city = $location ? $location['city'] : '';
                $timeEntries->location_state = $location ? $location['state'] : '';
                $timeEntries->location_zip = $location ? $location['zip'] : '';
                $timeEntries->location_country = $location ? $location['country'] : '';

                if ($timeEntries->save()) {
                    $imported[] = $time_off_request_id;
                }
            }
        }

        return $imported;
    }

}