<?php

include_once '../controller.php';
class upload_oss extends controller
{
    public function action() {
        $album = new Album();
        $album_list = $album->get_list("cover=''", 1);
        // var_dump($album_list);
        foreach ($album_list as $k => $v) {
            $r = $this->middle_upload($v['s_cover'], $v['id'], 1);
        }
        var_dump($r);
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

        if ($type == 3) {
            $savedir = $uploadobj->getMediaTmpDir();
        } else {
            $savedir = $uploadobj->getAlbumImageTmpDir();
        }

        $save_dir = tempnam($savedir, "attachment_{$type}_{$id}");

        $ext = strtolower(strrchr($url,'.'));

        if(!in_array($ext, array('.gif', '.jpg', '.jpeg', '.mp3', '.audio'))){
            return false;
        }

        $filename = $save_dir.$ext;

        var_dump($savedir);
        $file = Http::download($url, $savedir);

        if ($type == 3) {
            $res = $uploadobj->uploadStoryMedia($file, "media");
        } else {
            $res = $uploadobj->uploadAlbumImage($file, "content");
        }

        return $res;

    }
}
new upload_oss();