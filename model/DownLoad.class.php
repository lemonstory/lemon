<?php
class DownLoad extends ModelBase
{
	public $MAIN_DB_INSTANCE = 'share_main';
	public $DOWNLOAD_TABLE_NAME = 'download_story';
	public $CACHE_INSTANCE = 'cache';
	
	public $STATUS_DOWN_ING = 1; // 下载中状态
	public $STATUS_DOWN_OVER = 2;// 已下载完状态
	
	
	/** 将文件内容执行header输出
	 * 若为断线续传，http请求需要添加'HTTP_RANGE' = "bytes=4300000-" 的header头
	 * @param S      $file      要下载的文件
	 * @param I      $fileLen   文件的字节数
	 * @param B      $header    是否输出下载头
	 */
	/* public function startDownload($file, $fileLen, $header = true)
	{
	    ob_start();
	    if (empty($file) || empty($fileLen)) {
	        return false;
	    }
	    $endBytes = $fileLen - 1;
	
	    if ($header == true) {
	        header("Content-Type: application/force-download");
	        header("Content-Type: application/octet-stream");
	        header("Content-Type: application/download");
	        header("Content-Transfer-Encoding: binary");
	        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	        header("Cache-Control: must-ridate, post-check=0, pre-check=0");
	        header("Pragma: no-cache");
	        header("Content-Disposition: attachment; filename=" . basename($file));
	    }
	
	    //$_SERVER['HTTP_RANGE'] = "bytes=2000-";
	    //$rangeLog = "/alidata1/range.log";
	    //$rangeFp = fopen($rangeLog, 'a+');
	    if (isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
	        //fwrite($rangeFp, "@@" . $_SERVER['HTTP_RANGE'] . "\n");
	        //fclose($rangeFp);
	
	        // 断点续传, $_SERVER['HTTP_RANGE'] 的值 bytes=4390912-
	        list($name, $range) = explode("=", $_SERVER['HTTP_RANGE']);
	        $range = str_replace("-", "", $range);
	        $startBytes = $range;
	        $newLength = $endBytes - $range;              // 获取下次下载的长度
	        if ($header == true) {
	            header("HTTP/1.1 206 Partial Content");   // 部分内容，表示需要续传
	            header("Accept-Length: $newLength");
	            header("Accept-Ranges: bytes");           // 表示服务器可以接受range请求，并求度量单位是byte
	            header("Content-Range: bytes " . $range . "-" . $endBytes . "/" . $fileLen); //Content-Range: bytes 4908618-4988927/4988928
	            header("Content-Length: $newLength");     // 输入总长
	        }
	    } else {
	        //fwrite($rangeFp, "##0\n");
	        //fclose($rangeFp);
	
	        // 第一次连接或非断点下载
	        $startBytes = 0;
	        if ($header == true) {
	            header("Accept-Length: $fileLen");
	            header("Content-Range: bytes 0-$endBytes/$fileLen");
	            header("Content-Length: $fileLen");
	        }
	    }
	    
	    $bytesLen = 102400;
	    while ($startBytes < $endBytes) {
	        $startBytes += $bytesLen;
	        
	        $result = $this->getFileContent($file, $startBytes, $bytesLen);
	        $fileContent = $result['fileContent'];
	        echo $fileContent;
	        if ($header == true) {
	            flush();
	            ob_flush();
	        }
	    }
	    
	    return true;
	} */
	
	
	/**
	 * 获取下载文件的内容
	 * @param S $file          文件物理路径及名称
	 * @param I $startBytes    获取文件的起始字节数：$_SERVER['HTTP_RANGE'] 的值 bytes=4390912-，则$startBytes=4390912
	 * @param I $bytesLen      每次读取指定长度字节，默认一次读取10kb
	 * @return array           返回读取的文件内容，以及当前文件的指针位置
	 */
	/* public function getFileContent($file, $startBytes = 0, $bytesLen = 10240)
	{
	    if (empty($file)) {
	        return false;
	    }
	    if (!file_exists($file)) {
	        return false;
	    }
	    $fileLen = filesize($file);
	    if (empty($fileLen)) {
	        return false;
	    }
	    if (empty($bytesLen)) {
	        $bytesLen = 10240;
	    }
	    $bytesPost = 0;
	    $fileContent = '';
	
	    $fp = fopen($file, 'rb');
	    if (!empty($startBytes)) {
	        // 断点后再次连接, 偏移字节数
	        fseek($fp, $startBytes);
	    }
	    if (! feof($fp)) {
	        $fileContent = fread($fp, $bytesLen);
	    }
	    // 记录当前文件指针位置
	    //$bytesPost = ftell($fp);
	    fclose($fp);
	
	
	    return array('fileContent' => $fileContent, 'bytesPost' => $bytesPost);
	} */
	
	
	/**
	 * 获取uid或设备的下载的专辑记录
	 * @param I $uimid
	 * @param I $albumid
	 * @return array
	 */
	/* public function getDownLoadInfoByAlbumId($uimid, $albumid)
	{
	    if (empty($uid) || empty($albumid)) {
	        $this->setError(ErrorConf::paramError());
	        return array();
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "SELECT * FROM {$this->DOWNLOAD_TABLE_NAME} WHERE `uid` = ? and `albumid` = ?";
	    $st = $db->prepare($sql);
	    $st->execute(array($uid, $albumid));
	    $res = $st->fetch(PDO::FETCH_ASSOC);
	    if (empty($res)) {
	        return array();
	    } else {
	        return $res;
	    }
	} */
	
	
	/**
	 * uid或设备号，开始下载故事任务
	 * @param I $uimid
	 * @param I $albumid    专辑Id
	 * @param I $storyid    故事id
	 * @return boolean
	 */
	public function addDownLoadStoryInfo($uimid, $albumid, $storyid)
	{
		if (empty($uimid) || empty($albumid) || empty($storyid)) {
			$this->setError(ErrorConf::paramError());
			return false;
		}
		$taskid = time();
		$taskstatus = $this->STATUS_DOWN_ING;
		$addtime = date("Y-m-d H:i:s");
		
		$db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
		$sql = "INSERT INTO {$this->DOWNLOAD_TABLE_NAME} 
			(`uid`, `albumid`, `taskid`, `taskstatus`, `addtime`) 
			VALUES (?, ?, ?, ?, ?)";
		$st = $db->prepare($sql);
		$res = $st->execute(array($uid, $albumid, $taskid, $taskstatus, $addtime));
		return $res;
	}
	
	
	/**
	 * 专辑任务下载完成，更新任务状态
	 * @param I $uid
	 * @param I $albumid   专辑id
	 * @param I $taskid    任务id
	 * @return boolean
	 */
	public function updateUserDownLoadAlbumOver($uid, $albumid, $taskid)
	{
	    if (empty($uid) || empty($albumid) || empty($taskid)) {
	        return false;
	    }
	    $taskstatus = $this->STATUS_DOWN_OVER;
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "UPDATE {$this->DOWNLOAD_TABLE_NAME} SET `taskstatus` = ? WHERE `uid` = ? and `taskid` = ?";
	    $st = $db->prepare ( $sql );
	    $st->execute (array($taskstatus, $uid, $taskid));
	    return true;
	}
	
	/**
	 * 用户删除下载任务
	 * @param I $uid
	 * @param I $albumid
	 * @param I $taskid
	 * @return boolean
	 */
	public function delUserDownLoadAlbum($uid, $albumid, $taskid)
	{
	    if (empty($uid) || empty($albumid) || empty($taskid)) {
	        $this->setError(ErrorConf::paramError());
	        return false;
	    }
	    
	    $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
	    $sql = "DELETE FROM {$this->DOWNLOAD_TABLE_NAME} WHERE `uid` = ? AND `albumid` = ? AND `taskid` = ?";
	    $st = $db->prepare($sql);
	    $res = $st->execute(array($uid, $albumid, $taskid));
	    return $res;
	}
}