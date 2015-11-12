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
	 * 收听的用户榜单key
	 */
	public static function getRankListenUserKey()
	{
	    return 'ranklistenuser';
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

	public static function getAlbumListKey($params)
	{
		return 'album_list_'.serialize($params);
	}

	public static function getAlbumInfoKey($albumId)
	{
		return 'album_info_'.$albumId;
	}

	public static function getStoryInfoKey($storyId)
	{
		return 'story_info_'.$storyId;
	}

	public static function getStoryListKey($params)
	{
		return 'story_list_'.serialize($params);
	}

	public static function getAlbumStoryListKey($albumId)
	{
		return 'album_story_list_'.$albumId;
	}

	public static function getCommentInfoKey($commentId)
	{
		return 'comment_info_'.$commentId;
	}

	public static function getAlbumCommentListKey($albumId)
	{
		return 'album_comment_list_'.$albumId;
	}
	
}