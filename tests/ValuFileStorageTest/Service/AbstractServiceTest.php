<?php
namespace ValuFileStorageTest\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Application;

abstract class AbstractServiceTest extends TestCase
{
    protected static $serviceId;

    protected static $urlScheme;

    protected static $defaultTarget;

    protected static $urlPattern;

    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $dm;

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $sm;

    /**
     * @var \ValuSo\Broker\ServiceBroker
     */
    protected $serviceBroker;

    /**
     * @var Application
     */
    protected static $application;

    public static function setUpBeforeClass()
    {
        self::$application = Application::init([
            'modules' => [
                'DoctrineModule',
                'DoctrineMongoODMModule',
                'ValuCore',
                'ValuSo',
                'ValuFileStorage',
            ],
            'module_listener_options' => [
                'config_static_paths' => [__DIR__ . '/../../../config/tests.config.php'],
                'config_cache_enabled' => false,
                'module_paths' => [
                    'vendor/valu',
                    'vendor/doctrine',
                ]
            ]
        ]);

        self::rmDirRecursive(__DIR__ . '/../../data/tmp');

        foreach (['data/DoctrineMongoODMModule/Hydrator', 'data/DoctrineMongoODMModule/Proxy', __DIR__ . '/../../data/tmp'] as $dir) {
        	if (!file_exists($dir)) {
        		mkdir($dir, 0755, true);
        	}
        }
    }

    public static function rmDirRecursive($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            if(is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                self::rmDirRecursive($dir . DIRECTORY_SEPARATOR . $file);
            } else {
                unlink($dir . DIRECTORY_SEPARATOR . $file);
            }
        } 

        return rmdir($dir);
    }

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->triggered = new \ArrayObject();
        $sm = self::$application->getServiceManager();
        $this->serviceBroker = $sm->get('ServiceBroker');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->triggered = null;
        $this->serviceBroker = null;

        parent::tearDown();
    }

    public function testInsert()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');

        $meta = $this->service()->insert($url, static::$defaultTarget);

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
            $this->service()->totalSize(static::$urlScheme.':///')
        );

        $this->assertRegExp(
            static::$urlPattern,
            $meta['url']
        );
    }

    public function testInsertPlainTextDataUrl()
    {
        $text = "Test file";
        $url = 'data:,' . urlencode($text);
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertEquals(
            'text/plain',
            $meta['mimeType']
        );

        $this->assertEquals(
            strlen($text),
            $meta['filesize']
        );

        $this->assertRegExp(
            '/.*\/data.text_plain/',
            $meta['url']
        );
    }

    public function testInsertBase64EncodedImageDataUrl()
    {
        $file = $this->filePath('images/lake.jpg');

        $url = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($file));
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertEquals(
            'image/jpeg',
            $meta['mimeType']
        );

        $this->assertEquals(
            filesize($file),
            $meta['filesize']
        );

        $this->assertRegExp(
            '/.*\/data.image_jpeg/',
            $meta['url']
        );
    }

    public function testExists()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertTrue($this->service()->exists($meta['url']));
    }

    public function testDoesNotExist()
    {
         $this->assertFalse($this->service()->exists(static::$defaultTarget . '/file-does-not-exist'));
    }

    /**
     * @expectedException \ValuFileStorage\Service\Exception\RestrictedUrlException
     */
    public function testInsertBlacklisted(){
        $this->serviceBroker->getLoader()->load(static::$serviceId)->setOption(
            'blacklist',
            array('\.(exe|sh|dmg)$')
        );

        $url = $this->fileUrl('exec/application.exe');

        $this->service()->insert($url, static::$defaultTarget);
    }

    public function testReadBinary()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');

        $meta = $this->service()->insert($url, static::$defaultTarget);
        $data = $this->service()->read($meta['url']);

        $this->assertEquals(
            file_get_contents($file),
            $data
        );
    }

    public function testReadText()
    {
        $url  = $this->fileUrl('documents/text-only.txt');
        $file = $this->filePath('documents/text-only.txt');

        $meta = $this->service()->insert($url, static::$defaultTarget);
        $data = $this->service()->read($meta['url']);

        $this->assertEquals(
            file_get_contents($file),
            $data
        );
    }

    public function testWriteText()
    {
        $data = 'CHANGED';
        $meta = $this->service()->insert($this->fileUrl('documents/text-only.txt'), static::$defaultTarget);
        $this->service()->write($meta['url'], $data);

        $this->assertEquals(
            $data,
            $this->service()->read($meta['url'])
        );

        $meta = $this->service()->getMetadata($meta['url']);

        $this->assertEquals(
            strlen($data),
            $meta['filesize']
        );
    }

    public function testGetMetadata()
    {
        $url  = $this->fileUrl('documents/text-only.txt');
        $file = $this->filePath('documents/text-only.txt');

        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertEquals(
            $meta,
            $this->service()->getMetadata($meta['url'])
        );
    }

    public function testGetMetadataInfo()
    {
        $url  = $this->fileUrl('documents/pdf/document.pdf');
        $file = $this->filePath('documents/pdf/document.pdf');

        $file = $this->service()->insert($url, static::$defaultTarget);
        $meta = $this->service()->getMetadata($file['url']);

        $this->assertEquals(
            $file['url'],
            $meta['url']
        );

        $this->assertEquals(
            'application/pdf',
            $meta['mimeType']
        );

        $now = new \DateTime();

        $date = new \DateTime($meta['modifiedAt']);
        $this->assertTrue(($now->getTimestamp()-$date->getTimestamp()) < 2);

        $date = new \DateTime($meta['createdAt']);
        $this->assertTrue(($now->getTimestamp()-$date->getTimestamp()) < 2);
    }

    public function testReplace()
    {
        $url1  = $this->fileUrl('images/lake.jpg');
        $url2  = $this->fileUrl('images/icon.png');
        $file1 = $this->filePath('images/lake.jpg');
        $file2 = $this->filePath('images/icon.png');

        $meta1 = $this->service()->insert($url1, static::$defaultTarget);
        $meta2 = $this->service()->insert($url2, $meta1['url']);

        $this->assertEquals(
            $meta1['url'],
            $meta2['url']
        );

        $this->assertEquals(
            file_get_contents($file2),
            $this->service()->read($meta1['url'])
        );
    }

    public function testDelete()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertTrue(isset($meta['url']));

        $result = $this->service()->delete($meta['url']);

        $this->assertTrue($result);
    }

    public function testDeleteAll()
    {
        $this->service()->deleteAll(static::$urlScheme . '://');

        $this->assertEquals(
            0,
            $this->service()->totalSize(static::$urlScheme . ':///')
        );
    }

    public function testGetLocalCopy()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $meta = $this->service()->insert($url, static::$defaultTarget);
        $copy = $this->service()->getLocalCopy($meta['url']);

        $this->assertFileExists($copy);

        try {
            $path = $this->service()->getPath($meta['url']);
            $this->assertNotEquals($path, $copy);
        } catch(\ValuFileStorage\Service\Exception\UnsupportedOperationException $e) {
            // ignore if getPath not supported by current implementation
        }

        unlink($copy);
    }

    public function testGetResource()
    {
        $url  = $this->fileUrl('documents/text-only.txt');
        $meta = $this->service()->insert($url, static::$defaultTarget);
        $resource = $this->service()->getResource($meta['url']);

        $sizeToRead = filesize($url);
        $content = fread($resource, $sizeToRead);
        $this->assertEquals(file_get_contents($url), $content);
        fclose($resource);
    }

    /**
     * Retrieve access to service
     *
     * @return \ValuSo\Service\AbstractFileService
     */
    protected abstract function service();

    protected function fileUrl($file)
    {
        return 'file://' . realpath(dirname(__FILE__) . '/../../resources') . '/' . $file;
    }

    protected function filePath($file)
    {
        return realpath(dirname(__FILE__) . '/../../resources') . '/' . $file;
    }
}
