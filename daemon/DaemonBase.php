<?php
include_once (dirname(dirname(__FILE__))."/init.php");
$_SERVER['isdaemon'] = 1;
/**
 * 异步进程的基类
 */
abstract class DaemonBase
{
	protected $processnum 		= 1;		//允许启动进程的数量
	protected $isWhile 			= true;		//启动后运行一次deal还是循环运行
	protected $sleepTime  		= 1;		//休息时间
	protected $phpbin			= '/alidata/server/php/bin/php';
	public function __construct()
	{
		$maxleavtime = time()+900+rand(10,30);  //设置最大生命周期
		$this->checkLogPath();
		if(!$this->cProcessNum()) 
		{
			exit('processnum limit '.$this->processnum.'。');
		}
		do{
			if(time()-$maxleavtime>0)
			{
				exit;
			}
			$this->deal();
			if($this->isWhile===true){
				ob_get_clean();
			}
		}while ($this->isWhile===true);
	}
	abstract protected function deal();
	abstract protected function checkLogPath();
	/**
	 * 控制脚本的运行数量
	 *
	 * @return true or fasle;
	 */
	private function cProcessNum() {
		$num	= $this->getProcessSeq();
		if($num > $this->processnum)
		{
			return false;
		}
		return true;
	}
	
	protected function getProcessSeq(){
		$cmd = @popen("ps -ef | grep '{$_SERVER['SCRIPT_FILENAME']}' | grep -v grep | wc -l", 'r');
		$num = trim(@fread($cmd, 512));
		$num += 0;
		@pclose($cmd);
		return $num;
	}
	protected function getDaemonPath()
	{
		$daemonpath    		= dirname(__FILE__).'/';
		return $daemonpath;
	}
	protected function startpro($process) {
		$count=1;
		if(is_array($process))
		{
			$file = $process[0];
			$count = $process[1];
		}else{
			$file = $process;
		}
		$daemonpath = $this->getDaemonPath();
		for ($i=0;$i<$count;$i++)
		{
			$cmd = "{$this->phpbin} $daemonpath{$file} > /dev/null &";
			$fp = @popen($cmd, "r");
			if($fp) @pclose($fp);
		}
		

	}
	
 
}
?>