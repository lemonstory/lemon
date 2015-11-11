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
		$aliossobj = new AliOss();
		
		// 热门推荐
		$hotrecommendres = $managesysobj->getRecommendHotList(1, 9);
		if (!empty($hotrecommendres)) {
			foreach ($hotrecommendres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		$babyagetype = 0;
		if (!empty($uid)) {
			$userobj = new User();
			$userinfo = current($userobj->getUserInfo($uid, 1));
			if (!empty($userinfo)) {
			    $userextobj = new UserExtend();
			    $babyagetype = $userextobj->getBabyAgeType($userinfo['age']);
			}
		}
		
		// 同龄在听
		$listenobj = new Listen();
		$sameageres = $listenobj->getSameAgeListenList($babyagetype, 1, 9);
		if (!empty($sameageres)) {
			foreach ($sameageres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		// 最新上架
		$newonlineres = $managesysobj->getNewOnlineList($babyagetype, 1, 9);
		if (!empty($newonlineres)) {
			foreach ($newonlineres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		$albumlist = array();
		if (!empty($albumids)) {
			$albumids = array_unique($albumids);
			// 专辑信息
			$albumobj = new Album();
			$albumlist = $albumobj->getListByIds($albumids);
			// 专辑收听数
			$albumlistennum = $listenobj->getAlbumListenNum($albumids);
		}
		
		
		$hotrecommendlist = array();
		$sameagealbumlist = array();
		$newalbumlist = array();
		if (!empty($hotrecommendres)) {
			foreach ($hotrecommendres as $value) {
				$albumid = $value['albumid'];
				if (!empty($albumlist[$albumid])) {
				    $albuminfo = $albumlist[$albumid];
				    if (!empty($albuminfo['cover'])) {
				        $albuminfo['cover'] = $aliossobj->getImageUrlNg($albuminfo['cover'], 200);
				    }
				    $albuminfo['listennum'] = 0;
				    if (!empty($albumlistennum[$albumid])) {
				        $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
				    }
					$hotrecommendlist[] = $albuminfo;
				}
			}
		}
		if (!empty($sameageres)) {
			foreach ($sameageres as $value) {
				$albumid = $value['albumid'];
				if (!empty($albumlist[$albumid])) {
				    $albuminfo = $albumlist[$albumid];
				    if (!empty($albuminfo['cover'])) {
				        $albuminfo['cover'] = $aliossobj->getImageUrlNg($albuminfo['cover'], 200);
				    }
				    $albuminfo['listennum'] = 0;
				    if (!empty($albumlistennum[$albumid])) {
				        $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
				    }
					$sameagealbumlist[] = $albuminfo;
				}
			}
		}
		if (!empty($newonlineres)) {
			foreach ($newonlineres as $value) {
				$albumid = $value['albumid'];
				if (!empty($albumlist[$albumid])) {
				    $albuminfo = $albumlist[$albumid];
				    if (!empty($albuminfo['cover'])) {
				        $albuminfo['cover'] = $aliossobj->getImageUrlNg($albuminfo['cover'], 200);
				    }
				    $albuminfo['listennum'] = 0;
				    if (!empty($albumlistennum[$albumid])) {
				        $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
				    }
					$newalbumlist[] = $albuminfo;
				}
			}
		}
		
		// 推广位
		$focuspiclist = array();
		$focusres = $managesysobj->getFocusList(6);
		if (!empty($focusres)) {
		    $aliossobj = new AliOss();
		    foreach ($focusres as $value) {
		        $focusinfo['cover'] = $aliossobj->getFocusUrl($value['picid'], 1);
		        $focusinfo['linktype'] = $value['linktype'];
		        $focusinfo['linkurl'] = $value['linkurl'];
		        $focuspiclist[] = $focusinfo;
		    }
		}
		
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