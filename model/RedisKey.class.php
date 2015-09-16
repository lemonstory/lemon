<?php
class RedisKey
{
	/**
	 * 按用户宝宝年龄段，收听的专辑排行榜key
	 * @param I $babyagetype	宝宝年龄段
	 * @return string
	 */
	public static function getRankListenAlbumKey($babyagetype)
	{
		$listenalbumkey = 'ranklistenalbum_' . $babyagetype;
		return $listenalbumkey;
	}
	
	
	/**
	 * QQ联合登录，是否第一次授权的Key
	 * @param S $openid
	 * @return string
	 */
	public static function getQqLoginFirstKey($openid)
	{
	    return 'qqrecount_' . $openid;
	}
	
	
	/**
	 * QQ联合登录关联信息key
	 * @param I $uid
	 * @return string
	 */
	public static function getQqRelationInfoKey($uid)
	{
	    return 'qqrelation_' . $uid;
	}
	
	/**
	 * 用户信息key
	 * @param I $uid
	 * @return string
	 */
	public static function getUserInfoKey($uid)
	{
	    return 'ui_' . $uid;
	}
	/**
	 * 单个宝宝信息
	 */
	public static function getBabyInfoKey($babyid)
	{
	    return 'bi_' . $babyid;
	}
	/**
	 * 单个收货地址信息
	 */
	public static function getAddressInfoKey($addressid)
	{
	    return 'ai_' . $addressid;
	}
	/**
	 * 用户的收货地址列表key
	 */
	public static function getUserAddressListKey($uid)
	{
	    return 'ual_' . $uid;
	}
	
	/**
	 * 批量获取用户信息key
	 * @param A $uids
	 * @return string
	 */
	public static function getUserInfoKeys($uids)
	{
	    $uidarr = array();
	    foreach ($uids as $uid) {
	        $uidarr[] = self::getUserInfoKey($uid);
	    }
	    return $uidarr;
	}
	
	public static function getBabyInfoKeys($babyids)
	{
	    $idarr = array();
	    foreach ($babyids as $id) {
	        $idarr[] = self::getBabyInfoKey($id);
	    }
	    return $idarr;
	}
	public static function getAddressInfoKeys($addressids)
	{
	    $idarr = array();
	    foreach ($addressids as $id) {
	        $idarr[] = self::getAddressInfoKey($id);
	    }
	    return $idarr;
	}
	
	
	/**
	 * 用户收听播放故事，延迟处理数据队列
	 * @return string
	 */
	public static function getUserListenStoryQueueKey()
	{
	    return "userlistenstoryqueue";
	}
	
	/**
	 * 专辑数据，添加到opensearch表队列
	 * @return string
	 */
	public static function getAlbumToSearchQueueKey()
	{
	    return "albumtosearch";
	}
}