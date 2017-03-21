<?php
/**
 * abstract Class for payment process
 *
 * @abstract
 */

abstract class EndPoint {
    const AUTHNET   = 'authnet';
    const PAYPAL    = 'paypal';
    const FEDEX_US  = 'fedex';
    const FEDEX_WW  = 'fedexintl'
    const TEST      = 'test';

    const TRANSFER_METHOD_BATCH  = 'batch';
    const TRANSFER_METHOD_ONLINE = 'online';

    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_CAPTURE   = 'capture';
    const ACTION_REFUND    = 'refund';
    const ACTION_VOID      = 'void';

    private $options = array();
    private $transfer_method;
    private $action;
    private $process_identifier;

    public static function getValidNames() {
        return array(
            self::AUTHNET,
            self::PAYPAL,
            self::FEDEX_US,
            self::FEDEX_WW,
            self::TEST // only available on local
        );
    }

    public static function isValidName($name) {
        return in_array($name, self::getValidNames());
    }

    public static function getValidTransferMethods() {
        return array(
            self::TRANSFER_METHOD_BATCH,
            self::TRANSFER_METHOD_ONLINE
        );
    }

    public static function isValidTransferMethod($transfer_method) {
        return in_array($transfer_method, static::getValidTransferMethods());
    }

    public static function getValidActions() {
        return array(
            self::ACTION_AUTHORIZE,
            self::ACTION_CAPTURE,
            self::ACTION_REFUND,
            self::ACTION_VOID
        );
    }

    public static function isValidAction($action) {
        return in_array($action, static::getValidActions());
    }

    /**
     * Looks for tokens in request content with the pattern of %TOKEN[token_string]% and replaces
     * them with the actual value (cc number or ssn)
     **/
    public static function replaceTokenStrings($content) {
        $pattern = "/\%TOKEN\[(.*?)\]\%/";
        preg_match_all($pattern, $content, $matches);

        $token_strings = array();
        if ($matches) {
            $token_strings = $matches[1];

            $token = new \Token();
            $searches = $replacements = array();
            foreach ($token_strings as $token_string) {
                $value = $token->setOriginFromToken($token_string);

                $searches[] = "%TOKEN[{$token_string}]%";
                $replacements[] = $value;
                error_log("Token Replaced: {$token_string}");
            }

            $content = str_replace($searches, $replacements, $content);
        }

        return $content;
    }
}
