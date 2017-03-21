<?php
/**
 * Class Token
 *
 * Can do following tasks with both online(single) and batch (single or group of orders)
 * sale, authorization, refund, capture, void
 *
 * @category   PCI
 * @package    Token
 * @version    0.1
 */
use Exception as Exception;

class Token {
    protected $instance = null;

    protected $purpose_id  = null;

    protected $purpose_key = null;

    protected $token_validation_method = null;

    protected $token    = null;

    protected $tail     = null;

    protected $hash     = null;

    public  $error      = array();

    const PURPOSE_CREDIT_CARD = 1;
    const PURPOSE_SOCIAL_SECURITY = 2;

    /**
     * DB object that will be used for queries
     * will load PDO
     * @var PDO object
     */
    private $db = null;

    public function __construct () {
        $this->db = new \PDO("mysql:host=" . DB_HOST . ";dbname=pci", DB_USER, DB_PASS);
    }

    /**
     * create token from string that been passed in and purpose that been defined
     * This will throw an exception on invalid input and also on db errors
     *
     * @param string $string
     * @param string $region
     * @return token inserted
     */
    public function createToken ($string, $region = 'US') {
        if (!$this->validateRegion($region)) {
            throw new \Exception('Invalid region specified');
        }

        $this->setPurposeInfo($this->purpose_id);

        //for additional cc card, ssn check validation first, if true, encrypt
        $valid = call_user_func( array($this, $this->token_validation_method), $string);

        if ($valid) {
            $this->setTail ($string);

            $token = self::generateToken();
            $this->setToken($token);

            $hash = $this->encrypt(trim($string), $this->purpose_key);
        }
        else {
            throw new \Exception('Passed string is not valid for token purpose');
        }

        $sql = "INSERT INTO tokens (hash, tail, purpose_id, token, region) VALUES (?, ?, ?, unhex(?), ?)";
        $this->query($sql, array($hash, $this->tail, $this->purpose_id, $token, $region));

        return $token;
    }

    /**
     * set token purpose id
     *
     * @param $id int
     */
    public function setPurposeId($id) {
        $this->purpose_id = (int) $id;
    }

    /**
     * set token purpose key, validation method stuff from purpose_id
     *
     * @param $purpose_id int
     */
    public function setPurposeInfo($purpose_id) {
        $query =  sprintf("SELECT validation_method, s_key FROM pci.purposes WHERE purpose_id = ? LIMIT 1");
        $row = $this->query($query, array($purpose_id));

        if ($row) {
            $this->purpose_key = $row[0]['s_key'];
            $this->token_validation_method =  $row[0]['validation_method'];
        }
        else {
            throw new \Exception('Purpose information can not be found for this purpose id ' . $purpose_id);
        }
    }

    /**
     * set token tail from original
     *
     * @param $id int
     */
    public function setTail ($string) {
        $this->tail = substr(trim($string), -4);
    }

    public function getTail() {
        return $this->tail;
    }

    public function getToken () {
        return $this->token;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * get original string encrypt using the key provided - eack key is tied with the purpose
     *
     * @param string $string
     * @param string $key
     */
    private function encrypt ($string, $key) {
       return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
    }

    /**
     * get decrypt string back to original string using the key provided - eack key is tied with it purpose entry
     *
     * @param string $string
     * @param string $key
     */
    private function decrypt ($string, $key) {
       return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
    }

    /**
     * get original string (cc_card, ssn) from token string
     *
     * @param string $token_string
     */
    public function setOriginFromToken($token_string) {
        //get hash and key from id
        $query =  sprintf("SELECT hash,  s_key FROM my_schema.tokens JOIN my_schema.purposes ON purposes.purpose_id = tokens.purpose_id
                            WHERE token = unhex(?)");
        $row = $this->query($query, array($token_string));

        if ($row) {
            $this->updateAccessDate($token_string);
            return $this->decrypt($row[0]['hash'], $row[0]['s_key']);
        } else {
            throw new Exception('Unable to find origin string from token: ' . $token_string);
        }
    }

    public function updateAccessDate($token_string) {
        $query = sprintf("UPDATE my_schema.tokens SET accessed_at = NOW() WHERE token = unhex(?)");
        $this->query($query, array($token_string));
    }

    /**
     * insert token info
     *
     * @param array $save_array
     * @return inserted id
     */
    protected function insertToken($save_array) {
        if (is_array($save_array) && (!empty($save_array))) {
            return $this->queryInsert('my_schema.tokens', $save_array);
        } else {
            return null;
        }
    }

    /**
     * Validate a credit card number using the Luhn algorithm
     *
     * @param $number cc card number
     * @return boolean
     */
    protected function validateLuhn($number) {
        $number = preg_replace('/\D/', '', $number);

        // Set the string length and parity
        $number_length  = strlen($number);

        if ($number_length < 12)
            return FALSE;

        $parity         = $number_length % 2;

        // Loop through each digit and do the maths
        $total = 0;
        for ($i = 0; $i < $number_length; $i++) {
            $digit = $number[$i];
            // Multiply alternate digits by two
            if ($i % 2 == $parity) {
                $digit *= 2;
                // If the sum is two digits, add them together (in effect)
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            // Total up the digits
            $total += $digit;
        }

        // If the total mod 10 equals 0, the number is valid
        return ($total % 10 == 0) ? TRUE : FALSE;
    }

    /**
     * Checksum algorighm to validate South Korean RRN
     *
     * @link http://en.wikipedia.org/wiki/Resident_registration_number
     * @param type $rrnNum
     * @return boolean
     */
    protected function validateKoreanRrn($rrnNum) {
        $matchRegEx = '/^([0-9]){13}$/';

        if (! preg_match($matchRegEx, $rrnNum)) {
                return false;
        }

        $ra = str_split($rrnNum);

        $f1 = (2 * $ra[0]) + (3 * $ra[1]) + (4 * $ra[2]) + (5 * $ra[3]) + (6 * $ra[4]) + (7 * $ra[5]) + (8 * $ra[6]) + (9 * $ra[7]) + (2 * $ra[8]) + (3 * $ra[9]) + (4 * $ra[10]) + (5 * $ra[11]);

        $f2 = ((11 - ($f1 % 11))) % 10;

        if($f2 == $ra[12]){
            return true;
        }

        return false;
    }

    protected function validateNone($string) {
        return true;
    }

    protected function validateSsn( $number ){
        if( strlen( $number ) != 9 ){
            return false;
        }
        return true;
    }

    protected function validateRegion($region) {
        return in_array($region, array('US', 'EU', 'KR'));
    }

    /**
     * select database tables and return the selected rows
     *
     * @access public
     * @param type $query query of string
     *
     */
    public function query($query, $vars = array())
    {
        $p = $this->db->prepare($query);
        $p->execute($vars);
        return $p->fetchAll();
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    public static function generateToken() {
        $token = strtoupper(str_replace('-', '', self::generateUuid()));
        return $token;
    }

    public static function generateUuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    protected function __clone() {}

}
