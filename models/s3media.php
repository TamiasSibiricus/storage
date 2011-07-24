<?php
/**
 * S3mediaModel
 *
 * PHP version 5
 * 
 * some code inspired from primeminister CakePHP plugin
 * 
 * @category Model
 * @package  Croogo
 * @version  1.0
 * @author   Denys Kyselov <denys.kyselov@gmail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.cmgroup.org.ua
 */

//Maybe in future we use datasource for access to AWS S3 
//App::import('Datasource', 'Storage.AmazonS3');

App::import('Vendor', 'Storage.S3', array('file' => 'S3'.DS.'S3.php'));
 
class S3media extends AppModel {
  /**
   * Model name
   *
   * @var string
   * @access public
   */
  var $name = 'S3media';
    
  var $useTable = false;
  
  var $_config = array();
  
  var $_S3 = null; // Holds the S3 object
  
  /**
   * Constructor
   *
   * @param string $config 
   * @access public
   */
  public function __construct() {
    
    $this->_config['accessKey']  = Configure::read('Service.s3accessKey', '');
    $this->_config['secretKey']  = Configure::read('Service.s3secretKey', '');
    $this->_config['path']       = Configure::read('Service.mediaPath', 'uploads');
    $this->_config['bucketName'] = Configure::read('Service.s3bucketName', '');
    $this->_config['max-age']    = 15768000;

    $this->_S3 = new S3($this->_config['accessKey'], $this->_config['secretKey']);
    
    parent::__construct();
  }
  
  public function verifyBucket(){
    
    if (!$buckets = @$this->_S3->listBuckets()){
      //trigger_error(__('Could not connect to S3 service', true), E_USER_WARNING);
      return false;
    }
    if (!in_array($this->_config['bucketName'], $buckets)){
      $this->_S3->putBucket($this->_config['bucketName'], S3::ACL_PUBLIC_READ);
    }
    
    return true;
    
  }
    
  /**
   * Read amazon S3 bucket
   *
   * @param object $model 
   * @param array $queryData
   * @return array
   * @access public
   */
  public function read() 
  {
      
      //$contents = $this->_S3->getBucket($this->_config['bucketName']);
      //echo "S3::getBucket(): Files in bucket ".$this->_config['bucketName'].": ".print_r($contents, 1);
      //$result = array();
      //if (!isset($queryData['conditions']['bucket'])) {
      //    $queryData['conditions']['bucket'] = $this->config['bucket'];
      //}
      //print_r($queryData);
      //return $result;
  }

  /**
   * save object to Amazon S3
   *
   * @param string $data 
   * @param string $options 
   * @return void
   * @access public
   */
  public function save($data = null, $validate = true, $fieldList = array()) {
        
    if (!empty($data)) {
      // get remote file
      if (preg_match('/^http:\/\//i', $data['path'])) {
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
      } else {
        $file = WWW_ROOT . str_replace('/', DS, $data['path']);
      }
      $ctype = $this->_returnMIMEType($data['slug']);
      // now save the object to S3
      $res = $this->_S3->putObject(
        $this->_S3->inputFile($file, false), 
        $this->_config['bucketName'], 
        $this->_config['path'].'/'.$data['slug'], 
        S3::ACL_PUBLIC_READ, 
        array(),
        array(
          "Cache-Control" => "max-age=15768000",
          'Content-Type' => $ctype
        )
      );
    
      return true;
    
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
?>