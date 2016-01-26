<?php
include_once '../controller.php';
class index extends controller
{
	public function action() 
	{
		$uid = $this->getUid();
		$userinfo = array();
		$albumids = array();
		$recommendobj = new Recommend();
		$aliossobj = new AliOss();
		
		// 热门推荐
		$hotrecommendres = $recommendobj->getRecommendHotList(1, 9);
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
		$sameageres = $recommendobj->getSameAgeListenList($babyagetype, 1, 9);
		if (!empty($sameageres)) {
			foreach ($sameageres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		// 最新上架
		$newonlineres = $recommendobj->getNewOnlineList($babyagetype, 1, 9);
		if (!empty($newonlineres)) {
			foreach ($newonlineres as $value) {
				$albumids[] = $value['albumid'];
			}
		}
		
		$albumlist = array();
		$recommenddesclist = array();
		if (!empty($albumids)) {
			$albumids = array_unique($albumids);
			// 专辑信息
			$albumobj = new Album();
			$albumlist = $albumobj->getListByIds($albumids);
			// 专辑收听数
			$listenobj = new Listen();
			$albumlistennum = $listenobj->getAlbumListenNum($albumids);
			
			if ($_SERVER['visitorappversion'] >= "130000") {
			    // 获取推荐语
			    $recommenddescobj = new RecommendDesc();
			    $recommenddesclist = $recommenddescobj->getAlbumRecommendDescList($albumids);
			}
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
				        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
				    }
				    $albuminfo['listennum'] = 0;
				    if (!empty($albumlistennum[$albumid])) {
				        $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
				    }
				    $albuminfo['recommenddesc'] = "";
				    if (!empty($recommenddesclist[$albumid])) {
				        $albuminfo['recommenddesc'] = $recommenddesclist[$albumid]['desc'];
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
				        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
				    }
				    $albuminfo['listennum'] = 0;
				    if (!empty($albumlistennum[$albumid])) {
				        $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
				    }
				    $albuminfo['recommenddesc'] = "";
				    if (!empty($recommenddesclist[$albumid])) {
				        $albuminfo['recommenddesc'] = $recommenddesclist[$albumid]['desc'];
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
				        $albuminfo['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_ALBUM, $albuminfo['cover'], 460, $albuminfo['cover_time']);
				    }
				    $albuminfo['listennum'] = 0;
				    if (!empty($albumlistennum[$albumid])) {
				        $albuminfo['listennum'] = $albumlistennum[$albumid]['num']+0;
				    }
				    $albuminfo['recommenddesc'] = "";
				    if (!empty($recommenddesclist[$albumid])) {
				        $albuminfo['recommenddesc'] = $recommenddesclist[$albumid]['desc'];
				    }
					$newalbumlist[] = $albuminfo;
				}
			}
		}
		
		// 推广位
		$focuspiclist = array();
		$focusres = $recommendobj->getFocusList(6);
		if (!empty($focusres)) {
		    foreach ($focusres as $value) {
		        $focusinfo['cover'] = $aliossobj->getFocusUrl($value['id'], $value['covertime'], 1);
		        $focusinfo['linktype'] = $value['linktype'];
		        $focusinfo['linkurl'] = $value['linkurl'];
		        $focuspiclist[] = $focusinfo;
		    }
		}
		
		// 一级标签列表
		$firsttaglist = array();
		if ($_SERVER['visitorappversion'] >= "130000") {
    		$tagnewobj = new TagNew();
    		$firsttagres = $tagnewobj->getFirstTagList(8);
    		if (!empty($firsttagres)) {
    		    foreach ($firsttagres as $value) {
    		        if (!empty($value['cover'])) {
    		            $value['cover'] = $aliossobj->getImageUrlNg($aliossobj->IMAGE_TYPE_TAG, $value['cover'], 135, $value['covertime']);
    		        }
    		        $firsttaglist[] = $value;
    		    }
    		}
		}
		
		// 私人订制
		
		
		$data = array(
			"focuspic" => $focuspiclist,
			"hotrecommend" => $hotrecommendlist,
			"samgeage" => $sameagealbumlist,
			"newalbum" => $newalbumlist,
	        "firsttag" => $firsttaglist
		);
		$this->showSuccJson($data);
	}
}
new index();