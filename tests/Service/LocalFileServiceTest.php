<?php
namespace ValuFileStorage\Test\Service;

class LocalFileServiceTest extends AbstractServiceTest
{
    protected static $paths = array();
    
	protected static $serviceName = 'ValuFileStorageLocalFile';
    
    protected static $urlScheme = 'file';
    
    protected static $defaultTarget = 'file:///$tmp';
    
    protected static $urlPattern = '#^file:///\$[a-zA-Z0-9\-]+/[a-zA-Z0-9\-]+/[^/]+$#';
    
    protected static $textFileUrl;
    
    public static function setUpBeforeClass()
    {
        self::$paths = array(
            'tmp' => dirname(__DIR__) . '/tmp'        
        );
        
        parent::setUpBeforeClass();
        
        self::service()->setOption('paths', self::$paths);
        self::clearTestFiles();
    }
    
    public static function tearDownAfterClass()
    {
        self::clearTestFiles();
    }
    
    public function testDeleteNonExisting()
    {
        $this->assertFalse(
            self::service()->delete(static::$urlScheme . ':///$tmp/does-not-exist')
        );
    }
    
    private static function clearTestFiles()
    {
        foreach(self::$paths as $dir){
            
            $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir),
                    \RecursiveIteratorIterator::CHILD_FIRST);
            
            foreach($iterator as $fileInfo){
                if ($fileInfo->isFile() && is_writable($fileInfo->getPathname())) {
                    unlink($fileInfo->getPathname());
                } elseif ($fileInfo->isDir() && is_writable($fileInfo->getPathname())) {
                    rmdir($fileInfo->getPathname());
                }
            }
        }
    }
}