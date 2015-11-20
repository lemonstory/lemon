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
	 * 微信联合登录，是否第一次授权的Key
	 * @param S $openid
	 * @return string
	 */
	public static function getWechatLoginFirstKey($openid)
	{
	    return 'wechatrecount_' . $openid;
	}
	/**
	 * 微信联合登录关联信息key
	 * @param I $uid
	 * @return string
	 */
	public static function getWechatRelationInfoKey($uid)
	{
	    return 'wechatrelation_' . $uid;
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
	
	/**
	 * 宝宝信息key
	 */
	public static function getBabyInfoKeys($babyids)
	{
	    $idarr = array();
	    foreach ($babyids as $id) {
	        $idarr[] = self::getBabyInfoKey($id);
	    }
	    return $idarr;
	}
	
	/**
	 * 地址信息Key
	 */
	public static function getAddressInfoKeys($addressids)
	{
	    $idarr = array();
	    foreach ($addressids as $id) {
	        $idarr[] = self::getAddressInfoKey($id);
	    }
	    return $idarr;
	}
    
	/**
	 * 专辑列表key
	 */
	public static function getAlbumListKey($params)
	{
		return 'album_list_'.serialize($params);
	}
    
	/**
	 * 专辑信息
	 */
	public static function getAlbumInfoKey($albumId)
	{
		return 'album_info_'.$albumId;
	}
    
	/**
	 * 故事信息
	 */
	public static function getStoryInfoKey($storyId)
	{
		return 'story_info_'.$storyId;
	}

	/**
	 * 故事列表
	 */
	public static function getStoryListKey($params)
	{
		return 'story_list_'.serialize($params);
	}

	/**
	 * 专辑的故事列表
	 */
	public static function getAlbumStoryListKey($albumId)
	{
		return 'album_story_list_'.$albumId;
	}
    
	/**
	 * 评论信息
	 */
	public static function getCommentInfoKey($commentId)
	{
		return 'comment_info_'.$commentId;
	}

	/**
	 * 专辑的评论列表
	 */
	public static function getAlbumCommentListKey($albumId)
	{
		return 'album_comment_list_'.$albumId;
	}
	
	/**
	 * 用户的收藏专辑信息
	 */
	public static function getUserFavInfoByAlbumIdKey($uid, $albumid)
	{
	    return 'fav_ufi_' . $uid . '_' . $albumid;
	}
	
	/*
	 * 专辑被收藏的总数
	 */
	public static function getAlbumFavCountKey($albumid)
	{
	    return 'fav_afc_' . $albumid;
	}
	public static function getAlbumFavCountKeys($albumids)
	{
	    $albumidarr = array();
	    foreach ($albumids as $albumid) {
	        $albumidarr[] = self::getAlbumFavCountKey($albumid);
	    }
	    return $albumidarr;
	}
	
	/*
	 * 用户收藏的总数
	 */
	public static function getUserFavCountKey($uid)
	{
	    return 'fav_ufc_' . $uid;
	}
	
	// 用户收听的专辑信息
	public static function getUserListenAlbumInfoKey($uimid, $albumid)
	{
	    return 'listen_ulai_' . $uimid . '_' . $albumid;
	}
	// 用户收听的故事信息
	public static function getUserListenStoryInfoKey($uimid, $storyid)
	{
	    return 'listen_ulsi_' . $uimid . '_' . $storyid;
	}
	// 专辑收听总数
	public static function getAlbumListenCountKey($albumid)
	{
	    return 'listen_alc_' . $albumid;
	}
	public static function getAlbumListenCountKeys($albumids)
	{
	    $albumidarr = array();
	    foreach ($albumids as $albumid) {
	        $albumidarr[] = self::getAlbumListenCountKey($albumid);
	    }
	    return $albumidarr;
	}
}