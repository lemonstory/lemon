<?php
/**
 * 使用实例：
 *     // 设置model为AnTaskStat，指定key的列表cache
 *     Yii::app()->FzCacheWrapper->setListCache('AnTaskStat', 'huqq', array(0 => array('id' => 1), 1 => array('id' => 2)));
 *     
 *     // 获取model为AnTaskStat,指定key的列表cache
 *     Yii::app()->FzCacheWrapper->getListCache('AnTaskStat', 'huqq');
 *     
 *     // 删除model为AnTaskStat的列表cache
 *     Yii::app()->FzCacheWrapper->deleteNSCache('AnTaskStat');
 *     
 * @author Huqq
 */
class CacheWrapper
{
    // 定义项目命名空间
    public $PROJECT_NAME_SPACE = 'xnm';
    
    public $MODEL_NAME_SPACE = '';
    
    // cache开关
    public $CACHE_BUTTON = TRUE;
    
    public $SPLITE = '_';
    
    // 存储cache的redis实例，以及存储table_name_list命名空间值
    public $CACHE_INSTANCE = 'cache';
    
    // list cache expire
    public $LIST_CACHE_EXPIRE = 3600;
    
    
    /**
     * 获取列表cache
     * @param S $modelName      表名
     * @param S $key            缓存参数Key,如"courseid_videoid"
     * @return array
     */
    public function getListCache($modelName, $key)
    {
        if (empty($modelName) || empty($key)) {
            return array();
        }
        
        if ($this->CACHE_BUTTON == true) {
            $this->MODEL_NAME_SPACE = $this->getModelNS($modelName, true);
            if (empty($this->MODEL_NAME_SPACE)) {
                return array();
            }
            
            $cacheRedisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
            $modelNameSpaceValue = $cacheRedisObj->get($this->MODEL_NAME_SPACE);
            if (empty($modelNameSpaceValue)) {
                return array();
            }
            
            // cacheKeyName => project_tablename_list_time
            $cacheKeyName = $this->PROJECT_NAME_SPACE . $this->SPLITE . $this->MODEL_NAME_SPACE . $this->SPLITE . $modelNameSpaceValue;
            // cacheKey => project_tablename_list_time_id1_id2
            $cacheKey = $cacheKeyName . $this->SPLITE . $key;
            $result = $cacheRedisObj->get($cacheKey);
            if (empty($result)) {
                return array();
            }
            return unserialize($result);
        } else {
            return array();
        }
    }
    
    
    /**
     * 设置model列表命名空间缓存
     * @param S $modelName    model名称
     * @param S $key          列表缓存参数key 如："courseid_videoid"
     * @param S/A $value      缓存值
     * @return bool
     */
    public function setListCache($modelName, $key, $value)
    {
        if (empty($modelName) || empty($key) || empty($value)) {
            return false;
        }
        
        if ($this->CACHE_BUTTON == true) {
            $this->MODEL_NAME_SPACE = $this->getModelNS($modelName, true);
            if (empty($this->MODEL_NAME_SPACE)) {
                return false;
            }
            
            $cacheRedisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
            $modelNameSpaceValue = $cacheRedisObj->get($this->MODEL_NAME_SPACE);
            if (empty($modelNameSpaceValue)) {
                $nowTime = time();
                $cacheRedisObj->setex($this->MODEL_NAME_SPACE, $this->LIST_CACHE_EXPIRE, $nowTime);
                $modelNameSpaceValue = $nowTime;
            }
            
            // cacheKeyName => project_tablename_list_time
            $cacheKeyName = $this->PROJECT_NAME_SPACE . $this->SPLITE . $this->MODEL_NAME_SPACE . $this->SPLITE . $modelNameSpaceValue;
            // cacheKey => project_tablename_list_time_id1_id2
            $cacheKey = $cacheKeyName . $this->SPLITE . $key;
            $result = $cacheRedisObj->setex($cacheKey, $this->LIST_CACHE_EXPIRE, serialize($value));
            
            return $result;
        } else {
            return true;
        }
    
    }
    
    
    /**
     * 清除 ns cache
     * @param S $modelName
     * @return boolean
     */
    public function deleteNSCache($modelName)
    {
        if (empty($modelName)) {
            return false;
        }
        
        $this->MODEL_NAME_SPACE = $this->getModelNS($modelName, true);
        if (empty($this->MODEL_NAME_SPACE)) {
            return false;
        }
        
        $cacheRedisObj = AliRedisConnecter::connRedis($this->CACHE_INSTANCE);
        $result = $cacheRedisObj->setex($this->MODEL_NAME_SPACE, $this->LIST_CACHE_EXPIRE, time());
        return $result;
    }
    
    
    /**
     * 生成model的命名空间
     * @param S $modelName    DB对应的model名称
     * @param B $isList       是否为列表
     */
    private function getModelNS($tableName, $isList = false)
    {
        $modelNameSpace = '';
        if ($isList == true) {
            $modelNameSpace = $tableName . $this->SPLITE . 'list';
        } else {
            $modelNameSpace = $tableName . $this->SPLITE . 'info';
        }
        
        return $modelNameSpace;
    }
    
}
?>