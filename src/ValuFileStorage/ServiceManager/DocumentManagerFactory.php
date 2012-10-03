<?php
namespace ValuFileStorage\ServiceManager;

use ValuFileStorage\Odm,
    Zend\ServiceManager\ServiceLocatorInterface;

class DocumentManagerFactory 
    extends \Valu\Doctrine\ServiceManager\DocumentManagerFactory
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