<?php

use League\OAuth2\Client\Token\AccessToken;

define('TOKEN_FILE', DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'token_info.json');

class AmoCRMTokenActions {
    private $accessToken;
    public $apiClient;
    
    /**
     * @param array $accessToken
     */
    public function saveToken($accessToken) {
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];
    
            file_put_contents(TOKEN_FILE, json_encode($data));
            return true;
        } else {
            //die('Invalid access token ' . var_export($accessToken, true));
            return false;
        };
    }

    /**
     * @return AccessToken
     */
    public function getSavedToken() {
        if (!(file_exists(TOKEN_FILE))) {
            //die('Access token file not found');
            return null;
        };
    
        $accessToken = json_decode(file_get_contents(TOKEN_FILE), true);
    
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            //die('Invalid access token ' . var_export($accessToken, true));
            return null;
        };
    }

    /**
     * @return accessToken
     */
    public function getTokenbyCode($apiClient, $code, $referer) {
        if (isset($referer)) {
            $apiClient->setAccountBaseDomain($referer);
        };
        
        try {
            $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($code);
        
            if (!($accessToken->hasExpired())) {
                return $this->saveToken([
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $apiClient->getAccountBaseDomain(),
                ]);
            };
        } catch (Exception $e) {
            //die((string)$e);
            return false;
        };
    }
};
?>