<?php
namespace ValuFileStorage\Service;

use ValuSo\Feature;
use ValuSo\Annotation as ValuService;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;

/**
 * Local file storage implementation
 *
 * @author juhasuni
 *
 * @ValuService\Context("native")
 */
abstract class AbstractFileService
    implements Feature\ConfigurableInterface
{
    use Feature\OptionsTrait;

	protected $optionsClass = 'ValuFileStorage\Service\FileServiceOptions';

	/**
     * @var \Doctrine\ODM\MongoDB\Id\UuidGenerator
	 */
	protected $uuidGenerator;

	/**
	 * Test whether or not a file exists
	 *
	 * @param string $url
	 * @return boolean
	 *
	 * @ValuService\Context({"cli", "native"})
	 */
	public function exists($url)
	{
	    $this->testUrl($url);

	    $file = $this->getFileByUrl($url, false);
	    return $file !== null;
	}

	/**
     * Prepare file insertion
     *
     * @param string $sourceUrl
     * @param string $targetUrl
     * @return array
	 */
	protected function prepareInsert($sourceUrl, $targetUrl)
	{
	    $this->testUrl($targetUrl);

	    $sourceUrl	= $this->canonicalUrl($sourceUrl);
	    $scheme		= $this->parseScheme($sourceUrl);
	    $bytes      = null;
	    $file		= null;

	    if (!in_array($scheme, array('file', 'http', 'https', 'data'))) {
	        throw new Exception\UnsupportedUrlException(
                'Unsupported source URL scheme:%SCHEME%',
                array('SCHEME' => $scheme));
	    }

	    $source = null;
	    $bytes  = null;

	    if (!$this->isSafeUrl($sourceUrl)) {
	        throw new Exception\RestrictedUrlException(
	                sprintf('Reading files is not allowed from URL %s',$sourceUrl));
	    }

	    if ($scheme == 'file') {
	        $path = $this->parsePath($sourceUrl);
	        $file = trim($path);

	        if(!file_exists($file)){
	            throw new Exception\FileNotFoundException(
	                    'Local file %FILE% not found',
	                    array('FILE' => $file));
	        }
	    } elseif (($bytes = $this->readBytes($sourceUrl)) === false) {
	        throw new Exception\FileNotFoundException(
	                'Remote URL %URL% could not be read',
	                array('URL' => $sourceUrl));
	    }

	    return array(
	        'file' => $file,
            'bytes' => $bytes
        );
	}

	/**
	 * Retrieve instance of file by URL
	 *
	 * @param string $url
	 * @param boolean $throwException
	 */
	protected abstract function getFileByUrl($url, $throwException = false);

	/**
	 * Retrieve URL's canonical form
	 *
	 * @param string $url
	 * @return string
	 */
	protected function canonicalUrl($url)
	{
	    $url = trim($url);

	    if ($this->isDataUrl($url)) {
	        return $url;
	    }

	    // Assume local URL if scheme is missing
	    if (strpos($url, '://') === false) {
	        $url = 'file://' . $url;
	    }

	    if (strpos($url, 'file://') === 0) {
	        $path = $this->parsePath($url);
	        $path = realpath($path);

	        $url = 'file://' . $path;
	    }

	    return $url;
	}

	/**
	 * Read bytes from source URL
	 *
	 * @return string
	 */
	protected function readBytes($url)
	{
	    if ($this->isDataUrl($url)) {
	        return $this->parseDataUrl($url, 'value', '');
	    } else {
	        return file_get_contents($url);
	    }
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

	/**
	 * Is the URL scheme supported?
	 *
	 * @param string $url
	 * @return boolean
	 */
	protected function isSupportedScheme($url){
	    return strpos($url, $this->getOption('url_scheme').'://') === 0;
	}

	/**
	 * Test that URL (scheme) is supported and throw exception
	 * if not
	 *
	 * @param string $url
	 * @throws Exception\UnsupportedUrlException
	 * @return boolean
	 */
	protected function testUrl($url)
	{
	    if ($this->isSupportedScheme($url)) {
	        return true;
	    } else {
	        throw new Exception\UnsupportedUrlException(
                'URL %URL% is not supported',
                array('URL' => $url));
	    }
	}

	/**
	 * Parse scheme for URL
	 *
	 * @param string $url
	 * @return mixed|string
	 */
	protected function parseScheme($url)
	{
	    if (($scheme = parse_url($url, PHP_URL_SCHEME)) != false) {
	        return $scheme;
	    } else if($this->isDataUrl($url)) {
	        return 'data';
	    } else if(strpos($url, '://') === false) {
	        return 'file';
	    } else {
	        return substr($url, 0, strpos($url, '://'));
	    }
	}

	/**
	 * Test if given URL is a data URL (RFC 2397)
	 *
	 * @param string $uri
	 * @return boolean
	 */
	protected function isDataUrl($url)
	{
	    return stripos($url, 'data:') === 0;
	}

	/**
	 * Parse spec from data URL
	 *
	 * @param string $url
	 * @param string $spec
	 * @param string $default
	 * @return boolean|string
	 */
	protected function parseDataUrl($url, $spec, $default = '')
	{
	    $matchmap = [
	        'mime_type' => 1,
	        'charset'   => 2,
	        'encoding'  => 3,
	        'data'      => 4,
	        'value'     => null
	    ];

	    if (!array_key_exists($spec, $matchmap)) {
	        return false;
	    }

	    $matches = [];
	    if (preg_match('/^data:([^;,]+)?(?:;charset=([a-z0-9\-]+))?(?:;(base64))?,(.*)/i', $url, $matches)) {

	        if ($spec === 'value') {
	            if (strtolower($matches[3]) === 'base64') {
	                return base64_decode($matches[4]);
	            } else {
	                return urldecode($matches[4]);
	            }
	        } else {
	            $key = $matchmap[$spec];
	            return $matches[$key] !== '' ? $matches[$key] : $default;
	        }
	    } else {
	        return false;
	    }
	}

	/**
	 * Parse basename based on source URL
	 *
	 * @param string $sourceUrl
	 * @return string
	 */
	protected function parseBasename($sourceUrl)
	{
	    if ($this->isDataUrl($sourceUrl)) {
	        $mimeType   = $this->parseDataUrl($sourceUrl, 'mime_type', 'text/plain');
	        return 'data.' . str_replace('/', '_', $mimeType);
	    } else {
	        return basename($this->parsePath($sourceUrl));
	    }
	}

	/**
	 * Parse file path from URL
	 *
	 * @param string $url
	 * @return string
	 */
	protected function parsePath($url)
	{
	    if (($path = parse_url($url, PHP_URL_PATH)) != false) {
	        return $path;
	    } else {
	        $scheme = $this->parseScheme($url);
	        $prefix = $scheme . '://';

	        $url = $prefix . 'localhost' . substr($url, strlen($prefix));
	        return parse_url($url, PHP_URL_PATH);
	    }
	}

    public function setUuidGenerator($uuidGenerator)
    {
        $this->uuidGenerator = $uuidGenerator;
    }

    public function getUuidGenerator()
    {
        if (!$this->uuidGenerator) {
	        $this->setUuidGenerator(new UuidGenerator());
	    }

        return $this->uuidGenerator;
    }

	/**
     * Generate new UUID token
     *
     * @return string
	 */
	protected function generateUuid()
	{
	    return $this->getUuidGenerator()
            ->generateV5($this->getUuidGenerator()->generateV4(), php_uname('n'));
	}
}
