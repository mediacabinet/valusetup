<?php
namespace ValuSetup\Service;

use ValuAcl\Service\Annotation as ValuServiceAcl;
use ValuSetup\Setup\SoftwareVersion;
use ValuSetup\Setup\SetupUtils;
use ValuSo\Feature;
use ValuSo\Exception\MissingParameterException;
use ValuSo\Broker\ServiceBroker;
use ValuSo\Annotation as ValuService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Abstract setup service class
 * 
 * @author Juha Suni
 * 
 */
abstract class AbstractSetupService 
    implements ServiceLocatorAwareInterface,
               Feature\ServiceBrokerAwareInterface,
               Feature\ConfigurableInterface
{
    use Feature\ServiceBrokerTrait;
    use Feature\IdentityTrait;
    use Feature\OptionsTrait;
    
    /**
     * Module name
     */
    protected $name;
    
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
     * Retrieve setup priority
     * 
     * @return number
     */
    public function getPriority()
    {
        return 1;
    }
    
    /**
     * Retrieve module name for setup service
     * 
     * @return string
     * 
     * @ValuService\Context({"cli", "http", "http-get"})
     * @ValuServiceAcl\Superuser
     */
    public function getName()
    {
        if (!$this->name) {
            $this->name = $this->utils()->whichModule($this);
        }
        
        return $this->name;
    }
    
    /**
     * Retrieve version for module
     * 
     * @return string
     * 
     * @ValuService\Context({"cli", "http", "http-get"})
     * @ValuServiceAcl\Superuser
     */
    public function getVersion(){
        return $this->utils()->getModuleVersion($this->getName());
    }
    
    /**
     * Install module dependencies and execute setup when
     * ready 
     * 
     * @param array $options Setup options
     * @return boolean True on success
     * 
     * @ValuService\Context({"cli", "http", "http-put"})
     * @ValuServiceAcl\Superuser
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
     * @return boolean True on success
     * 
     * @ValuService\Context({"cli", "http", "http-post"})
     * @ValuServiceAcl\Superuser
     */
    public function setup(array $options = array())
    {
        return true;
    }
    
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
     * @return boolean True on success
     * 
     * @ValuService\Context({"cli", "http", "http-post"})
     * @ValuServiceAcl\Superuser
     */
	public function upgrade($from, array $options = array()){
	    
	    $to = $this->utils()->getModuleVersion($this->getName());
	    
	    if(SoftwareVersion::compare($to, $from) <= 0){
	        throw new Exception\IllegalVersionException(
                sprintf('Unable to upgrade %s to version %s', $this->getName().' '.$to, $from)
            );
	    }
	    
	    return true;
	}
    
	/**
	 * Uninstall module
	 * 
	 * This method should not uninstall dependent modules, nor
	 * the module settings by default.
	 * 
	 * @return boolean True on success
	 * 
	 * @ValuService\Context({"cli", "http", "http-delete"})
	 * @ValuServiceAcl\Superuser
	 */
    public function uninstall(array $options = array())
    {
        return true;
    }
    
    /**
     * Clear all cache records for module
     * 
     * @return boolean True if cache records existed and were cleared
     * 
     * @ValuService\Context({"cli", "http", "http-delete"})
	 * @ValuServiceAcl\Superuser
     */
    public function clearCache()
    {
        return false;
    }
    
    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     * 
     * @ValuService\Exclude
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     * 
     * @ValuService\Exclude
    */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    /**
     * Load service instace by service ID
     *
     * @return mixed
     *
     * @ValuService\Exclude
     */
    protected function loadServiceById($serviceId)
    {
        return $this->getServiceBroker()->getLoader()->load($serviceId);
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