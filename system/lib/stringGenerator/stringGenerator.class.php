<?php

/**
 * String generator
 */
class StringGenerator
{

    public function generate($settledLength)
    {
        $lowercase = "qwertyuiopasdfghjklzxcvbnm";
        $uppercase = "ASDFGHJKLZXCVBNMQWERTYUIOP";
        $numbers = "1234567890";
        $randomCode = "";
        $length = !empty($settledLength) ? $settledLength : 10;

        mt_srand(crc32(microtime()));
        $max = strlen($lowercase) - 1;
        for ($x = 0; $x < abs($length / 3); $x++) {
            $randomCode .= $lowercase[mt_rand(0, $max)];
        }
        $max = strlen($uppercase) - 1;
        for ($x = 0; $x < abs($length / 3); $x++) {
            $randomCode .= $uppercase[mt_rand(0, $max)];
        }

        $max = strlen($numbers) - 1;
        for ($x = 0; $x < abs($length / 3); $x++) {
            $randomCode .= $numbers[mt_rand(0, $max)];
        }

        $randomCode = str_shuffle($randomCode);

        return $this->checkTransactionId($randomCode, $length);
    }

    private function checkTransactionId($randomCode , $length)
    {
        global $mdb;

        $dbQuery = "SELECT * FROM mod_transactions 
                    WHERE payment_nonce = '" . $randomCode . "'";

        $query = new query($mdb, $dbQuery);

        if ($query->num_rows()) {
            return $this->generate($length);
        } else {
            return $randomCode;
        }
    }
}

?>