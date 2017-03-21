<?php
/**
 * Interface for all endpoints
 *
 * Each paymentgateway should have methods will set payment request and process payment
 *
 * @category   billing
 * @package    Payments
 * @version    0.1
 */

use \Exception as Exception;

interface IEndPoint {

    public function setProcessIdentifier($process_identifier);
    public function getProcessIdentifier();

    public function setOptions($options = array());
    public function getOptions();

    public function setTransferMethod($transfer_method);
    public function getTransferMethod();

    public function setAction($action);
    public function getAction();

    public function setRequestContent($request_content);
    public function getRequestContent();

    public function getResponseContent();

    public function process();
}
