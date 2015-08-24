<?php

include_once '../controller.php';
class upload_oss extends controller
{
    public function action() {
        // // 更新专辑封面
        $album = new Album();
        // $album_list = $album->get_list("cover=''", 100);
        // foreach ($album_list as $k => $v) {
        //     $r = $this->middle_upload($v['s_cover'], $v['id'], 1);
        //     if (is_string($r)) {
        //         $album->update(array('cover' = $r), "`id`={$v['id']}");
        //     }
        // }
        // // 更新故事封面
        // $story = new Story();
        // $story_list = $album->get_list("cover=''", 100);
        // foreach ($story_list as $k => $v) {
        //     $r = $this->middle_upload($v['s_cover'], $v['id'], 2);
        //     if (is_string($r)) {
        //         $album->update(array('cover' = $r), "`id`={$v['id']}");
        //     }
        // }
        // // 更新故事为本地地址
        $story = new Story();
        $story_list = $story->get_list("audio_url=''", 1);
        foreach ($story_list as $k => $v) {
            $r = $this->middle_upload($v['source_audio_url'], $v['id'], 3);
            var_dump($r);exit;
            if (is_string($r)) {
                $story->update(array('cover' => $r), "`id`={$v['id']}");
            }
        }
    }

    /**
     * 功能：php完美实现下载远程图片保存到本地
     * 将本地文件上传到oss,删除本地文件
     * type 1 专辑封面 2 故事封面 3 故事音频
     */
    private function middle_upload($url = '', $id = '', $type = ''){

        if (!$url || !$id || !$type) {
            return false;
        }

        $uploadobj = new Upload();
        $aliossobj = new AliOss();

        if ($type == 3) {
            $savedir = $uploadobj->getMediaTmpDir();
        } else {
            $savedir = $uploadobj->getAlbumImageTmpDir();
        }

        $savedir = $savedir.date("Y_m_d_{$type}_{$id}");

        $ext = strtolower(strrchr($url,'.'));

        if(!in_array($ext, array('.gif', '.jpg', '.jpeg', '.mp3', '.audio'))){
            return false;
        }

        $filename = $savedir.$ext;

        $file = Http::download($url, $filename);

        if ($type == 3) {
            $res = $uploadobj->uploadStoryMedia($file, "media", $id);
            return $res;
        } else {
            $res = $uploadobj->uploadAlbumImage($file, "content", $id);
            $dest_url  = $aliossobj->getImageUrlNg($file);
        }

        return $dest_url;

    }
}
new upload_oss();