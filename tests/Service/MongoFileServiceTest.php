<?php
namespace ValuFileStorage\Test\Service;

class MongoFileServiceTest extends AbstractServiceTest
{
	protected static $serviceName = 'ValuFileStorageMongoFile';
    
    protected static $urlScheme = 'mongofs';
    
    protected static $defaultTarget = 'mongofs:///';
    
    protected static $urlPattern = '#^mongofs:///[a-zA-Z0-9\-]+/[^/]+$#';
    
    public function testDeleteNonExisting()
    {
        $this->assertFalse(
            self::service()->delete(static::$urlScheme . ':///does-not-exist')
        );
    }
    
    /*
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
	}*/
	
	/**
	 * @depends testUpload
	 */
    /*
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
	*/
	
	public function testSaveRestrictedFile(){
		return;
		
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
			catch (\ValuFileStorage\Service\Exception\RestrictedUrlException $e){
				continue;
			}
			
			$this->fail('Expected RestrictedUrlException has not been raised');
		}
	}
	
}