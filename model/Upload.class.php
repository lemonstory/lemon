<?php
class Upload extends ModelBase
{
    /**
     * 上传专辑封面
     * @param I $albumid
     * @param S $fileobjname    图片文件名
     * @return array            图片文件的信息
     */
    public function uploadAlbumImage($albumid, $fileobjname='content')
    {
        $ossObj = new AliOss();
        $file = @$_FILES[$fileobjname];
        return $ossObj->uploadImage($file, $albumid);
    }
    
    
    /**
     * 上传故事音频内容
     * @param string $storyid
     * @param string $medianame    音频文件名
     * @return array 音频文件的信息
     */
    public function uploadStoryMedia($storyid, $medianame = "media")
    {
        $ossObj = new AliOss();
        $mediafile = @$_FILES[$medianame];
        $mediainfo = $ossObj->uploadMedia($mediafile, $storyid);
        return $mediainfo;
    }
}