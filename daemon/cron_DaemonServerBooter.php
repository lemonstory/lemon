<?php
/*
 * 实时需要执行的进程加到list中，无需crontab启动
 */
include 'DaemonBase.php';
class cron_DaemonServerBooter extends DaemonBase 
{
    public $isWhile = false;
    protected function deal() 
    {
        $processlist = $this->getProcessList();
        foreach ($processlist as $process) {
            $this->startpro($process);
        }
        exit();
    }
    
    private function getProcessList() 
    {
        $list = array(
                'album/deal_userListenStory.php',
                'album/deal_saveAlbumToSearch.php',
                
                'userinfo/deal_userImsiActionLog.php',
                'userinfo/deal_loadUserQqavatar.php',
                'userinfo/deal_repairUserInfo.php',
        );
        return $list;
    }
    
    protected function checkLogPath() {}
}
new cron_DaemonServerBooter();