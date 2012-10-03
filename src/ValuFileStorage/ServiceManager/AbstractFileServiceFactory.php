<?php
namespace ValuFileStorage\ServiceManager;

use Valu\Service\Exception\PermissionDeniedException;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractFileServiceFactory
{

    protected $aclInitialized = false;
    
    protected $globConfigs = array(
        'whitelist',
        'blacklist',
    );
    
    protected function configureService($service, ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        
        foreach ($this->globConfigs as $key) {
            if (isset($config['file_storage'][$key])) {
                $service->setOption($key, $config['file_storage'][$key]);
            }
        }
        
        $this->initAcl($serviceLocator);
    }
    
    protected function initAcl(ServiceLocatorInterface $serviceLocator)
    {
        if ($this->aclInitialized) {
            return;
        }
        
        $this->aclInitialized = true;
        
        $serviceBroker = $serviceLocator->get('ServiceBroker');
        
        /**
         * Attach ACL event listeners
        */
        $serviceBroker->getEventManager()->attach(
            array(
                'init.filestorage.insert',
                'init.filestorage.write',
                'init.filestorage.delete'
            ),
            function($e) use ($serviceBroker){
                if (!$serviceBroker->service('FileStorage.Acl')->isAllowed('current-user', 'file', 'write')) {
                    throw new PermissionDeniedException(
                            'User is not allowed to write files');
                }
            }
        );
        
        $serviceBroker->getEventManager()->attach(
            array(
                'init.filestorage.deleteall',
            ),
            function($e) use ($serviceBroker){
                if (!$serviceBroker->service('FileStorage.Acl')->isAllowed('current-user', 'file', 'batch-delete')) {
                    throw new PermissionDeniedException(
                        'User is not allowed to perform batch-delete');
                }
            }
        );
        
        $serviceBroker->getEventManager()->attach(
            array(
                'init.filestorage.getmetadata',
                'init.filestorage.read',
            ),
            function($e) use ($serviceBroker){
                if (!$serviceBroker->service('FileStorage.Acl')->isAllowed('current-user', 'file', 'read')) {
                    throw new PermissionDeniedException(
                        'User is not allowed to read files');
                }
            }
        );
    }
}