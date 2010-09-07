<?php
require_once("OLS_class_lib/webServiceServer_class.php");
class openfile_server extends webServiceServer
{
  private $error;

  public function getFile($params)
  {
    //print_r($params);
    $response = $this->get_file_response($params);

    if( $this->error )
      return $this->send_error();

    return $response;
  }

  public function checkFile($params)
  {
    $response = $this->check_file_response($params);
   
    if( $this->error )
      return $this->send_error();

    return $response;

  }

  private function check_file_response($params)
  {
    // get namespaces from config
    $ns = $this->config->get_value("xmlns","setup");
    // setup response object
    $response->checkFileResponse->_namespace = $ns["types"];
    // get the file
    $fileinfo = $this->check_file($params);
    // set response object
    if( is_array($fileinfo) )
      foreach( $fileinfo as $key=>$val )
	{
	  $response->checkFileResponse->_value->$key->_namespace = $ns["types"];  
	  $response->checkFileResponse->_value->$key->_value = $val;
	}

    // return responseobject
    return $response;
  }

  private function get_file_response($params)
  {
    // get namespaces from config
    $ns = $this->config->get_value("xmlns","setup");
    // setup response object
    $response->getFileResponse->_namespace = $ns["types"];
    // get the file
    $fileinfo = $this->get_file($params);
    // set response object
    if( is_array($fileinfo) )
      foreach( $fileinfo as $key=>$val )
	{
	  $response->getFileResponse->_value->$key->_namespace = $ns["types"];  
	  $response->getFileResponse->_value->$key->_value = $val;
	}

    // return responseobject
    return $response;
  }

  private function check_file($params)
  {
    $fileName = $params->fileName->_value;
    $fileType = $params->fileType->_value;
    $filePath = $params->filePath->_value;
    $version = $params->version->_value;
    $host = $params->host->_value;
    
    if( !$fileName || !$fileType || !$version )
      {
	$this->error = "Error in request";
	return;
      }

    $path = $host.$filePath.$fileName."_".$version.".".$fileType;   

    if( $this->is_url($host) )
      $fileinfo = $this->get_remote_file_info($path);
    else
      $fileinfo = $this->get_file_info($path);

    return $fileinfo;
  }

 
  private function get_file($params)
  {
    $fileName = $params->fileName->_value;
    $fileType = $params->fileType->_value;
    $filePath = $params->filePath->_value;
    $version = $params->version->_value;
    $host = $params->host->_value;
    
    if( !$fileName || !$fileType || !$version )
      {
	$this->error = "Error in request";
	return;
      }

    $path = $host.$filePath.$fileName."_".$version.".".$fileType;   

    if( $this->is_url($host) )
      $fileinfo = $this->get_remote_file_info($path);
    else
      $fileinfo = $this->get_file_info($path);
     
    if( $fileinfo['verified'] )
      $content = $this->get_content( $path );

    $fileinfo['content'] = $content;
    return $fileinfo;
   
  }

  private function get_remote_file_info($url)
  {
    $fp = @fopen($url,'r');
    if( $fp === false )
      {
	$this->error = "Could not read file: ".$url;
	return;
      }

    $headers = stream_get_meta_data($fp);

    $result['verified'] = "true";
    // file size
    $result['fileSize'] = $headers['unread_bytes'];
    // last modification
    $result['timeStamp'] = $this->httpdate_to_timestap($headers['wrapper_data'][3]);
    //$result['timeStamp'] = $headers['wrapper_data'][3];
    // type
    $result['fileType'] = $headers['wrapper_data'][8];
    // filePath
    $result['filePath'] = $url;
    
    fclose($fp);
    
    return $result;
  }

  private function httpdate_to_timestap($httpdate)
  {
    $index = strpos($httpdate,":");
    $time = substr($httpdate,$index+1);
    // echo $time;
    //exit;
    return strtotime($time);
  }

  private function is_url($host)
  {
    $contents = parse_url($host);
    if( $contents['scheme']=='http' || $contents['scheme']=='https' )
      return true;
    return false;
  }

  private function get_file_info($file)
  {
    $stat = @stat($file);
    $type = @filetype($file);
     if( !$stat || !$type )
      {
	$this->error = "Could not read file: ".$file;
	return false;
      }

    $result['verified'] = "true";
    // file size
    $result['fileSize'] = $stat['size'];
    // last modification
    $result['timeStamp'] = $stat['mtime'];
    // type
    $result['fileType'] = $type;
    // filePath
    $result['filePath'] = $file;

    return $result;
  }

  private function get_content($file)
  {
    $result = file_get_contents($file);
    
    if( $result === false )
      {
	$this->error = "Could not read file: ".$file;
	return;
      }
    
    return $result;   
    
  }
  
  private function send_error()
  {
    $response->getFileResponse->_value->error->_value = $this->error;
    return $response;
  }
}

$server=new openfile_server("openfile.ini");
$server->handle_request();

?>