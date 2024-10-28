<?php


namespace app\services;

use app\models\TimeEntries;
use app\models\User;
use app\services\interfaces\TsheetInterface;
use GuzzleHttp\Client;
use yii\db\Exception;
use yii\helpers\Url;

class TsheetService implements TsheetInterface
{
    const BASE_URI = 'https://rest.tsheets.com/api/v1/';

    private ?Client $client;
    private array $headers = [];
    private $tsheetConfig;

    public function __construct()
    {
        $this->client = $this->getClient();
        $this->tsheetConfig = \Yii::$app->params['tsheet'];
    }

    public function getAuthUrl(): string
    {
        return substr(Url::toRoute([$this->tsheetConfig['authorizationRequestUrl'],
                'response_type' => 'code',
                'client_id' => $this->tsheetConfig['client_id'],
                'redirect_uri' => $this->tsheetConfig['oauth_redirect_uri'],
                'state' => '12345',
            ]
        ), 1);
    }

    public function exchangeAuthCode(string $code)
    {
        $client = new Client();
        $response = $client->request('POST', $this->tsheetConfig['tokenEndPointUrl'], [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->tsheetConfig['client_id'],
                'client_secret' => $this->tsheetConfig['client_secret'],
                'code' => $code,
                'redirect_uri' => $this->tsheetConfig['oauth_redirect_uri'],
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws Exception
     */
    public function updateUserAuth($data): bool
    {
        $user = User::getMainUser();
        $user->tsheets_access_token = $data['access_token'] ?? '';
        $user->tsheets_refresh_token = $data['refresh_token'] ?? '';
        $user->tsheets_expires_in = $data['expires_in'] ?? '';
        $user->tsheets_realm_id = $data['company_id'] ?? '';
        return $user->save();
    }

    public function refreshToken(): bool
    {
        $user = User::getMainUser();
        $response = $this->client->request('POST', $this->tsheetConfig['tokenEndPointUrl'], [
            'headers' => $this->headers,
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->tsheetConfig['client_id'],
                'client_secret' => $this->tsheetConfig['client_secret'],
                'refresh_token' => $user->tsheets_refresh_token,
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $this->updateUserAuth($result);
    }

    public function getClient(): Client
    {
        $user = User::getMainUser();
        $this->client = new Client(['base_uri' => self::BASE_URI]);
        $this->headers = [
            'Authorization' => 'Bearer ' . $user->tsheets_access_token,
            'Accept' => 'application/json',
        ];
        return $this->client;
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

    public function handleTimeEntries($data): array
    {
        $imported = [];
        $time_off_request_entries = $data['results']['time_off_request_entries'];
        $supplemental_data = $data['supplemental_data'];
        $users = $supplemental_data['users'];
        $timesheets = $supplemental_data['timesheets'] ?? null;
        $jobcodes = $supplemental_data['jobcodes'] ?? null;
        foreach ($time_off_request_entries as $time_off_request_entry) {
            $time_off_request_id = $time_off_request_entry['time_off_request_id'];
            $existsRow = TimeEntries::findOne(['time_off_request_id' => $time_off_request_id]);
            if ($existsRow) {
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