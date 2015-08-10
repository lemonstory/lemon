<?php
/**
 * 设备型号管理类
 *
 */
class MClient extends ModelBase
{
    public $MCLIENT_DB_INSTANCE = 'share_manage';
    public $MCLIENT_TABLE_NAME = 'phonedata';
    public $MCLIENT_VERSION_TABLE_NAME = 'phoneversiondata';
    
    /**
     * 添加设备信息
     * @param string $ua
     * @param string $desc
     * @param int $adminuid
     */
    public function addClient($ua, $desc, $adminuid)
    {
        if (empty($ua) || empty($desc) || empty($adminuid)){
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "insert into {$this->MCLIENT_TABLE_NAME}".
                " (`ua`,`desc`,`editoruid`)".
                " values (?,?,?)";
        $st = $db->prepare ( $sql );
        $ret = $st->execute (array($ua,$img,$desc,$adminuid));
        if ($ret){
            $list = $this->getClientDataAllDb();
            $this->setClientData($list);
            return true;
        } else {
            return false;
        }
    }
    public function addClientVersion($phoneid, $version, $adminuid)
    {
        if (empty($phoneid) || empty($version) || empty($adminuid)){
            $this->setError(ErrorConf::paramError());
            return false;
        }
        
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "insert into {$this->MCLIENT_VERSION_TABLE_NAME} (`phoneid`,`version`,`editoruid`) values (?,?,?)";
        $st = $db->prepare ( $sql );
        $ret = $st->execute (array($phoneid, $version, $adminuid));
        if ($ret){
            $list = $this->getClientVersionDataByPhoneid($phoneid);
            $this->setClientVersionDataByPhoneid($phoneid, $list);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 启用话题列表（上线）
     * @param int $id
     */
    public function setClientData($list)
    {
        try {
            $conn =AliRedisConnecter::connRedis("tuturank");
            return $conn->set('clientdata', json_encode($list));
        } catch (Exception $e){
            
        }
    }
    public function setClientVersionDataByPhoneid($phoneid, $versionlist)
    {
        try {
            $key = 'clientversiondata-' . $phoneid;
            $conn =AliRedisConnecter::connRedis("tuturank");
            return $conn->set($key, json_encode($versionlist));
        } catch (Exception $e){
            
        }
    }
    
    /**
     * 获得设备信息
     */
    public function getClientDataAll()
    {
        try {
            $conn =AliRedisConnecter::connRedis("tuturank");
            $jsondata = $conn->get('clientdata');
            $list = @json_decode($jsondata, true);
            if (empty($list)){
                $list = $this->getClientDataAllDb();
                $this->setClientData($list);
            }
            return $list;
        } catch (Exception $e){
            
        }
    }
    
    public function getClientInfoDb($ids)
    {
        if (empty($ids)) {
            return array();
        }
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $idstr = '';
        foreach ($ids as $id) {
            $idstr .= "'$id',";
        }
        $idstr = rtrim($idstr, ',');
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "select `id`, `ua`, `desc` from {$this->MCLIENT_TABLE_NAME} where `id` IN ($idstr)";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($data)){
            return array();
        }
        $list = array();
        foreach ($data as $value) {
            $list[$value['id']] = $value;
        }
        return $list;
    }
    public function getClientVersionInfoDb($versionids)
    {
        if (empty($versionids)) {
            return array();
        }
        if (!is_array($versionids)) {
            $versionids = array($versionids);
        }
        $idstr = '';
        foreach ($versionids as $id) {
            $idstr .= "'$id',";
        }
        $idstr = rtrim($idstr, ',');
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "select * from {$this->MCLIENT_VERSION_TABLE_NAME} where `id` IN ($idstr)";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($data)){
            return array();
        }
        $list = array();
        foreach ($data as $value) {
            $list[$value['id']] = $value;
        }
        return $list;
    }
    
    
    public function getClientDataAllDb()
    {
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "select `ua`, `desc` from {$this->MCLIENT_TABLE_NAME} ";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($data) || !is_array($data)){
            return array();
        }
        $list = array();
        foreach ($data as $value){
            $list[$value['ua']] = $value['desc'];
        }
        return $list;
    }
    public function getClientVersionDataByPhoneid($phoneid)
    {
        if (empty($phoneid)) {
            return array();
        }
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "select * from {$this->MCLIENT_VERSION_TABLE_NAME} WHERE `phoneid` = ?";
        $st = $db->prepare ( $sql );
        $st->execute(array($phoneid));
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        if (empty($data) || !is_array($data)){
            return array();
        }
        $list = array();
        foreach ($data as $value){
            $list[$value['id']] = $value;
        }
        return $list;
    }
    
    
    public function getClientTotal()
    {
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "select count(id) from {$this->MCLIENT_TABLE_NAME}";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetchColumn()+0;
        return $data;
    }
    
    public function getClientPageList($page, $len=10)
    {
        $page = (int)$page > 0 ? (int)$page : 1;
        $start = ($page-1)*$len;
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "select * from {$this->MCLIENT_TABLE_NAME} order by id desc limit {$start},{$len}";
        $st = $db->prepare ( $sql );
        $st->execute();
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($data)){
            return array();
        }
        return $data; 
    }
    
    
    
    public function deleteClient($id)
    {
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "delete from {$this->MCLIENT_TABLE_NAME} where id = {$id}";
        $st = $db->prepare ( $sql );
        if ($st->execute()){
            $list = $this->getClientDataAllDb();
            $this->setClientData($list);
            return true;
        } else {
            return false;
        }
    }
    public function deleteClientVersion($versionid, $phoneid)
    {
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "delete from {$this->MCLIENT_VERSION_TABLE_NAME} where id = {$versionid}";
        $st = $db->prepare ( $sql );
        if ($st->execute()){
            $list = $this->getClientVersionDataByPhoneid($phoneid);
            $this->setClientVersionDataByPhoneid($phoneid, $list);
            return true;
        } else {
            return false;
        }
    }
    
    
    public function updateClient($id, $ua, $desc, $editoruid)
    {
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "update {$this->MCLIENT_TABLE_NAME} set `ua` = ?, `desc` = ?, `editoruid` = ? where id = ?";
        $st = $db->prepare ( $sql );
        if ($st->execute(array($ua, $desc, $editoruid, $id))){
            $list = $this->getClientDataAllDb();
            $this->setClientData($list);
            return true;
        } else {
            return false;
        }
    }
    public function updateClientVersion($versionid, $phoneid, $version, $editoruid)
    {
        $updatetime = date("Y-m-d H:i:s", $updatetime);
        $db = DbConnecter::connectMysql($this->MCLIENT_DB_INSTANCE);
        $sql = "update {$this->MCLIENT_VERSION_TABLE_NAME} set `version` = ?, `editoruid` = ? where id = ?";
        $st = $db->prepare ( $sql );
        if ($st->execute(array($version, $editoruid, $versionid))){
            $list = $this->getClientVersionDataByPhoneid($phoneid);
            $this->setClientVersionDataByPhoneid($phoneid, $list);
            return true;
        } else {
            return false;
        }
    }
    
}