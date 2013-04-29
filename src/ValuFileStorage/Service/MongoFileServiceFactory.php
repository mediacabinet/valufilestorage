<?php
namespace ValuFileStorage\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MongoFileServiceFactory extends AbstractFileServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $dm     = $serviceLocator->get('ValuFileStorageDm');
        
        $service = new MongoFileService($dm);
        $this->configureService($service, $serviceLocator);
        
        return $service;
    }
}