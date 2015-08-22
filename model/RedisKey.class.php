<?php
class RedisKey
{
	public static $RANK_KEY_LITEN_USER = 'ranklistenuser';
	public static $RANK_KEY_LITEN_ALBUM = 'ranklistenalbum';
	
	/**
	 * 收听用户排行榜key
	 * @param I $babyagetype	宝宝年龄段
	 * @return string
	 */
	public static function getRankListenUserKey($babyagetype)
	{
		$listenuserkey = self::RANK_KEY_LITEN_USER . '_' . $babyagetype;
		return $listenuserkey;
	}
	
	/**
	 * 收听专辑排行榜key
	 * @param I $babyagetype	宝宝年龄段
	 * @return string
	 */
	public static function getRankListenAlbumKey($babyagetype)
	{
		$listenalbumkey = self::RANK_KEY_LITEN_ALBUM . '_' . $babyagetype;
		return $listenalbumkey;
	}
}