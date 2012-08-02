<?php
namespace FoafFileStorage\Model;

use Foaf\Model\ArrayAdapterTrait;
use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="foaf_file_storage")
 */
class File
{
    use ArrayAdapterTrait;
    
    /**
     * @ODM\Id
     * @var int
     */
    protected $id;
    
	/**
	 * Physical file location
	 * 
	 * @ODM\String
	 * @var string
	 */
	protected $url;
	
	/**
	 * File
	 * 
	 * @ODM\File
	 * @var string
	 */
	protected $file;
	
	/**
	 * @ODM\Int
	 * @var int
	 */
	protected $filesize;
	
	/**
	 * @ODM\String
	 * @var string
	 */
	protected $mimeType;
	
	/** @ODM\Field */
    private $uploadDate;

	/** @ODM\Field */
    private $length;

	/** @ODM\Field */
    private $chunkSize;

	/** @ODM\Field */
    private $md5;

	public function __construct($url, array $specs = array()){
	    
		$this->setUrl($url);
		
		if(isset($specs['file'])){
		    $this->setFile($specs['file']);
		}
		
		if(isset($specs['bytes'])){
		    $this->setBytes($specs['bytes']);
		}
	}
	
	/**
	 * Return ID for resource
	 *
	 * @return int
	 */
	public function getId(){
	    return $this->id;
	}
	
	/**
	 * Get physical file location
	 * 
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}
	
	public function getFilename()
	{
	    return $this->file->getFilename();
	}
	
	/**
	 * Get file
	 * 
	 * @return \MongoGridFSFile
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Set local file
	 * 
	 * @param string $path
	 */
	public function setFile($path)
	{
		if(file_exists($path) && is_file($path) && is_readable($path)){
			
			$finfo = new \finfo();
			
			$this->setFilesize(filesize($path));
			$this->setMimeType($finfo->file($path, FILEINFO_MIME_TYPE));
			
			$this->file = $path;
		}
		else{
			throw new \Exception('File not found '.$path);
		}
	}
	
	public function getBytes()
	{
	    return $this->file->getBytes();
	}
	
	public function setBytes($bytes)
	{
	    $finfo = new \finfo();
	    
	    $this->setFilesize(strlen($bytes));
	    $this->setMimeType($finfo->buffer($bytes, FILEINFO_MIME_TYPE));
	    
	    $path = parse_url($this->getUrl(), PHP_URL_PATH);
	    
	    $this->file = new GridFSFile();
	    $this->file->setBytes($bytes);
	}
	
	/**
	 * Get filesize
	 * 
	 * @return int
	 */
	public function getFilesize()
	{
		return $this->filesize;
	}

	/**
	 * Get mime type
	 * 
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}
	
	/**
	 * Set filesize
	 *
	 * @param int $filesize
	 */
	protected function setFilesize($filesize)
	{
	    $this->filesize = intval($filesize);
	}

	/**
	 * Set file mime etpy
	 * 
	 * @param string $mimeType
	 */
	protected function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;
	}
	
	/**
	 * Set physical file location as URL
	 *
	 * @param string $url
	 */
	protected function setUrl($url)
	{
	    $this->url = $url;
	}
}