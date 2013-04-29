<?php
namespace ValuFileStorage\Odm;

use DoctrineMongoODMModule\Service\DocumentManagerFactory as BaseFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

class DocumentManagerFactory 
    extends BaseFactory
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /**
         * @var array
         */
        $config = $serviceLocator->get('Configuration');
        
        /**
         * @var \Doctrine\ODM\MongoDB\DocumentManager
         */
        $dm = parent::createService($serviceLocator);
        return $dm;
    }    
}