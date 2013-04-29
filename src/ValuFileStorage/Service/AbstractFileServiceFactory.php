<?php
namespace ValuFileStorage\Service;

use ValuSo\Exception\PermissionDeniedException;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractFileServiceFactory
{
    protected $globConfigs = array(
        'whitelist',
        'blacklist',
    );
    
    /**
     * Read and apply service configurations
     * 
     * @param \ValuFileStorage\Service\AbstractFileService $service
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function configureService($service, ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        
        foreach ($this->globConfigs as $key) {
            if (isset($config['file_storage'][$key])) {
                $service->setOption($key, $config['file_storage'][$key]);
            }
        }
    }
}