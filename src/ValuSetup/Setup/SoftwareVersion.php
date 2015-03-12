<?php
namespace ValuSetup\Setup;

/**
 * SoftwareVersion class provides tools for comparing
 * and normalizing version numbers
 */
class SoftwareVersion
{
    const DEFAULT_UNRESOLVED_VERSION = '0.1';
    
    const DEV_PREFIX = 'dev-';
    
    const DELIMITER = '.';
    
    /**
     * Version string
     *
     * Version can be for example:
     * - 1.0
     * - 0.1
     * - 1.10
     * - 1.1.11a
     *
     * @var string
     */
    protected $version;
    
    /**
     * Array of version numbers
     * 
     * @var array
     */
    protected $numbers = array();
    
    /**
     * Reg exp pattern for validating version string
     *
     * @var string
     */
    protected static $validPattern = '^([0-9]+\.?)+[\_\-]?([a-z]+[0-9]*)?$';
    
    public function __construct ($version)
    {
        $this->setVersion($version);
    }
    
    /**
     * Set current version
     *
     * @param $version string|int
     *            Version (float/integer is casted to string)
     * @return SoftwareVersion
     */
    public function setVersion ($version)
    {
        $version = strtolower(strval($version));
        if (! self::isValid($version)) {
            $version = self::DEFAULT_UNRESOLVED_VERSION;
        }
        
        $this->numbers = self::parse($version);
        $this->version = $version;
        return $this;
    }
    
    /**
     * Retrieve current version as a string representation, in
     * slightly normalized form
     *
     * Normalization means, that the version is returned as a string
     * (even if originally provided as a float) and it is transformed
     * into lowercase.
     *
     * @return string
     */
    public function getVersion ()
    {
        return $this->version;
    }
    
    /**
     * Retrieve current version in numeric string representation
     *
     * @return string Numeric version (e.g. "1.01a" becomes "1.1")
     */
    public function getNumeric ()
    {
        return implode(self::DELIMITER, $this->numbers);
    }
    
    /**
     * Test if current version is equal to given
     * version
     *
     * Version is always normalized before comparison, which
     * means that "1.1" equals to "1.1a" and float 1.0 equals to "1".
     *
     * @param $version string           
     * @return boolean
     */
    public function isEqualTo ($version)
    {
        return $this->compareTo($version) === 0;
    }
    
    /**
     * Test if current version is greater than given
     * version
     *
     * @param $version string           
     * @return boolean
     */
    public function isGt ($version)
    {
        return $this->compareTo($version) > 0;
    }
    
    /**
     * Test if current version is greater than or equal to given
     * version
     *
     * @param $version string
     * @return boolean
     */
    public function isGte ($version){
        return $this->compareTo($version) >= 0;
    }
    
    /**
     * Test if current version is less than given
     * version
     *
     * @param $version string           
     * @return boolean
     */
    public function isLt ($version)
    {
        return $this->compareTo($version) < 0;
    }
    
    /**
     * Test if current version is less than or equal to given
     * version
     *
     * @param $version string
     * @return boolean
     */
    public function isLte($version){
        return $this->compareTo($version) <= 0;
    }
    
    /**
     * Compare current version to given version and
     * return -1 if current is less and +1 if current
     * version is greater than given version
     *
     * @param $version string           
     * @return array
     */
    public function compareTo($version)
    {
        $cmp = self::parse($version);
        $result = $this->cmp($cmp, $this->numbers);
        if ($result === 0) {
            $result = $this->cmp($this->numbers, $cmp);
            $result = - $result;
        } else {
            return $result;
        }
        return $result;
    }
    
    /**
     * Get normalized string representation of version number
     */
    public function __toString()
    {
        return $this->getVersion();
    }
    
    /**
     * Compare two version strings and return
     * -1 if $v1 is less and +1 if $v1 is greater
     * than $v2. Return 0 if versions are equal.
     * 
     * @param string $v1
     * @param string $v2
     * @return int
     */
    public static function compare($v1, $v2){
        $v1 = new SoftwareVersion($v1);
        $v2 = new SoftwareVersion($v2);
        
        return $v1->compareTo($v2);
    }
    
    /**
     * Parse array of integers based on version
     * number
     *
     * Non-integer values and preceeding zeros are
     * dropped.
     *
     * @param $version string           
     * @return array
     */
    public static function parse ($version)
    {
        $nums = explode(self::DELIMITER, $version);
        $numbers = array();
        foreach ($nums as $num) {
            if (preg_match('/^0*([0-9]+)/', $num, $matches)) {
                if (isset($matches[1])) {
                    $numbers[] = $matches[1];
                } else {
                    $numbers[] = 0;
                }
            } else {
                break;
            }
        }
        if (! sizeof($numbers)) {
            $numbers = array(0);
        }
        return $numbers;
    }
    
    /**
     * Test if version is valid
     *
     * @return boolean
     */
    public static function isValid ($version)
    {
        if (self::isDev($version)) {
            return true;
        } else {
            return (boolean) preg_match('/' . self::$validPattern . '/i', $version);
        }
    }
    
    /**
     * Is this a development version?
     * 
     * @param string $version
     * @return boolean
     */
    public static function isDev($version)
    {
        return strpos($version, self::DEV_PREFIX) === 0;
    }
    
    /**
     * Compare two arrays
     *
     * @param $array1 array           
     * @param $array2 array           
     * @return int
     */
    private function cmp ($array1, $array2)
    {
        foreach ($array1 as $key => $number) {
            if (isset($array2[$key])) {
                if ($array2[$key] > $number) {
                    return + 1;
                } elseif ($array2[$key] < $number) {
                    return - 1;
                }
            } elseif ($number > 0) {
            	return - 1;
            }
        }
        return 0;
    }
}