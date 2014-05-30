<?php
namespace ValuSetup;

use Zend\ModuleManager\Feature;

class Module
    implements Feature\ConfigProviderInterface
{   
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
}