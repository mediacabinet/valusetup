<?php
namespace ValuSetup\Service;

use ValuSo\Feature;
use ValuSo\Annotation as ValuService;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Authentication\Adapter\Http as HttpAdapter;
use Zend\Authentication\Adapter\Http\FileResolver;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Provides HTTP digest based authentication mechanism
 * 
 * IMPORTANT: This service should be enabled only when the system is being
 * installed. Easiest way to disable this service is to remove
 * the identity file.
 * 
 * @ValuService\Context({"native", "cli"})
 */
class AuthService
    implements ServiceLocatorAwareInterface,
               Feature\ConfigurableInterface,
               Feature\ServiceBrokerAwareInterface
{
    use Feature\OptionsTrait;
    use Feature\ServiceBrokerTrait;
    
    const STORAGE_LOCATOR_KEY = 'ValuAuthStorage';
    
    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    private $serviceLocator;
    
    /**
     * Authentication service
     *
     * @var Zend\Authentication\AuthenticationService
     */
    private $authenticationService;
    
    /**
     * Authentication storage instance
     * 
     * @var Zend\Authentication\Storage\StorageInterface
     */
    private $storage;
    
    /**
     * Private copy of user's identity
     * 
     * @var array
     */
    private $identity = null;
    
	public function authenticate(MvcEvent $event)
	{
	    $request = $event->getRequest();
	    $response = $event->getResponse();
	    
	    if (!($request instanceof HttpRequest)) {
	        return null;
	    }
	    
	    if(!$this->setupAuthEnabled()){
	        return null;
	    }
	    
	    $file = $this->getOption('identity_file');
        $resolver = new FileResolver($file);
        $config = array(
            'accept_schemes' => 'digest',
            'realm'          => $this->getOption('realm'),
            'digest_domains' => '/',
            'nonce_timeout'  => $this->getOption('nonce_timeout'),
        );

        $adapter = new HttpAdapter($config);
        $adapter->setRequest($request);
        $adapter->setResponse($response);
        $adapter->setDigestResolver($resolver);
	    
	    return $this->getAuthenticationService()->authenticate($adapter);
	}
	
    /**
     * Returns the identity or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity()
    {
        if($this->hasIdentity()){
            if($this->identity === null){
                
                $identity = $this->authenticationService->getIdentity();
                $identity = array_merge(
                    $identity,
                    $this->getOption('identity')        
                );
                
                // Extend with real identity information
                if ($this->getServiceBroker()->exists('User')) {
                    $userService = $this->getServiceBroker()->service('User');
                    
                    // Access filter needs to be disabled to prevent infinite looping
                    $userService->disableFilter('access');
                    
                    $extension = $userService->find(
                        '$'.$identity['username'],
                        array('id', 'email')
                    );
                    
                    if ($extension) {
                        $identity = array_merge($identity, $extension);
                    }
                }
                
                $this->identity = $identity;
            }
            
            return $this->identity;
        }
        else{
            return null;
        }
    }
    
    /**
     * Returns true if and only if an identity is available
     *
     * @return boolean
     */
    public function hasIdentity()
    {
        return $this->setupAuthEnabled() && $this->getAuthenticationService()->hasIdentity();
    }

    /**
     * Clears the identity
     *
     * @return void
     */
    public function clearIdentity()
    {
        $this->getAuthenticationService()->clearIdentity();
    }
    
    /**
	 * Set storage instance
	 * 
	 * @param StorageInterface $storage
	 * 
	 * @ValuService\Exclude
	 */
	public function setStorage(StorageInterface $storage)
	{
	    $this->storage = $storage;
	}
	
	/**
	 * Retrieve storage instance
	 * 
	 * @return \Zend\Authentication\Storage\StorageInterface
	 * 
	 * @ValuService\Exclude
	 */
	public function getStorage()
	{
	    if(!$this->storage && $this->getServiceLocator()->has(self::STORAGE_LOCATOR_KEY)){
	        $this->setStorage($this->getServiceLocator()->get(self::STORAGE_LOCATOR_KEY));
	    }
	    
	    return $this->storage;
	}
    
    /**
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::getServiceLocator()
     * 
     * @ValuService\Exclude
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    /**
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::setServiceLocator()
     * 
     * @ValuService\Exclude
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
    
    /**
     * Retrieve AuthenticationService instance
     * 
     * @return \Zend\Authentication\AuthenticationService
     */
    protected function getAuthenticationService()
    {
        if ($this->authenticationService === null) {
            $this->authenticationService = new AuthenticationService($this->getStorage());
        }
        
        return $this->authenticationService;
    }
    
    /**
     * Is setup authentication enabled?
     * 
     * @return boolean
     */
    private function setupAuthEnabled()
    {
        $file = $this->getOption('identity_file');
         
        if(!$file || !is_readable($file)){
            return false;
        } else {
            return true;
        }
    }
}