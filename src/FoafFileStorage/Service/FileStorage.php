<?php
namespace FoafFileStorage\Service;

use Foaf\Service\Exception\OperationException,
	Foaf\Service\AbstractService,
	Foaf\Service\Broker,
	Foaf\Service\Response\Http\Binary as BinaryResponse,
	FoafFileStorage\Service\Exception,
	FoafFileStorage\Model\File,
	Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Local file storage implementation
 * 
 * @author juhasuni
 *
 */
class FileStorage extends AbstractService
{
	
	protected $optionsClass = 'FoafFileStorage\Service\FileStorage\FileStorageOptions';
	
	/**
	 * Document manager
	 * 
	 * @var DocumentManager
	 */
	protected $dm;

	/**
	 * Service broker
	 * 
	 * @var Broker
	 */
	protected $serviceBroker = null;
	
	public function __construct(Broker $serviceBroker, DocumentManager $documentManager){
		$this->setServiceBroker($serviceBroker);
		$this->setDocumentManager($documentManager);
	}
	
	public static function version()
	{
	    return '1.0';
	}
	
	/**
	 * Fetch internal URL scheme for this file storage
	 * implementation
	 * 
	 * @return string
	 */
	public function getScheme(){
	    return $this->getOption('url_scheme');
	}

	/**
	 * Add file to storage
	 * 
	 * This method reads file contents from the URL specified.
	 * Supported URL schemes are file, http and https.
	 * 
	 * @param string $url File URL
	 * @param array $metadata File metadata
	 * @throws \Exception
	 */
	public function addFile($url, array $metadata = array())
	{
		$file		= null;
		$url		= trim($url);
		$c			= parse_url($url);
		$scheme		= $c['scheme'];
		$repository	= $this->getFileRepository();
		$storageUrl = $this->generateUrl($url);
		
		if(in_array($scheme, array('file', 'http', 'https'))){
		    
		    $source = null;
		    $bytes = null;
		    
			if(!$this->isSafeUrl($url)){
				throw new Exception\RestrictedUrlException('Reading files is not allowed from URL '.$url);
			}
			
			if($scheme == 'file'){
			    $source = trim($c['path']);
			    
			    if(!file_exists($source)){
			    	throw new Exception\FileNotFoundException('Local file '.$source.' not found');
			    }
			}
			else{
			   if(($bytes = file_get_contents($url)) === false){
			       throw new Exception\FileNotFoundException('Remote file '.$url.' could not be loaded');
			   }
			}

			/**
			 * Save the file to temporary folder, if
			 * file is not found
			 */
			$name	= parse_url($storageUrl, PHP_URL_HOST);

			$arr	= explode('.', basename(parse_url($storageUrl, PHP_URL_PATH)));
			$ext	= sizeof($arr) > 1 ? array_pop($arr) : '';
			if($ext) $name .= '.'.$ext;
			
			if($source){
			    $specs = array(
			    	'file'	=> $source
			    );
			}
			else{
			    $specs = array(
			    	'bytes'	=> $bytes
			    );  
			}
			
			$specs = array_merge(
			    $specs,
		        $metadata
	        );
			
			$file = new \FoafFileStorage\Model\File(
				$storageUrl,
				$specs
			);
			
			$this->getDocumentManager()->persist($file);
			$this->getDocumentManager()->flush($file);
			
			return $this->fetchFileMetadata($file);
		}
		else{
			throw new \Exception('Unsupported URL scheme '.$scheme);
		}
	}
	
	/**
	 * Read file data
	 * 
	 * @param string $url
	 * @return string|boolean
	 * @throws Exception\FileNotFoundException
	 */
	public function readFile($url){
	    
	    if(!$this->isSupportedScheme($url)){
	        return false;
	    }
	    
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
	public function writeFile($url, $data){
	    
	    if(!$this->isSupportedScheme($url)){
	        return false;
	    }
	    
	    $file = $this->getFileByUrl($url);
	    $file->setBytes($data);
	    
	    $this->getDocumentManager()->flush();
	    return true;
	}
	
	/**
	 * Get file as a complete HTTP response
	 * 
	 * @param string $url
	 * @return BinaryResponse|null
	 * @throws Exception\FileNotFoundException
	 */
	public function downloadFile($url){
	    
	    if(!$this->isSupportedScheme($url)){
	        return false;
	    }
	    
	    $response = new BinaryResponse();
	    
	    try{
	    	$file = $this->getFileByUrl($url);
	    }
	    catch (Exception\FileNotFoundException $e){
	        $response->setStatusCode(404);
	        $response->setReasonPhrase($e->getMessage());
	    }
	    
	    $response->setBytes($file->getBytes());
	    $response->headers()->addHeaderLine('Content-Type', $file->getMimeType());
	    $response->headers()->addHeaderLine('Content-Disposition', 'attachment; filename='.$file->getName());
	    
	    return $response;
	}
	
	/**
	 * Retrieve metadata for file
	 * 
	 * @param string $url    File URL
	 * @return array|null File info (url, filesize, mimeType)
	 */
	public function getFileMetadata($url)
	{
	    if(!$this->isSupportedScheme($url)){
	        return null;
	    }
	    
		$repository	= $this->getFileRepository();
		$file 		= $repository->findOneByUrl($url);
		
		return $this->fetchFileMetadata($file);
	}
	
	/**
	 * Delete file
	 * 
	 * @param string $url   File URL in filesystem
	 * @return boolean		True if file was found and removed
	 */
	public function deleteFile($url)
	{
	    
	    if(!$this->isSupportedScheme($url)){
	        return false;
	    }
	    
		$dm			= $this->getDocumentManager();
		$repository	= $this->getFileRepository();
		$file 		= $repository->findOneByUrl($url);

		if($file){
			
			$dm->remove($file);
			$dm->flush($file);
			
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * Delete all files in file storage
	 * 
	 * @return int Number of files deleted
	 */
	public function deleteAllFiles()
	{
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
	 * Set service broker instance
	 * 
	 * @param Broker $broker
	 * @return FileStorage
	 */
	public function setServiceBroker(Broker $broker){
		$this->serviceBroker = $broker;
		return $this;
	}
	
	/**
	 * Retrieve service broker instance
	 * 
	 * @return Broker
	 */
	public function getServiceBroker(){
		return $this->serviceBroker;
	}
	
	/**
	 * Is the URL scheme supported?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isSupportedScheme($url){
	    return parse_url($url, PHP_URL_SCHEME) == $this->getScheme();
	}
	
	/**
	 * Retrieve file by URL
	 * 
	 * @param string $url
	 * @return File
	 * @throws Exception\FileNotFoundException
	 */
	protected function getFileByUrl($url){
	    if(strpos($url, $this->getScheme().'://') !== 0){
	    	return null;
	    }
	     
	    $repository	= $this->getFileRepository();
	    $file 		= $repository->findOneByUrl($url);
	     
	    if(!$file){
	    	throw new Exception\FileNotFoundException('File not found from URL '.$url);
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
	 * Fetch file info
	 * 
	 * @param File $file
	 * @return array
	 */
	protected function fetchFileMetadata(File $file){
		return $file->toArray(array(
			'url',
			'filesize',
			'mimeType'
		));
	}
	
	/**
	 * Retrieve repository
	 * 
	 * @param string $type
	 * @return NodeRepository
	 */
	protected function getRepository($type){
		return $this->getDocumentManager()->getRepository('FoafFileStorage\\Model\\'.$type);
	}
	
	/**
	 * Generate file storage URL
	 * 
	 * @param string $sourceUrl Source file URL
	 * @return string			Mongodb URL in form mongo://<ID>/<FILENAME>
	 */
	protected function generateUrl($sourceUrl){
		$id			= md5($sourceUrl . uniqid());
		$basename 	= basename(parse_url($sourceUrl, PHP_URL_PATH));
		
		return $this->getScheme() . '://' . $id . '/' .$basename;
	}
	
	/**
	 * Is URL safe for reading?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isSafeUrl($url){
		return 	$this->isWhitelisted($url) &&
				!$this->isBlacklisted($url);
	}
	
	/**
	 * Is URL whitelisted?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isWhitelisted($url){
		return $this->matchesArray($url, $this->getOption('whitelist'));
	}
	
	/**
	 * Is URL blacklisted?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isBlacklisted($url){
		return $this->matchesArray($url, $this->getOption('blacklist'));
	}
	
	/**
	 * Match given URL against array of regular expressions and
	 * return true if a match is found
	 * 
	 * @param string $url
	 * @param array $regexps
	 * @return boolean
	 */
	protected function matchesArray($url, $regexps){
		if(sizeof($regexps)){
			foreach ($regexps as $regexp){
				$re = '#' . $regexp . '#i';
				
				if(preg_match($re, $url)){
					return true;
				}
			}
		}
		
		return false;
	}
}