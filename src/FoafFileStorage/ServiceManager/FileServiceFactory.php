<?php
namespace FoafFileStorage\ServiceManager;

use FoafFileStorage\Service\File;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $broker = $serviceLocator->get('ServiceBroker');
        $dm = $serviceLocator->get('FoafFileStorageDm');
        
        $service = new File($broker, $dm);
        return $service;
    }
}