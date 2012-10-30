<?php
namespace ValuFileStorage\Test\Service;

use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractServiceTest extends TestCase
{
    
    protected static $serviceName = '';
    
    protected static $urlScheme = '';
    
    protected static $defaultTarget = '';
    
    protected static $urlPattern = '';
    
    protected static $textFileUrl;

    /**
     * @var \Valu\Service\Broker
     */
    protected static $broker;
    
    public static function setUpBeforeClass()
    {
        $config = include APPLICATION_TEST_CONFIG_FILE;
        $config['modules'][] = 'ValuFileStorage';
    
        $application = \Zend\Mvc\Application::init($config);
        self::$broker = $application->getServiceManager()->get('ServiceBroker');
    }
    
    public function testInsert()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');
    
        $meta = self::service()->insert($url, static::$defaultTarget);
    
        $this->assertInternalType(
            'array',
            $meta
        );
    
        $this->assertEquals(
            filesize($file),
            $meta['filesize']
        );
    
        $this->assertEquals(
            'image/jpeg',
            $meta['mimeType']
        );
    
        $this->assertEquals(
            filesize($file),
            self::service()->totalSize()
        );
    
        $this->assertRegExp(
            static::$urlPattern,
            $meta['url']
        );
    }
    
    public function testExists()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $meta = self::service()->insert($url, static::$defaultTarget);
        
        $this->assertTrue(self::service()->exists($meta['url']));
    }
    
    public function testDoesNotExist()
    {
         $this->assertFalse(self::service()->exists(static::$defaultTarget . '/file-does-not-exist'));
    }
    
    /**
     * @expectedException \ValuFileStorage\Service\Exception\RestrictedUrlException
     */
    public function testInsertBlacklisted(){
    
        self::service()->setOption(
            'blacklist',
            array('\.(exe|sh|dmg)$')
        );
    
        $url = $this->fileUrl('exec/application.exe');
    
        self::service()->insert($url, static::$defaultTarget);
    }
    
    public function testReadBinary()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');
    
        $meta = self::service()->insert($url, static::$defaultTarget);
        $data = self::service()->read($meta['url']);
    
        $this->assertEquals(
            file_get_contents($file),
            $data
        );
    }
    
    public function testReadText()
    {
        $url  = $this->fileUrl('documents/text-only.txt');
        $file = $this->filePath('documents/text-only.txt');
    
        $meta = self::service()->insert($url, static::$defaultTarget);
        $data = self::service()->read($meta['url']);
    
        $this->assertEquals(
            file_get_contents($file),
            $data
        );
    
        self::$textFileUrl = $meta['url'];
    }
    
    /**
     * @depends testReadText
     */
    public function testWriteText()
    {
        $data = 'CHANGED';
        self::service()->write(self::$textFileUrl, $data);
    
        $this->assertEquals(
            $data,
            self::service()->read(self::$textFileUrl)
        );
    
        $meta = self::service()->getMetadata(self::$textFileUrl);
    
        $this->assertEquals(
            strlen($data),
            $meta['filesize']
        );
    }
    
    public function testGetMetadata()
    {
        $url  = $this->fileUrl('documents/text-only.txt');
        $file = $this->filePath('documents/text-only.txt');
        
        $meta = self::service()->insert($url, static::$defaultTarget);
        
        $this->assertEquals(
            $meta,
            self::service()->getMetadata($meta['url'])
        );
    }
    
    public function testGetMetadataInfo()
    {
        $url  = $this->fileUrl('documents/pdf/document.pdf');
        $file = $this->filePath('documents/pdf/document.pdf');
        
        $file = self::service()->insert($url, static::$defaultTarget);
        $meta = self::service()->getMetadata($file['url']);
        
        $now = new \DateTime();
        
        $this->assertEquals(
            $file['url'],
            $meta['url']     
        );
        
        $this->assertEquals(
            'application/pdf',
            $meta['mimeType']
        );
        
        $date = new \DateTime($meta['modifiedAt']);
        $interval = $now->diff($date);
        $this->assertTrue($interval->s < 2);
        
        $date = new \DateTime($meta['createdAt']);
        $interval = $now->diff($date);
        $this->assertTrue($interval->s < 2);
    }
    
    public function testReplace()
    {
        $url1  = $this->fileUrl('images/lake.jpg');
        $url2  = $this->fileUrl('images/icon.png');
        $file1 = $this->filePath('images/lake.jpg');
        $file2 = $this->filePath('images/icon.png');
    
        $meta1 = self::service()->insert($url1, static::$defaultTarget);
        $meta2 = self::service()->insert($url2, $meta1['url']);
        
        $this->assertEquals(
            $meta1['url'],
            $meta2['url']        
        );
        
        $this->assertEquals(
            file_get_contents($file2),
            self::service()->read($meta1['url'])
        );
    }
    
    public function testDelete()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $meta = self::service()->insert($url, static::$defaultTarget);
    
        $this->assertTrue(isset($meta['url']));
    
        $result = self::service()->delete($meta['url']);
    
        $this->assertTrue($result);
    }
    
    public function testDeleteAll()
    {
        self::service()->deleteAll(static::$urlScheme . '://');
    
        $this->assertEquals(
            0,
            self::service()->totalSize()
        );
    }
    
    /**
     * Retrieve access to service
     * 
     * @param string|null $service
     * @return \Valu\Service\ServiceInterface
     */
    protected static function service($service = null)
    {
        if($service === null){
            $service = static::$serviceName;
        }
        
        return self::$broker->getLoader()->load($service);
    }
    
    protected function fileUrl($file)
    {
        return 'file://' . realpath(dirname(__FILE__) . '/../resources') . '/' . $file;
    }
    
    protected function filePath($file)
    {
        return realpath(dirname(__FILE__) . '/../resources') . '/' . $file;
    }
}