<?php
namespace ValuFileStorage\Service;

use Valu\Utils\UuidGenerator;
use ValuFileStorage\Service\Exception;
use ValuFileStorage\Model;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * MongoDB file storage implementation
 * 
 * @author juhasuni
 *
 */
class MongoFile extends AbstractFileService
{
	
	protected $optionsClass = 'ValuFileStorage\Service\File\FileOptions';
	
	/**
	 * Document manager
	 *
	 * @var DocumentManager
	 */
	protected $dm;
	
	public function __construct(DocumentManager $documentManager){
	    $this->setDocumentManager($documentManager);
	}
	
	public static function version()
	{
	    return '1.0';
	}
	
	/**
	 * Add file to storage
	 *
	 * This method reads file contents from the URL specified.
	 * Supported URL schemes are file, http and https.
	 *
	 * @param string $sourceUrl File URL
	 * @param string $targetUrl Target URL
	 * @param array $metadata File metadata
	 * @throws \ValuFileStorage\Service\Exception\FileNotFoundException
	 */
	public function insert($sourceUrl, $targetUrl, array $metadata = array())
	{
	    $specs = $this->prepareInsert($sourceUrl, $targetUrl);
	    $path = $this->parsePath($targetUrl);
	
	    // Create a new file or overwrite existing
	    if ($path == '/' || $path == '') {
	
	        $file = new Model\File(
                $this->generateUrl($sourceUrl),
                $metadata
	        );
	
	        $this->getDocumentManager()->persist($file);
	
	    } else {
	        $file = $this->getFileByUrl($targetUrl);
	    }
	    
	    if ($specs['file']) {
	        $file->setFile($specs['file']);
	    } else {
	        $file->setBytes($specs['bytes']);
	    }
	
	    $this->getDocumentManager()->flush($file);
	    
	    // Must be cleared
	    $this->getDocumentManager()->clear();
	    
	    return $this->fetchFileMetadata($file);
	}

	/**
	 * Read file data
	 * 
	 * @param string $url
	 * @return string|boolean
	 * @throws Exception\FileNotFoundException
	 */
	public function read($url){
	    
	    $this->testUrl($url);
	    
	    $file = $this->getFileByUrl($url);
	    return $file->getFile()->getBytes();
	}
	
	/**
	 * Write data to file
	 * 
	 * @param string $url
	 * @param string $data
	 * @return boolean
	 */
	public function write($url, $data){
	    $this->testUrl($url);
	    
	    $file = $this->getFileByUrl($url);
	    $file->setBytes($data);
	    
	    $this->getDocumentManager()->flush();
	    
	    // Must be cleared
	    $this->getDocumentManager()->clear();
	    
	    return true;
	}
	
	/**
	 * Retrieve metadata for file
	 * 
	 * @param string $url    File URL
	 * @return array|null File info (url, filesize, mimeType)
	 */
	public function getMetadata($url)
	{
	    $this->testUrl($url);
		return $this->fetchFileMetadata(
	        $this->getFileByUrl($url));
	}
	
	/**
	 * Retrieves the total file storage size in bytes
	 *
	 * @return int
	 */
	public function totalSize($url = null)
	{
	    if ($url !== null) {
	        $this->testUrl($url);
	    }
	    
	    $result = $this->getDocumentManager()
	        ->getDocumentCollection('ValuFileStorage\Model\File')
	        ->group(
	            array(),
	            array('sum' => 0),
	            "function (obj, prev) { prev.sum += obj.length; }");
	     
	    return isset($result['retval'][0])
	        ? $result['retval'][0]['sum'] : 0;
	}
	
	/**
	 * Retrieve path in local file system (not supported)
	 *
	 * @param string $url
	 * @throws Exception\UnsupportedOperationException
	 */
	public function getPath($url)
	{
	    $this->testUrl($url);
	    
	    throw new Exception\UnsupportedOperationException(
	            'Operation ' . __FUNCTION__ . 'is not supported for this storage implementation');
	}
	
	/**
	 * Retrieve local copy of the file
	 * 
	 * @param string $url
	 * @return string
	 */
	public function getLocalCopy($url)
	{
	    $this->testUrl($url);
	    
	    $tmpFile = tempnam(sys_get_temp_dir(), 'valu-temp');
	    
	    $file = $this->getFileByUrl($url);
	    $file->getFile()->write($tmpFile);
	    
	    return $tmpFile;
	}
	
	/**
	 * Delete file
	 * 
	 * @param string $url   File URL in filesystem
	 * @return boolean		True if file was found and removed
	 */
	public function delete($url)
	{
	    $this->testUrl($url);

		try{
		    $dm   = $this->getDocumentManager();
		    $file = $this->getFileByUrl($url);
		    $dm->remove($file);
		    $dm->flush();
		    
		    return true;
		    
		} catch(Exception\FileNotFoundException $e) {
		    return false;
		}
	}
	
	/**
	 * Delete all files in file storage
	 * 
	 * @return int Number of files deleted
	 */
	public function deleteAll($url)
	{
	    $this->testUrl($url);
	    $batchSize = $this->getOption('delete_batch_size');
	     
	    $qb = $this->getFileRepository()->createQueryBuilder();
	    $qb	->select('id')
	        ->findAndRemove()
	        ->hydrate(false);
	     
	    if($batchSize){
	        $qb->limit($batchSize);
	    }
	    
	    $removed = 0;
	    
	    do{
	        $result = $qb ->getQuery()
	                      ->execute();
	        
	        $removed += sizeof($result);
	        
	    }while(sizeof($result));
	    
	    return $removed;
	}
	
	/**
	 * Set document manager instance
	 *
	 * @param DocumentManager $dm
	 * @return FileStorage
	 */
	public function setDocumentManager(DocumentManager $dm){
	    $this->dm = $dm;
	    return $this;
	}
	
	/**
	 * Retrieve document manager instance
	 *
	 * @return DocumentManager
	 */
	public function getDocumentManager(){
	    return $this->dm;
	}
	
	/**
	 * Fetch file info
	 * 
	 * @param File $file
	 * @return array
	 */
	protected function fetchFileMetadata(Model\File $file){
		return array(
		    'url' => $file->getUrl(),
	        'filesize' => $file->getSize(),
	        'mimeType' => $file->getMimeType()        
        );
	}
	
	/**
	 * Generate file storage URL
	 *
	 * @param string $sourceUrl Source file URL
	 * @return string			Mongodb URL in form mongo://<ID>/<FILENAME>
	 */
	protected function generateUrl($sourceUrl){
	    $id = UuidGenerator::generate(UuidGenerator::VERSION_3, uniqid(), 'valu-file-storage');
	    $basename = basename(parse_url($sourceUrl, PHP_URL_PATH));
	
	    return $this->getOption('url_scheme') . ':///' . $id . '/' .$basename;
	}
	
	/**
	 * Retrieve file by URL
	 * 
	 * @param string $url
	 * @return Model\File
	 * @throws Exception\FileNotFoundException
	 */
	protected function getFileByUrl($url){
	     
	    $repository	= $this->getFileRepository();
	    $file 		= $repository->findOneByUrl($url);
	     
	    if(!$file){
	    	throw new Exception\FileNotFoundException(
    	        'File not found from URL '.$url);
	    }
	    
	    return $file;
	}
	
	/**
	 * Retrieve file repository
	 *
	 * @return FileRepository
	 */
	protected function getFileRepository(){
	    return $this->getRepository('File');
	}
	
	/**
	 * Retrieve repository
	 *
	 * @param string $type
	 * @return NodeRepository
	 */
	protected function getRepository($type){
	    return $this->getDocumentManager()->getRepository('ValuFileStorage\\Model\\'.$type);
	}
}