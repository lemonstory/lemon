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
	public static function getAlbumInfoKeys($albumids)
	{
	    $albumidarr = array();
	    foreach ($albumids as $albumid) {
	        $albumidarr[] = self::getAlbumInfoKey($albumid);
	    }
	    return $albumidarr;
	}
    
	/**
	 * 故事信息
	 */
	public static function getStoryInfoKey($storyId)
	{
		return 'story_info_'.$storyId;
	}
	public static function getStoryInfoKeys($storyids)
	{
	    $storyidarr = array();
	    foreach ($storyids as $storyid) {
	        $storyidarr[] = self::getStoryInfoKey($storyid);
	    }
	    return $storyidarr;
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
	public static function getAlbumStoryListKey($albumId, $page, $len)
	{
		return 'album_story_list_' . $albumId . '_' . $page . '_' . $len;
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
	public static function getAlbumCommentListKey($params)
	{
		return 'album_comment_list_'.serialize($params);
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
	
	
	// 热门搜索关键词列表
	public static function getHotSearchContentListKey()
	{
	    return 'search_hscl';
	}
	// 搜索内容信息记录
	public static function getSearchContentCountInfoKey($searchcontent)
	{
	    return 'search_scci' . urlencode($searchcontent);
	}
	
	
	// 用户id与设备关联信息
	public static function getUserImsiInfoKey($resid, $restype)
	{
	    return 'imsi_uii_' . $resid . '_' . $restype;
	}
	public static function getUserImsiInfoByUimidKey($uimid)
	{
	    return 'imsi_uii_uimid_' . $uimid;
	}
	
	
	// 专辑与标签关联：某个专辑的所有标签列表key
	public static function getAlbumTagRelationKeyByAlbumId($albumid)
	{
	    return 'atr_albumid_' . $albumid;
	}
	// 专辑与标签关联：批量获取多个专辑的所有标签列表key
	public static function getAlbumTagRelationKeyByAlbumIds($albumids)
	{
	    $albumidarr = array();
	    foreach ($albumids as $albumid) {
	        $albumidarr[] = self::getAlbumTagRelationKeyByAlbumId($albumid);
	    }
	    return $albumidarr;
	}
	// 专辑与标签关联：某个id的key
	public static function getAlbumTagRelationKeyById($id)
	{
	    return "atr_id_{$id}";
	}
	public static function getAlbumTagRelationKeyByIds($ids)
	{
	    $idarr = array();
	    foreach ($ids as $id) {
	        $idarr[] = self::getAlbumTagRelationKeyById($id);
	    }
	    return $idarr;
	}
	
	
	// 通过标签ID获取标签信息
	public static function getTagInfoKeyById($tagid)
	{
	    return "tag_tagid_" . $tagid;
	}
	// 批量获取标签信息
	public static function getTagInfoKeyByIds($tagids)
	{
	    $tagidarr = array();
	    foreach ($tagids as $tagid) {
	        $tagidarr[] = self::getTagInfoKeyById($tagid);
	    }
	    return $tagidarr;
	}
	// 通过标签名称获取标签信息
	public static function getTagInfoKeyByName($tagname)
	{
	    return "tag_tagname_" . $tagname;
	}

	//获首页缓存key
    public static function getIndexDataKey($babyagetype)
    {
        return 'index_data_' . $babyagetype;
    }
}