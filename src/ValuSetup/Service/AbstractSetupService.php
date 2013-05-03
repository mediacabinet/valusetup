<?php
namespace ValuSetup\Service;

use ValuSetup\Setup\SoftwareVersion;
use ValuSetup\Setup\SetupUtils;
use ValuSo\Feature;
use ValuSo\Exception\MissingParameterException;
use ValuSo\Broker\ServiceBroker;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class AbstractSetupService 
    implements ServiceLocatorAwareInterface,
               Feature\ServiceBrokerAwareInterface
{
    use Feature\ServiceBrokerTrait;
    
    /**
     * Setup utils
     * 
     * @var \ValuSetup\Setup\SetupUtils
     */
    protected $utils;
    
    /**
     * Service locator
     * 
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;
    
    /**
     * Retrieve module name for setup service
     * 
     * @return string
     */
    public abstract function getName();
    
    /**
     * Retrieve version for module
     * 
     * @return string
     */
    public function getVersion(){
        return $this->utils()->getModuleVersion($this->getName());
    }
    
    /**
     * Install module dependencies and execute setup when
     * ready 
     * 
     * @param array $options Setup options
     */
    public function install($version = null, array $options = array())
    {
        $module = $this->utils()->whichModule($this);
        if ($version === null) {
            $version = $this->utils()->getModuleVersion($module);
            
            if (!$version) {
                throw new MissingParameterException(
                    'Parameter version is missing and cannot be autodetected');
            }
        }
        
        return $this->utils()->install($module, $version);
    }
    
    /**
     * Setup module
     * 
     * @param array $options
     */
    public abstract function setup(array $options = array());
    
    /**
     * Upgrade module from previous version
     * 
     * This method should be invoked only after the new
     * version has been loaded. This method should also ignore
     * any values of $from that indicate version number that
     * is greater than or equal to current version.
     * 
     * This method should ensure backwards compatibility and
     * prepare data from previous version for the current
     * version.
     * 
     * @param string $from Version information
     * @param array $options
     */
	public function upgrade($from, array $options = array()){
	    
	    $to = $this->utils()->getModuleVersion($this->getName());
	    
	    if(SoftwareVersion::compare($to, $from) <= 0){
	        throw new Exception\IllegalVersionException(
                sprintf('Unable to upgrade %s to version %s', $this->getName().' '.$to, $from)
            );
	    }
	}
    
	/**
	 * Uninstall module
	 * 
	 * This method should not uninstall dependent modules, nor
	 * the module settings by default.
	 */
    public abstract function uninstall(array $options = array());
    
    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
    */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    /**
     * Direct access to service utilities
     * 
     * @return \ValuSetup\Setup\SetupUtils
     */
    protected function utils()
    {
        if(!$this->utils){
            $this->utils = $this->getServiceLocator()->get('valu_setup.setup_utils');
        }
        
        return $this->utils;
    }
    
    /**
     * Trigger callback
     * 
     * @param string $callback Operation name
     * @param array|null $callbackArgs Arguments
     * @throws \Exception
     */
    protected function triggerCallback($callback, $callbackArgs = null){
         
        if(!$this->utils()->hasSetupService($this->getName())){
            throw new \Exception(sprintf('Callback service %s not available', $this->getName()));
        }
         
        $this->utils()->initSetupService($this->getName())->fork(
            $callback,
            $callbackArgs
        );
    }
}