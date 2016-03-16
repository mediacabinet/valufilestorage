<?php
namespace ValuFileStorageTest\Service;

use ValuFileStorageTest\Mock\MockUuidGenerator;

class LocalFileServiceTest extends AbstractServiceTest
{
    protected static $serviceId = 'ValuFileStorageLocalFile';

    protected static $urlScheme = 'file';

    protected static $defaultTarget = 'file:///$tmp';

    protected static $urlPattern = '#^file:///\$[a-zA-Z0-9\-]+/([a-zA-Z0-9\-]+/)*[a-zA-Z0-9\-]+/[^/]+$#';

    public function tearDown()
    {
        $service = $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile');
        $service->setUuidGenerator(null);

        $this->clearTestFiles();
        parent::tearDown();
    }

    public function testInsertDoesNotWriteToSameFolderWithExistingFile()
    {
        $uuidGenerator = new MockUuidGenerator([
            '3f8bac09fec639d0bad06f3748d4676a',
            '3f8bac09fec639d0bad06f3748d4676a',
            '8ab152c200ba3108b36897ae59e29deb']);

        $service = $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile');
        $service->setUuidGenerator($uuidGenerator);

        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');

        $meta1 = $this->service()->insert($url, static::$defaultTarget);
        $meta2 = $this->service()->insert($url, static::$defaultTarget);

        $path1 = $this->service()->getPath($meta1['url']);
        $path2 = $this->service()->getPath($meta2['url']);

        $this->assertFileExists($path1);
        $this->assertFileExists($path2);
        $this->assertNotEquals($path1, $path2);
    }

    public function testInsertWithOneIntermediatePath()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');

        $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->setOption('hashedDirLevels', 1);
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertRegExp(
            '#^file:///\$[a-zA-Z0-9\-]+/([a-zA-Z0-9\-]{2}/){1}[a-zA-Z0-9\-]+/[^/]+$#',
            $meta['url']
        );
    }

    public function testInsertWithTwoHashedDirLevels()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');

        $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->setOption('hashedDirLevels', 2);
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertRegExp(
            '#^file:///\$[a-zA-Z0-9\-]+/([a-zA-Z0-9\-]{2}/){2}[a-zA-Z0-9\-]+/[^/]+$#',
            $meta['url']
        );
    }

    public function testGetMetadataForFilesStoredWithMixedHashedDirLevels()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $file = $this->filePath('images/lake.jpg');

        $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->setOption('hashedDirLevels', 0);
        $meta1 = $this->service()->insert($url, static::$defaultTarget);

        $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->setOption('hashedDirLevels', 1);
        $meta2 = $this->service()->insert($url, static::$defaultTarget);

        $this->assertEquals(
            $meta1,
            $this->service()->getMetadata($meta1['url'])
        );

        $this->assertEquals(
            $meta2,
            $this->service()->getMetadata($meta2['url'])
        );
    }

    public function testReadFilesStoredWithMixedHashedDirLevels()
    {
        $url  = $this->fileUrl('documents/text-only.txt');
        $file = $this->filePath('documents/text-only.txt');

        $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->setOption('hashedDirLevels', 0);
        $meta1 = $this->service()->insert($url, static::$defaultTarget);

        $this->serviceBroker->getLoader()->load('ValuFileStorageLocalFile')->setOption('hashedDirLevels', 1);
        $meta2 = $this->service()->insert($url, static::$defaultTarget);

        $contents = file_get_contents($file);
        $this->assertEquals(
            $contents,
            $this->service()->read($meta1['url'])
        );

        $this->assertEquals(
            $contents,
            $this->service()->read($meta2['url'])
        );
    }

    public function testDeleteDoesNotRemoveOtherFilesInHashFolder()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $this->serviceBroker
            ->getLoader()
            ->load('ValuFileStorageLocalFile')
            ->setOption('hashedDirLevels', 1);

        $meta = $this->service()->insert($url, static::$defaultTarget);

        $file = $this->service()->getPath($meta['url']);
        $parentHashDir = dirname(dirname($file));
        $testFolder = $parentHashDir . DIRECTORY_SEPARATOR . 'foo';
        $testFile = $testFolder . DIRECTORY_SEPARATOR . 'test.txt';

        mkdir($testFolder, 0777);
        touch($testFile);

        $this->service()->delete($meta['url']);

        $this->assertFileNotExists($file);
        $this->assertFileExists($testFile);
        $this->assertFileExists($testFolder);
    }

    public function testDeleteNonExisting()
    {
        $this->assertFalse(
            $this->service()->delete(static::$urlScheme . ':///$tmp/does-not-exist')
        );
    }

    public function testGetPath()
    {
        $url  = $this->fileUrl('images/lake.jpg');
        $meta = $this->service()->insert($url, static::$defaultTarget);

        $this->assertFileExists($this->service()->getPath($meta['url']));
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
