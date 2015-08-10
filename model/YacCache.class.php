<?php
class YacCache
{
	public static  function setKvData($key,$value,$ttl=30) 
	{
		$yac = new Yac();
		$a = $yac->set($key,$value,$ttl);
	}
	
	public static function setArrayData($kvs,$ttl=30)
	{
		$yac = new Yac();
		$yac->set($kvs,$ttl);
	}
	
	public static function getData($key)
	{
		$yac = new Yac();
		$result = $yac->get($key);
		return $result;
	}
	
	
	public static function del($key)
	{
		$yac = new Yac();
		$yac->delete($kvs,$ttl);
	}
}