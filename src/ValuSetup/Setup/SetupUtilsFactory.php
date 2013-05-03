<?php
namespace ValuSetup\Setup;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SetupUtilsFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $broker = $serviceLocator->get('ServiceBroker');
        $config = $serviceLocator->get('Configuration');
        $config = !empty($config['setup_utils']) ? $config['setup_utils'] : array();
        
        $utils = new SetupUtils(
            $broker,
            $config
        );
        
        return $utils;
    }
}