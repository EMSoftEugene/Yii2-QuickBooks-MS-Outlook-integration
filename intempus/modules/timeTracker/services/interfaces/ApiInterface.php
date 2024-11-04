<?php


namespace app\modules\timeTracker\services\interfaces;

use GuzzleHttp\Client;

interface ApiInterface
{
    public function getAuthUrl(): string;

    public function exchangeAuthCode(string $code);

    public function updateUserAuth(array $data): bool;

    public function refreshToken(): bool;

    public function getClient(): ?Client;

    public function requestGet(string $url, array $queryParams = []);

    public function requestPost(string $url, array $params = []);


}