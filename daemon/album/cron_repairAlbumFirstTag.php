<?php
/**
 * Created by PhpStorm.
 * User: gaoyong
 * Date: 16/2/1
 * Time: 下午4:28
 */

//目前有的故事辑只有二级标签,没有一级标签
//修复故事辑的一级标签.
//业务逻辑:
//      查找(指定)一级标签下的所有二级标签的故事辑
//      如果该故事辑有一级标签,则不做任何处理,如果没有一级标签则添加对应的一级标签
//使用:
//  修复tagid为2的所有专辑:
//  php your_path/cron_repairAlbumFirstTag.php -t 2


include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");

class cron_repairAlbumFirstTag extends DaemonBase {

    protected $isWhile = false;

    protected function deal() {


        $options = getopt("t:");
        $repairFirstTag = 0;
        if(!empty($options)) {

            $repairFirstTag = $options['t'];
        }

        if(empty($repairFirstTag)) {

            die("Fail: tag id is empty");
        }

        $tagNewObj = new TagNew();
        $ids = $tagNewObj->getFirstTagIds(8);
        $logfile = "/alidata1/cron_repairAlbumFirstTag.log";
        $fp = @fopen($logfile, "a+");
        $repairNum = 0;
        $notRequiredNum = 0;
        $count = 0;

        if(!in_array($repairFirstTag,$ids)) {

            die();

        } else {

            //TODO:最多只能取出20个标签
            $tagIds = $tagNewObj->getSecondTagIds($repairFirstTag);
            if(!empty($tagIds)) {

                $startRelationId = 0;
                $direction = "down";
                $section = 2; //每次取1000条,分批处理
                $albumTagRelationListCount = 0;
                $isFirstLoop = true;
                $ret = false;

                while($isFirstLoop || $albumTagRelationListCount > 0 ) {


                    $isFirstLoop = false;
                    $albumTagRelationList = $tagNewObj->getAlbumTagRelationListFromTag($tagIds,0,0,0,$direction,$startRelationId,$section);
                    $albumTagRelationListCount = count($albumTagRelationList);
                    $lastIndex = $albumTagRelationListCount - 1 ;
                    $startRelationId = $albumTagRelationList[$lastIndex]['id'];
                    if(!empty($albumTagRelationList) && is_array($albumTagRelationList)) {

                        $albumids = array();
                        foreach($albumTagRelationList as $item) {
                            $albumids[] = $item['albumid'];
                        }

                        $albumids = array_unique($albumids);
                        foreach($albumids as $albumid) {

                            if(!empty($albumid)) {

                                $count++;
                                $tagRelationListInAlbumIds = $tagNewObj->getAlbumTagRelationListByAlbumIds($albumid);
                                if(!empty($tagRelationListInAlbumIds)) {
                                    $tagRelationListInAlbumId =  $tagRelationListInAlbumIds[$albumid];
                                    $tagIdsInAlbumId = array();
                                    if(!empty($tagRelationListInAlbumId)) {

                                        foreach($tagRelationListInAlbumId as $item) {

                                            $tagIdsInAlbumId[] = $item['tagid'];
                                        }

                                        $tagIdsInAlbumIdStr = implode(",",$tagIdsInAlbumId);
                                        if(!in_array($repairFirstTag,$tagIdsInAlbumId)) {

                                            $repairNum++;
                                            $ret = $tagNewObj->addAlbumTagRelationInfo($albumid,$repairFirstTag);
                                            $tagNewObj->clearAlbumTagRelationCacheByAlbumIds($albumid);

                                            fwrite($fp, "repearAlbumid: {$albumid} tagIdsInAlbumId: {$tagIdsInAlbumIdStr} repairFirestTagTag: {$repairFirestTag}  ret: {$ret}\n");

                                        } else {
                                            //do nothing
                                            $notRequiredNum++;
                                            fwrite($fp, "doNothing: {$albumid} tagIdsInAlbumId: {$tagIdsInAlbumIdStr} repairFirestTagTag: {$repairFirestTag}  ret: {$ret}\n");
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {

                fwrite($fp, "repairFirstTag: {$repairFirstTag} secondTagIds is empty\n");
            }
        }

        fwrite($fp,"Done! count:{$count}, repairNum:{$repairNum},  notRequiredNum:{$notRequiredNum}\n");
        fclose($fp);
    }

    protected function checkLogPath() {}

}
new cron_repairAlbumFirstTag ();