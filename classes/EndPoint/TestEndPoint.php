<?php
/**
 * Class for all Test endpoint process
 *
 *
 * @category   billing
 * @package    Payments
 * @version    0.1
 */

class TestEndPoint extends EndPoint implements IEndPoint {
    /**
     * Use this to set database and connection parameters needed every time
     * this class is run.  Options are set in /inc/config.php for each
     * payment gateway and transfer method
     *
     * @param   array   $options
     **/
    public function setOptions($options = array()) {
        $this->options = $options;
    }

    public function getOptions() {
        return $this->options;
    }

    /**
     * Use this to specify either an online (single) or batch type of transfer.
     *
     * @param   string $transfer_method (online|batch)
     **/
    public function setTransferMethod($transfer_method) {
        $this->transfer_method = $transfer_method;
    }

    public function getTransferMethod() {
        return $this->transfer_method;
    }

    /**
     * Use this to specify the type of transaction this represents.  It may not
     * be useful right now, but could potentially be used to do action-specific
     * processing on a request/response.
     *
     * @param   string  $action (capture|refund|auth|sale|void)
     **/
    public function setAction($action) {
        $this->action = $action;
    }

    public function getAction() {
        return $this->action;
    }

    /**
     * Use this for batch transfers to be able to keep track of information
     * or responses.  This can be used for creating unique files or rows in databases.
     * It is fully up to the individual gateway class what to do with it.
     *
     * @param   string  $process_identifier
     **/
    public function setProcessIdentifier($process_identifier) {
        $this->process_identifier = $process_identifier;
    }

    public function getProcessIdentifier() {
        return $this->process_identifier;
    }

    /**
     * This stores the request content blob as a string.  Before storing it,
     * we replace any token strings with their respective sensitive data.  Token
     * strings look like %TOKEN[token_string]%.
     *
     * @param   string  $request_content
     **/
    public function setRequestContent($request_content) {
        $this->request_content = $this->replaceTokenStrings($request_content);
    }

    public function getRequestContent() {
        return $this->request_content;
    }

    /**
     * Process XML request and get the response
     *
     * @acess public
     */
    public function process() {
        if (APPLICATION_ENV != 'local') {
            throw new \Exception('Unable to run Test Endpoint on a production environment');
        }

        $this->response_content = $this->request_content;
    }

    /**
     * Get the response content
     *
     * @access public
     * @return string
     */
    public function getResponseContent() {
        return trim($this->response_content);
    }
}
