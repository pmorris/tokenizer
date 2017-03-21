<?php

/**
 * An HTTP interface for tokenizing sensitive information
 *
 * @author  Phil Morris <pmorris96@gmail.com>
 *
 * @param   purpose_id (integer) - Identifies the token type
 * @param   origin_string (string) - The string to be tokenized
 * @param   region (string) - The country or region (e.g. US)
 * @param   callback (string) - A function name to be used as a javascript callback
 *
 * @return  An json or JSONP response containing a tokenized value, on success
 */

include(dirname(dirname(__FILE__)) . '/inc/init.php');

$data = RestUtils::processRequest();
$method = $data->getMethod();
$response_code = 200;

switch($method) {
    case 'get':
    case 'post':
        $request = $data->getRequestVars();
        if (!is_object($request)) {
            $returner = array(
                'status'=> 'error',
                'error' => 'Data not received'
            );
            $response_code = 400;
            break;
        }

        $purpose_id = $request->purpose_id;
        $origin_string = $request->origin_string;
        $region = 'US';
        if (property_exists($request, 'region')) {
            $region = $request->region;
        }

        try {
            $token = new Token();
            $token->setPurposeId($purpose_id);

            $token_string = $token->createToken($origin_string, $region);
            $returner = array('token' => $token_string);

            $referer = '';
            if (isset($_SERVER['HTTP_REFERER'])) {
                $referer = $_SERVER['HTTP_REFERER'];
            }

        } catch (\Exception $e) {
            $returner = array(
                'status' => 'error',
                'error' => $e->getMessage()
            );
            $response_code = 500;
            error_log("Token Creation Error: " . $e->getMessage());
        }
        break;
}

if (isset($_REQUEST['callback']) && (strlen($_REQUEST['callback']) >2)) {
    $body = $_REQUEST['callback'] . '(' . json_encode($returner) . ')';
    $content_type = 'application/javascript';
} else {
    $body = json_encode($returner);
    $content_type = 'application/json';
}

RestUtils::sendResponse($response_code, $body, $content_type);