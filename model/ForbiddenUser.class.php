<?php
// 封号
class ForbiddenUser extends ModelBase
{
    public $FORBIDDEN_DB_INSTANCE = 'share_manage';
    public $FORBIDDEN_TABLE_NAME = 'forbidden_user';
    
    public $FORBIDDENUSER_REASONS = array(
        'sqnr' => '色情内容',
        'zzyl' => '政治言论',
        'ggnr' => '广告内容',
        'rsgj' => '人身攻击',
        'eysp' => '恶意刷屏',
        'rlgz' => '扰乱规则',
    );
    
    public function addforbidden($uid, $reasontype = '', $admininput = '')
    {
        if (empty($uid)){
            $this->setError(ErrorConf::paramError());
            return false;
        }
    
        $ossObj = new AliOss();
        $ossObj->deleteAvatarOss($uid);
        
        $userObj = new User();
        $data = array('status'=>-2, 'avatartime'=>time());
        $ret = $userObj->setUserinfo($uid, $data);
        if (empty($ret)){
            return false;
        }
        
        $addtime = date('Y-m-d H:i:s');
        $db = DbConnecter::connectMysql($this->FORBIDDEN_DB_INSTANCE);
        $sql = "insert into {$this->FORBIDDEN_TABLE_NAME}".
                " (uid,reasontype,admininput,addtime)".
                " values (?,?,?,?)";
        $st = $db->prepare ( $sql );
        $ret = $st->execute (array($uid,$reasontype,$admininput,$addtime));
        if (!$ret){
            return false;
        }
        
        return true;
    }
    
}