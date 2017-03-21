<?php
/**
 * Lookup or verify a token
 *
 * @author Phil Morris <pmorris96@gmail.com>
 *
 * @param   token (string) - A unique hash for a token
 * @param   purpose_id (integer) - Identifes the token type
 * @param   origin_string (string) - An original string to compare with the token
 *
 * @return  A json object which will contain 'origin_string' or 'valid', depending on the token type
 */

include(dirname(dirname(__FILE__)) . '/inc/init.php');

$data = RestUtils::processRequest();
$method = $data->getMethod();
$request = $data->getRequestVars();
$response_code = 200;

$returner = array();

switch($method) {
    case 'get':
    case 'post':
        if (!is_object($request)) {
            $returner['status'] = 'error';
            $returner['error'] = 'Data not received';
            $response_code = 400;
            break;
        }

        $token_string  = $request->token;
        $purpose_id    = $request->purpose_id;

        $purpose = \Purpose::factory($purpose_id);

        try {
            if (!($purpose instanceof \Purpose)) {
                throw new Exception('Invalid purpose_id');
                $response_code = 400;
            }

            $purpose_access = $purpose->getAllowLookup();
            if ($purpose_access == \Purpose::LOOKUP_NONE) {
                $response_code = 403;
                throw new Exception('Action not allowed');
            }

            $token = new \Token;
            $value = $token->setOriginFromToken($token_string);

            $returner['status'] = 'success';
            if ($purpose_access & \Purpose::LOOKUP_RETURN) {
                $returner['orign_string'] = $value;
            } elseif ($purpose_access & \Purpose::LOOKUP_MATCH) {
                $returner['valid'] = (isset($request->origin_string) && ($value == $request->origin_string));
            }
        } catch (Exception $e) {
            $returner['status'] = 'error';
            $returner['error'] = $e->getMessage();
        }
        break;
}

RestUtils::sendResponse($response_code, json_encode($returner), 'application/json');