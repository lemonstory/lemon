<?php
include_once '../controller.php';
class index extends controller
{
	public function action() 
	{
		$uid = $this->getUid();
		$userinfo = array();
		$albumids = array();
		$managesysobj = new ManageSystem();
		
		// 热门推荐
		$hotrecommendres = $managesysobj->getRecommendHotList(9);
		if (!empty($hotrecommendres)) {
			foreach ($hotrecommendres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		if (!empty($uid)) {
			$userobj = new User();
			$userinfo = current($userobj->getUserInfo($uid));
			if (!empty($userinfo)) {
				$defaultbabyid = $userinfo['defaultbabyid'];
				$userextinfo = new UserExtend();
				$babyinfo = $userextinfo->getUserBabyInfo($defaultbabyid);
				$babyagetype = $userextinfo->getBabyAgeType($babyinfo['age']);
			}
		} else {
			$babyagetype = 0;
		}
		
		// 同龄在听
		$listenobj = new Listen();
		$sameageres = $listenobj->getSameAgeListenList($babyagetype, 9);
		if (!empty($sameageres)) {
			foreach ($sameageres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		// 最新上架
		$newonlineres = $managesysobj->getNewOnlineList($babyagetype, 9);
		if (!empty($newonlineres)) {
			foreach ($newonlineres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		$albumlist = array();
		if (!empty($albumids)) {
			$albumids = array_unique($albumids);
			$albumobj = new Album();
			$albumlist = $albumobj->getListByIds($albumids);
		}
		
		$hotrecommendlist = array();
		$sameagealbumlist = array();
		$newalbumlist = array();
		if (!empty($hotrecommendres)) {
			foreach ($hotrecommendres as $value) {
				$albumid = $value['albumid'];
				if (!empty($albumlist[$albumid])) {
				    $albuminfo['id'] = $albumlist[$albumid]['id'];
				    $albuminfo['title'] = $albumlist[$albumid]['title'];
				    $albuminfo['cover'] = $albumlist[$albumid]['cover'];
					$hotrecommendlist[] = $albuminfo;
				}
			}
		}
		
		
		if (!empty($sameageres)) {
			foreach ($sameageres as $value) {
				$albumid = $value['albumid'];
				if (!empty($albumlist[$albumid])) {
					$sameagealbumlist[$albumid] = $albumlist[$albumid];
				}
			}
		}
		if (!empty($newonlineres)) {
			foreach ($newonlineres as $value) {
				$albumid = $value['albumid'];
				if (!empty($albumlist[$albumid])) {
					$newalbumlist[$albumid] = $albumlist[$albumid];
				}
			}
		}
		
		// 推广位
		$focuspiclist = array();
		$focuspiclist = $managesysobj->getFocusList(6);
		
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