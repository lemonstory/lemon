<?php
class ModelBase
{
	private $errorinfo=array();
	public function setError($errorinfo)
	{
		$this->errorinfo = $errorinfo;
	}
	public function getError()
	{
		return $this->errorinfo;
	}
}