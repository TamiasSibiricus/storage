<?php
/**
 * A CakePHP datasource for interacting with the amazon S3.
 *
 * Create datasource config in APP/config/database.php:
 * var $amazon_s3 = array(
 * 'datasource' => 'amazon_s3',
 * 'bucket' => 's3.cloudspeakers.com',
 * 'accessKey' => 'xxxx',
 * 'secretKey' => 'xxxx'
 * );
 * 
 * @package AmazonS3
 * @author primeminister
 * @copyright 2011 Ministry of Web Developemt
 * @license MIT
**/
App::import('Vendor', 'AmazonS3.S3');

class AmazonS3Source extends DataSource 
{
	var $description = "Amazon S3 Datasource";

	var $S3 = null; // Holds the S3 object

    /**
     * Constructor
     *
     * @param string $config 
     * @access public
     */
    public function __construct($config) {
        $this->S3 = new S3($config['accessKey'], $config['secretKey'], false);
        parent::__construct($config);
    }
    
    /**
     * Describe
     *
     * @param object $model
     * @return array
     * @access public
     */
    public function describe($model) {
        return $this->_schema['amazon_s3'];
    }
    
    /**
     * Read amazon S3 bucket
     *
     * @param object $model 
     * @param array $queryData
     * @return array
     * @access public
     */
    public function read($model, $queryData = array()) 
    {
        $result = array();
        if (!isset($queryData['conditions']['bucket'])) {
            $queryData['conditions']['bucket'] = $this->config['bucket'];
        }
        print_r($queryData);
        return $result;
    }
     
    /**
     * find items or item on amazon S3
     *
     * @param mixed $type Find by method (first / all) or just the query (method defaults to all)
     * @param mixed $query string or array of search options
     * @return array Array of records
     * @access public
     */
    function find($type, $uri = null, $options=array()) {
	    /*
		if (!is_string($type) || (is_string($type) && !array_key_exists($type, $this->_findMethods))) {
			$uri = $type;
			$type = 'all';
		}
        $options = am(array(
            'bucket'=>$this->config['bucket'],
            'saveTo'=>false,
            'return'=>true
        ), $options);
        
        $result = false;
        switch ($type) {
            case 'info':
                $result = $this->S3->getObjectInfo($options['bucket'], $uri, $options['return']);
                break;
                
            case 'first':
                $result = $this->S3->getObject($options['bucket'], $uri, $options['saveTo']);
                break;
            
            case 'all':
            default:
                $result = $this->S3->getBucket($options['bucket'], null, $uri);
                break;
        }
        pr($result);
		return $result;
		*/
	}
	
	/**
	 * save object to Amazon S3
	 *
	 * @param string $data 
	 * @param string $options 
	 * @return void
	 * @access public
	 */
    public function save($data, $options=array()) {
        $options = array_merge(array(
            'path'=>'',
            'bucket'=>$this->config['bucket'],
            'max-age'=>15768000
        ), $options);
        
        if (!empty($options['path']) && !empty($data)) 
        {
    		// get remote file
    		$file = $data;
    		if (preg_match('/^http:\/\//i', $file)) {
    		    // read remote
    		    $handle = fopen($file, "rb");
                $img = stream_get_contents($handle);
                fclose($handle);
    		    $ext = mb_substr($file, mb_strrpos($file, '.')+1, mb_strlen($file));
    		    // write locally
    		    $file = TMP. uniqid('S3').'.'.$ext;
    		    $handle = fopen($file, "wb");
                fwrite($handle, $img);
                fclose($handle);
            }
            $ctype = $this->_returnMIMEType($file);
        	// now save the object to S3
        	$res = $this->S3->putObjectFile(
        	    $file, 
        	    $options['bucket'], 
        	    $options['path'], 
        	    S3::ACL_PUBLIC_READ, 
        	    array(),
        	    array(
        	        "Cache-Control" => "max-age=". $options['max-age'],
        	        'Content-Type' => $ctype
        	    )
        	);
        	return $options['bucket'] . $options['path'];
    	}
		return false;
    }
    
    /**
     * Return mime-type of file
     *
     * @param string $filename 
     * @return void
     * @access private
     */
    private function _returnMIMEType($filename)
    {
        preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);

        switch(strtolower($fileSuffix[1]))
        {
            case "js" :
                return "application/x-javascript";

            case "json" :
                return "application/json";

            case "jpg" :
            case "jpeg" :
            case "jpe" :
                return "image/jpg";

            case "png" :
            case "gif" :
            case "bmp" :
            case "tiff" :
                return "image/".strtolower($fileSuffix[1]);

            case "css" :
                return "text/css";

            case "xml" :
                return "application/xml";

            case "doc" :
            case "docx" :
                return "application/msword";

            case "xls" :
            case "xlsx" :
            case "xlt" :
            case "xlm" :
            case "xld" :
            case "xla" :
            case "xlc" :
            case "xlw" :
            case "xll" :
                return "application/vnd.ms-excel";

            case "ppt" :
            case "pps" :
                return "application/vnd.ms-powerpoint";

            case "rtf" :
                return "application/rtf";

            case "pdf" :
                return "application/pdf";

            case "html" :
            case "htm" :
            case "php" :
                return "text/html";

            case "txt" :
                return "text/plain";

            case "mpeg" :
            case "mpg" :
            case "mpe" :
                return "video/mpeg";

            case "mp3" :
                return "audio/mpeg3";

            case "wav" :
                return "audio/wav";

            case "aiff" :
            case "aif" :
                return "audio/aiff";

            case "avi" :
                return "video/msvideo";

            case "wmv" :
                return "video/x-ms-wmv";

            case "mov" :
                return "video/quicktime";

            case "zip" :
                return "application/zip";

            case "tar" :
                return "application/x-tar";

            case "swf" :
                return "application/x-shockwave-flash";

            default :
            if(function_exists("mime_content_type"))
            {
                $fileSuffix = mime_content_type($filename);
            }

            return "unknown/" . trim($fileSuffix[0], ".");
        }
    }

    /**
     * close this datasource
     *
     * @access public
     * @return boolean
     * @author primeminister
     */
	function close() {
		return true;
	}
}