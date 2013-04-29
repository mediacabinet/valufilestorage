<?php
namespace ValuFileStorage;

use ValuFileStorage\Odm\DocumentManagerFactory;
use Zend\ModuleManager\Feature;
use Zend\EventManager\Event;
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
                    __NAMESPACE__ => __DIR__
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