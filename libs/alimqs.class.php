<?php

/**
 * mqs.class.php 	消息队列服务MQS 
 *
 * $Author: 	徐阳(xybb501@aliyun.com)
 * $Date: 		2014-07-30
 */
 
/* ---阿里Mqs消息--- */
class Mqs{

	public $AccessKey		= '';
	public $AccessSecret	= '';
	public $CONTENT_TYPE	= 'text/xml;utf-8';
	public $MQSHeaders		= '2014-07-08';
	public $queueownerid	= '';
	public $mqsurl			= '';
	
	
	function __construct($key,$secret,$queueownerid,$mqsurl){
		$this->AccessKey	= $key;
		$this->AccessSecret = $secret;
		$this->queueownerid	= $queueownerid;
		$this->mqsurl		= $mqsurl;
	}
	
	//curl 操作	 受保护的方法
	protected function requestCore( $request_uri, $request_method, $request_header, $request_body = "" ){
        if( $request_body != "" ){
            $request_header['Content-Length'] = strlen( $request_body );
        }
        $_headers = array(); foreach( $request_header as $name => $value )$_headers[] = $name . ": " . $value;
        $request_header = $_headers;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
        $res = curl_exec($ch);
        curl_close($ch);
        return $data = explode("\r\n\r\n",$res);
    }
	//获取错误Handle  受保护的方法
	protected function errorHandle($headers){
        preg_match('/HTTP\/[\d]\.[\d] ([\d]+) /', $headers, $code);
        if($code[1]){
            if( $code[1] / 100 > 1 && $code[1] / 100 < 4 ) return false;
            else return $code[1];
        }
    }
	//签名函数	受保护的方法
	protected function getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders = array(), $CanonicalizedResource = "/" ){
        $order_keys = array_keys( $CanonicalizedMQSHeaders );
        sort( $order_keys );
        $x_mqs_headers_string = "";
        foreach( $order_keys as $k ){
            $x_mqs_headers_string .= join( ":", array( strtolower($k), $CanonicalizedMQSHeaders[ $k ] . "\n" ) );
        }
        $string2sign = sprintf(
            "%s\n%s\n%s\n%s\n%s%s",
            $VERB,
            $CONTENT_MD5,
            $CONTENT_TYPE,
            $GMT_DATE,
            $x_mqs_headers_string,
            $CanonicalizedResource
        );
        $sig = base64_encode(hash_hmac('sha1',$string2sign,$this->AccessSecret,true));
        return "MQS " . $this->AccessKey . ":" . $sig;
    }
	//获取时间 受保护的方法
	protected function getGMTDate(){
        date_default_timezone_set("UTC");
        return date('D, d M Y H:i:s', time()) . ' GMT';
    }
	//解析xml	受保护的方法
	protected function getXmlData($strXml){
		$pos = strpos($strXml, 'xml');
		if ($pos) {
			$xmlCode=simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
			$arrayCode=$this->get_object_vars_final($xmlCode);
			return $arrayCode ;
		} else {
			return '';
		}
	}
	//解析obj	受保护的方法
	protected function get_object_vars_final($obj){
		if(is_object($obj)){
			$obj=get_object_vars($obj);
		}
		if(is_array($obj)){
			foreach ($obj as $key=>$value){
				$obj[$key]=$this->get_object_vars_final($value);
			}
		}
		return $obj;
	}
	
}


/* ---阿里Mqs消息列队--- */
Class Queue extends Mqs{
	//创建一个新的消息队列。
	public function Createqueue($queueName,$parameter=array()){
		//默认值规定好
		$queue=array('DelaySeconds'=>0,'MaximumMessageSize'=>65536,'MessageRetentionPeriod'=>345600,'VisibilityTimeout'=>30,'PollingWaitSeconds'=>30);
		foreach($queue as $k=>$v){ 
			foreach($parameter as $x=>$y){ 
				if($k==$x){	$queue[$k]=$y;	}		//修改默认值
			}
		}
		$VERB = "PUT";
        $CONTENT_BODY = $this->generatequeuexml($queue);
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
		}
		return $msg;
	}
	
	//修改消息队列的属性。
	public function Setqueueattributes($queueName,$parameter=array()){
		//默认值规定好
		$queue=array('DelaySeconds'=>0,'MaximumMessageSize'=>65536,'MessageRetentionPeriod'=>345600,'VisibilityTimeout'=>30,'PollingWaitSeconds'=>30);
		foreach($queue as $k=>$v){ 
			foreach($parameter as $x=>$y){ 
				if($k==$x){	$queue[$k]=$y;	}		//修改默认值
			}
		}
		$VERB = "PUT";
        $CONTENT_BODY = $this->generatequeuexml($queue);
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName . "?metaoverride=true";
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//获取消息队列的属性
	public function Getqueueattributes($queueName){
		$VERB = "GET";
        $CONTENT_BODY = "" ;
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//删除一个已创建的消息队列。
	public function Deletequeue($queueName){
		$VERB = "DELETE";
        $CONTENT_BODY = "" ;
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
		}
		return $msg;
	}
	
	//获取多个消息队列列表
	public function ListQueue($prefix='',$number='',$marker=''){
		$VERB = "GET";
        $CONTENT_BODY = "" ;
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders,
        );
		
		if($prefix!=''){$CanonicalizedMQSHeaders['x-mqs-prefix'] = $prefix;	}
		if($number!=''){$CanonicalizedMQSHeaders['x-mqs-ret-number'] = $number;	}
		if($marker!=''){$CanonicalizedMQSHeaders['x-mqs-marker'] = $marker;	}
		
		$RequestResource = "/";
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}	
	//数据转换到xml
	private function generatequeuexml($queue=array()){
		header('Content-Type: text/xml;');  
		$dom = new DOMDocument("1.0", "utf-8");
		$dom->formatOutput = TRUE; 
		$root = $dom->createElement("Queue");//创建根节点
		$dom->appendchild($root);
		$price=$dom->createAttribute("xmlns"); 
		$root->appendChild($price); 
		$priceValue = $dom->createTextNode('http://mqs.aliyuncs.com/doc/v1/'); 
		$price->appendChild($priceValue); 
		
		foreach($queue as $k=>$v){ 
			$queue = $dom->createElement($k);  
			$root->appendChild($queue);  
			$titleText = $dom->createTextNode($v);  
			$queue->appendChild($titleText);  
		}
		return $dom->saveXML();  
	}
	
}

class Message extends Mqs{

	//发送消息到指定的消息队列
	public function SendMessage($queueName,$msgbody,$DelaySeconds=0,$Priority=8){
		$VERB = "POST";
        $CONTENT_BODY = $this->generatexml($msgbody,$DelaySeconds,$Priority);
        $CONTENT_MD5  = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
        $RequestResource = "/" . $queueName . "/messages";
        $sign = $this->getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource );
        $headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok和返回值数组,错误返回错误代码和错误原因数组！
		$msg=array();
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//接收指定的队列消息 
	public function ReceiveMessage($queue,$Second){
		$VERB = "GET";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
        $RequestResource = "/" . $queue . "/messages?waitseconds=".$Second;
        $sign = $this->getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource );
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok和返回值数组,错误返回错误代码和错误原因数组！
		$msg=array();
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//删除已经被接收过的消息
	public function DeleteMessage($queueName,$ReceiptHandle){
		$VERB = "DELETE";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName . "/messages?ReceiptHandle=".$ReceiptHandle;
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
        }else{
			$msg['state']="ok";
		}
		return $msg;
	}
	
	//查看消息，但不改变消息状态（是否被查看或接收）
	public function PeekMessage($queuename){
		$VERB = "GET";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
        $RequestResource = "/" . $queuename . "/messages?peekonly=true";
        $sign = $this->getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource );
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok和返回内容数组,错误返回错误代码和错误原因数组！
		$msg=array();
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	//修改未被查看消息时间，
	public function ChangeMessageVisibility($queueName,$ReceiptHandle,$visibilitytimeout){
	
		$VERB = "PUT";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName . "/messages?ReceiptHandle=".$ReceiptHandle."&VisibilityTimeout=".$visibilitytimeout;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//返回状态，正确返回ok,错误返回错误代码！
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	//数据转换到xml
	private function generatexml($msgbody,$DelaySeconds=0,$Priority=8){
		header('Content-Type: text/xml;');  
		$dom = new DOMDocument("1.0", "utf-8");
		$dom->formatOutput = TRUE; 
		$root = $dom->createElement("Message");//创建根节点
		$dom->appendchild($root);
		$price=$dom->createAttribute("xmlns"); 
		$root->appendChild($price); 
		$priceValue = $dom->createTextNode('http://mqs.aliyuncs.com/doc/v1/'); 
		$price->appendChild($priceValue); 
		
		$msg=array('MessageBody'=>$msgbody,'DelaySeconds'=>$DelaySeconds,'Priority'=>$Priority);
		foreach($msg as $k=>$v){ 
			$msg = $dom->createElement($k);  
			$root->appendChild($msg);  
			$titleText = $dom->createTextNode($v);  
			$msg->appendChild($titleText);  
		}
		return $dom->saveXML();  
	}
}