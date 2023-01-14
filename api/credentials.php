<?php

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\Dotenv\Dotenv;

//вывод ответа
require_once __DIR__ . '/outputResponse.php';

//AmoCRM
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/token_actions.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$clientId = $_ENV['CLIENT_ID'];
$clientSecret = $_ENV['CLIENT_SECRET'];
$redirectUri = $_ENV['CLIENT_REDIRECT_URI'];

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

$amoCRMTokenActions = new AmoCRMTokenActions();
if (isset($_GET["code"]) && isset($_GET['referer'])) {
    $accessToken = $amoCRMTokenActions->getTokenbyCode($apiClient, $_GET['code'], $_GET['referer']);
    if (!($accessToken)) {
        outputResponse(false, 'Ошибка получения и сохранения accessToken AmoCRM');
    };
};
$accessToken = $amoCRMTokenActions->getSavedToken();
if (!($accessToken)) {
    outputResponse(false, 'Ошибка получения сохраненного accessToken AmoCRM');
};

//назначение accessToken
($apiClient->setAccessToken($accessToken)
    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
    ->onAccessTokenRefresh(
        function (AccessTokenInterface $accessToken, string $baseDomain) {
            $amoCRMTokenActions->saveToken(
                [
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $baseDomain,
                ]
            );
        }
    )
);
?>