<?php
namespace FoafFileStorage\ServiceManager;

use FoafFileStorage\Odm,
    Zend\ServiceManager\ServiceLocatorInterface;

class DocumentManagerFactory 
    extends \Foaf\Doctrine\ServiceManager\DocumentManagerFactory
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        
        /**
         * Configurations
         * 
         * @var array
         */
        $config = $serviceLocator->get('Configuration');
        
        /**
         * DocumentManager
         * 
         * @var \Doctrine\ODM\MongoDB\DocumentManager
         */
        $dm = parent::createService($serviceLocator);
        $evm = $dm->getEventManager();
        
        return $dm;
    }    
}