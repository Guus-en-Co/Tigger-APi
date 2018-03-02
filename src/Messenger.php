<?php
/**
 * Created by PhpStorm.
 * User: guus
 * Date: 28/02/2018
 * Time: 12:42
 */

namespace Messenger;

class Messenger {

    public function errorMessage($message, $errorcode)
    {
        //send 400 (bad request)
        return json_encode(array('error' => 'true', 'code' => $errorcode, 'message' => $message));
    }

    public function successMessage($message, $customArray = NULL)
    {
        if (isset($customArray) && !empty($customArray)) return json_encode(array_merge(array('error' => 'false', 'code' => 0, 'message' => $message), $customArray));
        else return json_encode(array('error' => 'false', 'code' => 0, 'message' => $message));
    }
}
