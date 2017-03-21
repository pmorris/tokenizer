<?php

/**
 * Acts as a wrapper for external endpoints which decrypt and replace any tokenized values
 *
 * @param   end_point (string) A key corresponding to the endpoint sub class
 * @param   transfer_method (string:online|batch) An optional processing instruction utilized by some endpoint subclasses
 * @param   action (capture|sale|refund|auth|void) An optional processing instruction used by some enpoint classes
 * @param   process_identifier Used for batch requests to name request and response files
 * @param   content
 *
 * @return  Will print the standard response from the payment gateway.  On error, will return a 403 status
 */

include(dirname(dirname(__FILE__)) . '/inc/init.php');

$data = RestUtils::processRequest();
$method = $data->getMethod();
$request = $data->getRequestVars();

$referer = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
}

switch ($method) {
    case 'get':
    case 'post':
        try {
            if (!property_exists($data->getRequestVars(), 'endpoint') || !\EndPoint::isValidName($request->endpoint)) {
                throw new \Exception('Invalid endpoint');
            }
            else{
                //if endpoint is valid, use the child class
                $endpointClass = ucwords($request->endpoint);
            }

            if (!property_exists($data->getRequestVars(), 'transfer_method') || !$endpointClass::isValidTransferMethod($request->transfer_method)) {
                throw new \Exception('Invalid transfer method');
            }

            if (!property_exists($data->getRequestVars(), 'action') || !$endpointClass::isValidAction($request->action)) {
                throw new \Exception('Invalid action');
            }

            if (!property_exists($data->getRequestVars(), 'content')) {
                throw new \Exception('Invalid or missing content parameter');
            }

            $process_identifier = '';
            if (property_exists($data->getRequestVars(), 'process_identifier')) {
                $process_identifier = $data->getRequestVars()->process_identifier;
            }

            $options = array();
            if (isset($endpoint_config[$request->endpoint][$request->transfer_method])) {
                $options = $endpoint_config[$request->endpoint][$request->transfer_method];
            }

            error_log("Process Request endpoint: {$request->endpoint}, transfer: {$request->transfer_method}, action: {$request->action}, process_id: {$process_identifier}, IP: " . $_SERVER['REMOTE_ADDR'] . ", Referer: " . $referer);

            $end_point = \EndPointFactory::getEndPoint($request->endpoint);
            $end_point->setProcessIdentifier($process_identifier);
            $end_point->setOptions($options);
            $end_point->setTransferMethod($request->transfer_method);
            $end_point->setAction($request->action);
            $end_point->setRequestContent($request->content);

            $end_point->process();
            $response = $end_point->getResponseContent();
            $content_type = 'text/plain';

            if (preg_match("/^Error/", $response)) {
                throw new \Exception($response);
            }

            if (property_exists($data->getRequestVars(), 'verbose') && $request->verbose == true){
                $content_type = 'text/html';
                $response = '<html><head><title>process</title></head><body>' . htmlentities($response)
                            . '<hr/><h2>Request Content</h2>';

                if (($xml = simplexml_load_string($end_point->getRequestContent())) !== false){
                    $response .= '<textarea style="border:0;width:900px;height:100%;">' . $xml->asXML() . '</textarea>';
                }
                else{
                    $response .= var_export($end_point->getRequestContent(), true);
                }

                $response .= '</body></html>';
            }

            RestUtils::sendResponse(200, $response, $content_type);

        } catch (\Exception $e) {
            $response = "Error: " . $e->getMessage();
            $response_code = 403;
            RestUtils::sendResponse(403, $response, $content_type);
        }

        break;
}
