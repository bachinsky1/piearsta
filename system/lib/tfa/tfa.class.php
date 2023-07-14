<?php

use Dolondro\GoogleAuthenticator\GoogleAuthenticator;
use Dolondro\GoogleAuthenticator\QrImageGenerator\GoogleQrImageGenerator;
use Dolondro\GoogleAuthenticator\SecretFactory;

/**
 * TFA class
 * implements Two-Factor Authentication based on GoogleAuthenticator app
 */
class Tfa
{
    /** @var db */
    private $db;

    /** @var config */
    private $cfg;

    /** @var array */
    private $userData;

    /** @var array $tfaConfig */
    private $tfaConfig;

    /** @var  SecretFactory */
    private $secretFactory;

    public $appName = 'Piearsta.lv';
    public $userFullName;

    public function __construct()
    {
        /** @var  db */
        $this->db = &loadLibClass('db');
        /** @var  config */
        $this->cfg = &loadLibClass('config');

        /** @var array tfaConfig */
        $this->tfaConfig = $this->cfg->get('tfa');

        $this->secretFactory = new SecretFactory();
    }

    public function getTfaInfo()
    {
        if(empty($this->userData) || empty($this->userData['tfa'])) {
            return null;
        }

        return $this->userData['tfa'];
    }

    public function setUserData($userData)
    {
        if(empty($userData)) {
            return null;
        }

        $this->userData = $userData;
        $this->userFullName = (!empty($userData['name']) ? $userData['name'] : '') . ' ' . (!empty($userData['surname']) ? $userData['surname'] : '');
    }

    /**
     * @return array
     */
    private function saveTfaKey($key)
    {
        $res = array(
            'success' => true,
        );

        $dbQuery = "
                INSERT INTO mod_tfa
                    (profile_id, tfa_key)
                VALUES
                    (".$this->userData['id'].", '".$key."')
                ON DUPLICATE KEY UPDATE
                    profile_id = ".$this->userData['id'].",
                    tfa_key = '".$key."';
            ";

        try {

            doQuery($this->db, $dbQuery);

        } catch (Exception $e) {

            $res = array(
                'success' => false,
                'message' => 'Exception occurred',
                'exception' => $e,
                'query' => $dbQuery
            );
        }

        return $res;
    }

    /**
     * @return array|null
     */
    public function generateNewTfa()
    {
        if(empty($this->userData) || empty($this->userData['id'])) {
            return null;
        }

        // get new secret code and save it to session

        $secret = $this->secretFactory->create($this->appName, $this->userFullName);

        // temporary set key to session
        $_SESSION['temp_tfa_key'] = $secret->getSecretKey();

        return array(
            'secret' => $secret->getSecretKey(),
            'qr' => $this->getQrCode($secret),
        );
    }

    /**
     * @param $code
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function checkCodeOnConfigure($code)
    {
        if(empty($_SESSION['temp_tfa_key'])) {
            return array(
                'success' => false,
                'message' => 'No tfa key in session',
            );
        }

        $res = array(
            'success' => false,
            'message' => 'Wrong code',
        );

        $googleAuth = new GoogleAuthenticator();

        if($googleAuth->authenticate($_SESSION['temp_tfa_key'], $code)) {

            $this->saveTfaKey($_SESSION['temp_tfa_key']);
            unset($_SESSION['temp_tfa_key']);

            $res = array(
                'success' => true,
                'message' => 'Code correct',
            );
        }

        return $res;
    }

    /**
     * @param $code
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function checkCodeOnLogin($code)
    {
        if($_SESSION['tfa_login_attempts'] < 1) {

            $res = array(
                'success' => false,
                'message' => 'Login attempts exceeded',
                'attemptsLeft' => 0,
                'logout' => true,
            );

            if(DEBUG) {
                $res['debug'] = array(
                    'userData' => $this->userData,
                    'session' => $_SESSION,
                );
            }

            return $res;
        }

        $key = $this->userData['tfa'];

        if(empty($key)) {

            $res = array(
                'success' => false,
                'message' => 'Tfa not configured',
                'attemptsLeft' => null,
                'logout' => false,
            );

            if(DEBUG) {
                $res['debug'] = array(
                    'userData' => $this->userData,
                    'session' => $_SESSION,
                );
            }

            return $res;
        }

        $googleAuth = new GoogleAuthenticator();

        if($googleAuth->authenticate($key, $code)) {

            unset($_SESSION['tfa_login_attempts']);

            return array(
                'success' => true,
                'message' => 'Code correct',
                'attemptsLeft' => null,
                'logout' => false,
            );

        } else {

            $_SESSION['tfa_login_attempts']--;

            $res = array(
                'success' => false,
                'message' => 'Wrong code',
                'wrongCode' => true,
                'attemptsLeft' => $_SESSION['tfa_login_attempts'],
                'logout' => false,
            );

            if($_SESSION['tfa_login_attempts'] < 1) {
                $res['logout'] = true;
            }

            if(DEBUG) {
                $res['debug'] = array(
                    'userData' => $this->userData,
                    'session' => $_SESSION,
                );
            }

            return $res;
        }
    }

    /**
     * @return array
     */
    public function removeKey($userId = null)
    {
        $u = null;

        if(!empty($this->userData) && !empty($this->userData['id'])) {
            $u = $this->userData['id'];
        }

        if(!empty($userId)) {
            $u = $userId;
        }

        if(empty($u)) {
            return array(
                'success' => false,
            );
        }

        $dbQuery = "SELECT * FROM mod_tfa WHERE profile_id = " . $u;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            $updDbQuery = "
                UPDATE mod_tfa SET tfa_key = null
                WHERE profile_id = " . $u;

            doQuery($this->db, $updDbQuery);
        }

        unset($this->userData['tfa']);
        unset($_SESSION['user']['tfa']);

        return array(
            'success' => true,
        );
    }

    /**
     * @param $secret
     * @return string
     */
    private function getQrCode($secret)
    {
        $qrImageGenerator = new GoogleQrImageGenerator();
        return $qrImageGenerator->generateUri($secret);
    }
}