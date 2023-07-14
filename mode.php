<?php
$configData = file_get_contents('mode_config.json');

$data = json_decode($configData, 1);

if (!empty($data)) {

    if (isset($data['maintenance']) && $data['maintenance']){
        if (isset($_SERVER["CONTENT_TYPE"]) && !empty($_SERVER["CONTENT_TYPE"])){
            http_response_code(503);
            exit();
        } else {
            include('maintenance.php');
            exit();
        }
    }

    if (!empty($data['next_maintenance'])){
        if (new DateTime() < new DateTime($data['next_maintenance'])){
            define('MAINTENANCE_WARNING', true);
        }
    }
}
