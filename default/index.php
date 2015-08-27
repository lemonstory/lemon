<?php
include_once '../controller.php';
class index extends controller
{
	public function action() 
	{
		$uid = $this->getUid();
		$userobj = new User();
		$userinfo = current($userobj->getUserInfo($uid));
		
		$defaultbabyid = $userinfo['defaultbabyid'];
		$userextinfo = new UserExtend();
		$babyinfo = $userextinfo->getUserBabyInfo($defaultbabyid);
		$babyagetype = $userextinfo->getBabyAgeType($babyinfo['age']);
		
		
		// 热门推荐
		$hotrecommendlist = array();
		
		// 同龄在听
		$sameagealbumlist = array();
		$listenobj = new Listen();
		$sameagealbumlist = $listenobj->getRankListSameAgeListen($babyagetype, 0, 12);
		
		
		// 最新上架
		$newalbumlist = array();
		
		// 推广位
		$focuspiclist = array();
		
		// 私人订制
		
		
		$data = array(
			"focuspic" => $focuspiclist,
			"hotrecommend" => $hotrecommendlist,
			"samgeage" => $sameagealbumlist,
			"newalbum" => $newalbumlist,
		);
		$this->showSuccJson($data);
	}
}
new index();