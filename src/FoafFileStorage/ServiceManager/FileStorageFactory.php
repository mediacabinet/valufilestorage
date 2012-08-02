<?php
namespace FoafFileStorage\ServiceManager;

use FoafFileStorage\Service\FileStorage,
    Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

class FileStorageFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $broker = $serviceLocator->get('ServiceBroker');
        $dm = $serviceLocator->get('FoafFileStorageDm');
        
        $service = new FileStorage($broker, $dm);
        return $service;
    }
}