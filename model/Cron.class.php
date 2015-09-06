<?php
class Cron extends ModelBase
{
    public $MAIN_DB_INSTANCE = 'share_main';
    public $RECOMMEND_SAME_AGE_TABLE_NAME = 'recommend_same_age';
    public $KVSTORE_INSTANCE = 'user_listen';
    
    /**
     * cron进程执行
     * 添加同龄在听到推荐表
     * 按照年龄段，将用户收听次数最多的专辑展示在首页
     * @param I $babyagetype	当前用户的宝宝年龄段
     * @param I $start			列表开始位置
     * @param I $len			列表长度
     * @return array
     */
    public function cronSaveSameAgeToDb()
    {
        $start = 0;
        $len = 100;
        $addtime = date("Y-m-d H:i:s");
    
        $redisobj = AliRedisConnecter::connRedis($this->KVSTORE_INSTANCE);
        $db = DbConnecter::connectMysql($this->MAIN_DB_INSTANCE);
        $sql = "INSERT INTO {$this->RECOMMEND_SAME_AGE_TABLE_NAME}
            (`albumid`, `agetype`, `order`, `status`, `addtime`)
            VALUES (?, ?, ?, ?, ?)";
        
        foreach ($this->AGE_TYPE_LIST as $babyagetype) {
            $albumkey = RedisKey::getRankListenAlbumKey($babyagetype);
            $albumidlist = $redisobj->zRevRange($albumkey, $start, $len - 1);
            if (empty($albumidlist)) {
                continue;
            }
            
            foreach ($albumidlist as $albumid) {
                $st = $db->prepare($sql);
                $res = $st->execute(array($albumid, $babyagetype, 100, $this->RECOMMEND_STATUS_OFFLINE, $addtime));
                if (empty($res)) {
                    continue;
                }
            }
        }
    
        return true;
    }
    
}