<?php
/**
 * Purpose
 *
 * @author     Phil Morris
 * @category   PCI
 * @package    Purpose
 * @version    0.1
 */
class Purpose {

    const LOOKUP_NONE = 0; // look not allowed
    const LOOKUP_MATCH = 0x01; // a supplied value can be confirmed with a token
    const LOOKUP_RETURN = 0x02; // a token can be decrypted

    protected $purpose_id;
    protected $s_key;
    protected $validation_method;
    protected $allow_lookup;

    /**
     * Instantiate a new Purpose from a purpose_id
     *
     * @static
     * @access public
     * @param int The purpose ID
     * @return Purpose
     */
    public static function factory($purpose_id) {
        $db = new \PDO("mysql:host=" . DB_HOST . ";dbname=pci", DB_USER, DB_PASS);
        $query = 'SELECT * FROM `purposes` WHERE `purpose_id` = ? LIMIT 1';
        $p = $db->prepare($query);
        $p->execute(array($purpose_id));
        return $p->fetchObject(__CLASS__);
    }

    public function getPurposeId() {
        return $this->purpose_id;
    }
    public function getSKey() {
        return $this->s_key;
    }
    public function getValidationMethod() {
        return $this->validation_method;
    }
    public function getAllowLookup() {
        return $this->allow_lookup;
    }
}
