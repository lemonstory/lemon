<?php
//die();

//目前小于1.3版本的推荐数据和1.3版本的推荐数据是两套存储
//修复将小于v1.3的推荐数据,同步至v1.3中.
//业务逻辑:
//      遍历version<1.3的所有推荐专辑,查找该专辑所属的一级标签,更新该专辑在album_tag_relation里面的
//      tagid,isrecommend,recommendstatus,uptime,addtime
//备注:
//      目前线上只修复了热门推荐
//使用:
//
//  php your_path/cron_copyRecommendHotToAlbumTagRelation.php

include_once (dirname ( dirname ( __FILE__ ) ) . "/DaemonBase.php");
class cron_copyRecommendHotToAlbumTagRelation extends DaemonBase {

    protected $isWhile = false;

	protected function deal() {

        $logfile = "/alidata1/cron_copyRecommendHotToAlbumTagRelation.log";
        $fp = @fopen($logfile, "a+");
        $repair_num = 0;
        $not_required_num = 0;
        $count = 0;

        //数据量较小未做分批处理
        $hot_recommend_list = array();
        $same_age_recommend_list = array();
        $new_online_recommend_list = array();
        $all_recommend_list = array();

        $db = DbConnecter::connectMysql("share_main");

        //热门推荐
	    $selectsql = "SELECT * FROM `recommend_hot`";
	    $selectst = $db->prepare($selectsql);
	    $selectst->execute();
        $hot_recommend_list = $selectst->fetchAll(PDO::FETCH_ASSOC);

//        $selectsql = "SELECT * FROM `recommend_same_age`";
//        $selectst = $db->prepare($selectsql);
//        $selectst->execute();
//        $same_age_recommend_list = $selectst->fetchAll(PDO::FETCH_ASSOC);
//
//        $selectsql = "SELECT * FROM `recommend_new_online`";
//        $selectst = $db->prepare($selectsql);
//        $selectst->execute();
//        $new_online_recommend_list = $selectst->fetchAll(PDO::FETCH_ASSOC);

        foreach ($hot_recommend_list as $k => $item) {

            $albumid = $item['albumid'];
            $ordernum = $item['ordernum'];
            $status = $item['status'];
            $addtime = $item['addtime'];
            $all_recommend_list[$albumid] = $item;
        }

//        foreach($same_age_recommend_list as $k => $item) {
//
//            $albumid = $item['albumid'];
//            $agetype = $item['agetype'];
//            $ordernum = $item['ordernum'];
//            $status = $item['status'];
//            $addtime = $item['addtime'];
//
//            $all_recommend_list[$albumid] = array(
//
//                'albumid' => $albumid,
//                'ordernum' => $ordernum,
//                'status'   => $status,
//                'addtime'  => $addtime,
//            );
//        }
//
//        foreach($new_online_recommend_list as $k => $item) {
//
//            $albumid = $item['albumid'];
//            $agetype = $item['agetype'];
//            $ordernum = $item['ordernum'];
//            $status = $item['status'];
//            $addtime = $item['addtime'];
//
//            $all_recommend_list[$albumid] = array(
//
//                'albumid' => $albumid,
//                'ordernum' => $ordernum,
//                'status'   => $status,
//                'addtime'  => $addtime,
//            );
//        }


        if (!empty($all_recommend_list))

            $tag_new_obj = new TagNew();

        foreach ($all_recommend_list as $albumid => $item) {

            $count++;
            $album_tag_relation_list = $tag_new_obj->getAlbumTagRelationListByAlbumIds($albumid);
            $isrecommend = false;
            foreach ($album_tag_relation_list as $k => $r_item) {

                foreach ($r_item as $tagid => $relation_item) {

                    $tag_id = $relation_item['tagid'];
                    $tag_info_list = $tag_new_obj->getTagInfoByIds($tag_id);
                    $tag_info = array();
                    if (!empty($tag_info_list[$tag_id])) {

                        $tag_info = $tag_info_list[$tag_id];
                        $pid = $tag_info['pid'];

                        if (0 == $pid && 0 == $r_item['isrecommend']) {

                            //修改该故事辑为推荐状态
                            $ret = false;
                            $isrecommend = true;
                            $update_data = array();
                            $update_data['isrecommend'] = 1;
                            $update_data['recommendstatus'] = $item['status'];
                            $update_data['uptime'] = date("Y-m-d H:i:s", time());;
                            $update_data['addtime'] = $item['addtime'];
                            $ret = $tag_new_obj->updateAlbumTagRelationInfo($albumid, $tag_id, $update_data);

                            $repair_num++;
                            fwrite($fp, "repearAlbumid: albumid: {$albumid} tagId: {$tag_id} ret: {$ret}\n");
                            break;
                        }
                    }
                }
            }

            if (!$isrecommend) {

                $not_required_num++;
                fwrite($fp, "doNothing: albumid: {$albumid} tagId: {$tag_id} \n");
            }
        }

        fwrite($fp, "Done! count:{$count}, repairNum:{$repair_num},  notRequiredNum:{$not_required_num}\n");
        fclose($fp);
        $db = null;
    }

	protected function checkLogPath() {}

}
new cron_copyRecommendHotToAlbumTagRelation ();