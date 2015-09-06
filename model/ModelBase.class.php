<?php
class ModelBase extends ConfigVar
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