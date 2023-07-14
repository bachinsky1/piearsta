<?php
/**
 * @package		Adweb
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2020, BlueBridge Technologies
 */

// ------------------------------------------------------------------------

/**
 * Class SessionManager
 *
 */
class SessionManager
{
    /** @var config */
    protected $cfg;

    /** @var Db */
    protected $db;

    /** @var string */
    protected $table = 'mod_users_sessions';

    /** @var integer */
    protected $maxSessions;

    /** @var integer */
    protected $sessionConsiderExpired;

    /** @var null | string */
    protected $clientIp = null;

    /** @var null | integer */
    protected $userId = null;

    /** @var null | string */
    protected $sessionId = null;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cfg = &loadLibClass('config');
        $this->db = $this->cfg->db;
        $this->maxSessions = $this->cfg->get('maxSessions');
        $this->sessionConsiderExpired = $this->cfg->get('sessionConsiderExpired');

        // if user logged in
        if(isset($_SESSION) && isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $this->userId = $_SESSION['user']['id'];
            $this->clientIp = $_SERVER['REMOTE_ADDR'];
            $this->sessionId = session_id();

            // create or refresh session record
            $res = $this->createSessionRecord($this->userId);

            // check if user profile was disabled and set flag in session if so.
            if(
                !$res['susccess'] &&
                $res['status'] == 'canceled' &&
                in_array($res['reason'], array(SESSION_CANCEL_DISABLED_BY_USER, SESSION_CANCEL_DISABLED_BY_SYSTEM))
            ) {
                // Set flag in session
                // To logout and show popup message
                $_SESSION['profileDisabled'] = true;
            }
        }
    }

    /**
     * @param $userId
     * @return array
     */
    public function createSessionRecord($userId)
    {
        $this->userId = $userId;
        $this->clientIp = $_SERVER['REMOTE_ADDR'];
        $this->sessionId = session_id();

        $checkResult = $this->sessionCheck($this->userId);

        if($checkResult['success']) {

            // create session record
            $data = array(
                'user_id' => $this->userId,
                'session_id' => $this->sessionId,
                'ip_address' => $this->clientIp,
                'is_canceled' => '0',
                'cancelation_reason' => 'NULL',
                'last_used' => date(PIEARSTA_DT_FORMAT, time()),
            );

            // check if less than 5 min left until session expiration
            // and refresh session
            if(isset($_SESSION['sessionExpirationTime']) && (strtotime($_SESSION['sessionExpirationTime']) - 300) <= date(PIEARSTA_DT_FORMAT, time())) {
                refreshSession();
                $lifeTime = $this->cfg->get('apacheSessionLifetime');
                $_SESSION['sessionExpirationTime'] = date(PIEARSTA_DT_FORMAT, time() + $lifeTime * 60);
                $data['created'] = $_SESSION['sessionExpirationTime'];
            }

            $fieldsString = implode(',', array_keys($data));

            $insertArray = array();

            array_walk($data, function ($value, $field) use (&$insertArray) {
                if($value == 'NULL') {
                    $insertArray[] = $value;
                } else {
                    $insertArray[] = "'" . $value . "'";
                }
            });

            $insertString = implode(',', $insertArray);

            $updateArray = array();

            array_walk($data, function ($value, $field) use (&$updateArray) {
                if($value == 'NULL') {
                    $updateArray[] = $field . ' = ' . $value;
                } else {
                    $updateArray[] = $field . ' = ' . "'" . $value . "'";
                }
            });

            $updateString = ' ' . implode(',', $updateArray) . ' ';

            $dbQuery = "INSERT INTO " . $this->table . "
                            (" . $fieldsString . ") 
                        VALUES
                            (" . $insertString . ") 
                        ON DUPLICATE KEY UPDATE " . $updateString;

            doQuery($this->db, $dbQuery);
        }

        return $checkResult;
    }

    /**
     * @param $userId
     * @return array
     */
    public function sessionCheck($userId = null)
    {
        if(!$userId && $this->userId) {
            $userId = $this->userId;
        }

        // check if user profile was disabled ( deleted ) in one of the sessions

        $dbQuery = "SELECT * FROM " . $this->table . "
                    WHERE
                        user_id = " . $userId . " AND 
                        (
                            cancelation_reason = " . SESSION_CANCEL_DISABLED_BY_USER . " OR 
                            cancelation_reason = " . SESSION_CANCEL_DISABLED_BY_SYSTEM . " 
                        )";
        $query = new query($this->db, $dbQuery);

        if($query->num_rows() > 0) {

            return array(
                'success' => false,
                'status' => 'canceled',
                'reason' => '3',
            );
        }

        // sess lifetime in seconds
        $sessLifetime = ($this->cfg->get('sessionTimeout') / 1000);

        // get session records for userId
        $dbQuery = "SELECT * FROM " . $this->table . "
                    WHERE
                        user_id = " . $userId . " AND 
                        is_canceled = 0 AND
                        last_used > '" . date(PIEARSTA_DT_FORMAT, (time() - $sessLifetime)) . "'
                    GROUP BY ip_address";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            $filteredSessArray = $query->getArray();

            if($this->maxSessions && count($filteredSessArray) >= $this->maxSessions) {

                return array(
                    'success' => false,
                    'status' => 'limit',
                    'reason' => 'Max session limit for user exceeded',
                );
            }
        }

        return array(
            'success' => true,
            'status' => 'free',
            'reason' => 'User can login',
        );
    }

    /**
     * @param $userId
     * @param $reason
     * @param string $sessId
     */
    public function sessionAbort($userId, $reason, $sessId = '')
    {
        $where = '';

        if($sessId) {
            $where .= " AND session_id = '" . $sessId . "'";
        }

        $dbQuery = "UPDATE " . $this->table . "
                    SET
                        is_canceled = 1,
                        cancelation_reason = " . $reason . ",
                        last_used = '" . date(PIEARSTA_DT_FORMAT, time()) . "'  
                    WHERE
                        user_id = " . $userId . $where;
        doQuery($this->db, $dbQuery);
    }

}

?>