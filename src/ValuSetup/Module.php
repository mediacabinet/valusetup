<?php
namespace ValuSetup;

use Zend\ModuleManager\Feature;
use Zend\Loader\AutoloaderFactory;
use Zend\Loader\StandardAutoloader;

class Module
    implements Feature\AutoloaderProviderInterface,
               Feature\ConfigProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            AutoloaderFactory::STANDARD_AUTOLOADER => array(
                StandardAutoloader::LOAD_NS => array(
                    'Valu' => __DIR__,
                ),
            ),
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
}