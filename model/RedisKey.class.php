<?php
class RedisKey
{
	/**
	 * 收听用户排行榜key
	 * @param I $babyagetype	宝宝年龄段
	 * @return string
	 */
	public static function getRankListenUserKey($babyagetype)
	{
		$listenuserkey = 'ranklistenuser_' . $babyagetype;
		return $listenuserkey;
	}
	
	/**
	 * 收听专辑排行榜key
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
	 * 批量获取用户信息key
	 * @param A $uids
	 * @return string
	 */
	public static function getUserInfoKeys($uids)
	{
	    $uidarr = array();
	    foreach ($uids as $uid) {
	        $uidarr[] = "ui_" . $uid;
	    }
	    return $uidarr;
	}
}