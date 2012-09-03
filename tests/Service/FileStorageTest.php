<?php
namespace FoafFileStorage\Test\Service;

use FoafFileStorage\Service\FileStorage,
	Doctrine\ODM\MongoDB\DocumentManager;

class FileStorageTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var FileStorage
	 */
	protected static $storage;
	
	/**
	 * @var DocumentManager
	 */
	protected static $dm;
	
	protected $rootPath = '/';
	
	/**
	 * Urls of saved files
	 * 
	 * @var array
	 */
	protected static $saved = array();
	
	public static function setUpBeforeClass()
	{
		$app 	= $GLOBALS['application'];
		
		$modules = $app->getBootstrap()->getResource('modules');
		$module = $modules['foaf-file-storage']->getResource('module');
		$dm = $module->getDocumentManager();
		
		$dm->createQueryBuilder('FoafFileStorage\\Model\\AbstractNode')
			->remove()
			->getQuery()
			->execute();
			
		$dm->flush();
			
		$loader = $app->getBootstrap()->getResource('ServiceLoader');
		$fs = $loader->getServiceInstance('FileStorage', 'FoafFileStorage\\Service\\FileSystem');
		
		$fs->createRootDir();
		
		$fs->createDir(
			'/temp',
			array('roles' => 'temp')
		);
		
		self::$storage = $loader->getServiceInstance('FileStorage', 'FoafFileStorage\\Service\\FileStorage');
	}

	public function testUpload(){
		
		$dir = realpath(dirname(__FILE__) . '/../resources');
		
		$files = array(
			$dir . '/images/lake.jpg',
			$dir . '/images/icon.png',
			$dir . '/documents/pdf/document.pdf',
			$dir . '/documents/pdf/memo.pdf',
			$dir . '/documents/memo.rtf'
		);
		
		foreach($files as $file){
			
			$url	= 'file://'.$file;
			$info	= self::$storage->upload($url);
			
			$this->assertNotNull(
				$info,
				'Failed creating file '.$file
			);
			
			if($info){
				self::$saved[] = $info['url'];
			}
		}
	}
	
	/**
	 * @depends testUpload
	 */
	public function testDelete(){
		if(sizeof(self::$saved)){
			foreach (self::$saved as $url){
				$success = self::$storage->delete($url);
				
				$this->assertTrue($success);
			}
		}
		else{
			$this->markTestSkipped('Nothing to remove');
		}
	}
	
	public function testSaveRestrictedFile(){
		
		/**
		 * Set restriction options
		 */
		self::$storage->setOption(
			'blacklist',
			array(
				'hidden' 	=> '/\.[^/]+',
				'exec'		=> '\.(exe|sh|dmg)$'
			)
		);
		
		$dir = realpath(dirname(__FILE__) . '/../resources');
		
		$files = array(
			$dir . '/.htaccess',
			$dir . '/exec/application.exe',
			$dir . '/exec/application.tmp.sh'
		);
		
		foreach($files as $file){
			
			$url = 'file://'.$file;
			
			try{
				self::$storage->upload($url);
			}
			catch (\FoafFileStorage\Service\Exception\RestrictedUrlException $e){
				continue;
			}
			
			$this->fail('Expected RestrictedUrlException has not been raised');
		}
	}
}