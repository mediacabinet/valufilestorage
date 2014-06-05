<?php
namespace ValuFileStorage;

use ValuFileStorage\Odm\DocumentManagerFactory;
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
    
    /**
     * {@inheritDoc}
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'ValuFileStorageDm' => new DocumentManagerFactory('odm_default'),
            )
        );
    }
}