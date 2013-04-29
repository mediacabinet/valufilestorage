<?php
namespace ValuFileStorageTest\Service;

class LocalFileServiceTest extends AbstractServiceTest
{
    protected static $serviceId = 'ValuFileStorageLocalFile';
    
    protected static $urlScheme = 'file';
    
    protected static $defaultTarget = 'file:///$tmp';
    
    protected static $urlPattern = '#^file:///\$[a-zA-Z0-9\-]+/[a-zA-Z0-9\-]+/[^/]+$#';
    
    public function tearDown()
    {
        $this->clearTestFiles();
        parent::tearDown();
    }
    
    public function testDeleteNonExisting()
    {
        $this->assertFalse(
            $this->service()->delete(static::$urlScheme . ':///$tmp/does-not-exist')
        );
    }
    
    /**
     * Retrieve access to service
     * 
     * @return \ValuSo\Service\LocalFileService
     */
    protected function service()
    {
        return $this->serviceBroker->service('FileStorage.File');
    }
    
    private function clearTestFiles()
    {
        $paths = $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->getOption('paths');
        
        foreach($paths as $dir){
            
            $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir),
                    \RecursiveIteratorIterator::CHILD_FIRST);
            
            foreach($iterator as $fileInfo){
                if ($fileInfo->getFileName() === '.' || $fileInfo->getFileName() === '..') {
                    continue;
                }
                
                if ($fileInfo->isFile() && is_writable($fileInfo->getPathname())) {
                    unlink($fileInfo->getPathname());
                } elseif ($fileInfo->isDir() && is_writable($fileInfo->getPathname())) {
                    rmdir($fileInfo->getPathname());
                }
            }
        }
    }
}