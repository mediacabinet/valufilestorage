<?php
/**
 * Valu FileSystem Module
 *
 * @copyright Copyright (c) 2012-2013 Media Cabinet (www.mediacabinet.fi)
 * @license   BSD 2 License
 */
namespace ValuFileStorage\Service;

use ValuSetup\Service\AbstractSetupService;
use ValuSo\Annotation as ValuService;

class SetupService extends AbstractSetupService
{
    public function setup(array $options = array())
    {
        $this->reloadMeta();
        $this->ensureIndexes();
        return true;
    }
    
    /**
     * Reload proxy and hydrator classes
     *
     * @return true
     */
    public function reloadMeta()
    {
        $dm = $this->getServiceLocator()->get('ValuFileStorageDm');
        $metadatas = $dm->getMetadataFactory()->getAllMetadata();
        $dm->getProxyFactory()->generateProxyClasses($metadatas, $dm->getConfiguration()->getProxyDir());
        $dm->getHydratorFactory()->generateHydratorClasses($metadatas, $dm->getConfiguration()->getHydratorDir());
    
        return true;
    }
    
    /**
     * Ensure that database indexes exist
     *
     * @return boolean
     */
    public function ensureIndexes()
    {
        $sm = $this->getServiceLocator()->get('ValuFileStorageDm')->getSchemaManager();
        $sm->ensureIndexes();
    
        return true;
    }
}