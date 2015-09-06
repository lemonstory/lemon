<?php
class Upload extends ModelBase
{
	public function getMediaTmpDir()
	{
		$aliossobj = new AliOss();
		return $aliossobj->LOCAL_IMG_TMP_PATH;
	}
	public function getAlbumImageTmpDir()
	{
		$aliossobj = new AliOss();
		return $aliossobj->LOCAL_IMG_TMP_PATH;
	}
	
    /**
     * 上传临时目录下的封面图片，临时目录：/alidata1/tmppicfile/
     * @param S $tmpfilename    临时目录存储的图片文件名，如111
     * @param S $tmpfiletype    文件格式，如png
     * @param I $albumid
     * @return array            图片文件的信息
     */
    public function uploadAlbumImage($tmpfilename, $tmpfiletype, $albumid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImage($tmpfilename, $tmpfiletype, $albumid);
    }
    
    
    /**
     * 上传临时目录下的音频文件， 临时目录：/alidata1/tmpmediafile/
     * @param string $tmpfilepath    临时目录,存储的音频文件名，如222
     * @param S $tmpfiletype         文件格式，如mp3
     * @param string $storyid
     * @return array 音频文件的信息
     */
    public function uploadStoryMedia($tmpfilename, $tmpfiletype, $storyid)
    {
        $ossObj = new AliOss();
        $mediainfo = $ossObj->uploadMedia($tmpfilename, $tmpfiletype, $storyid);
        return $mediainfo;
    }
    
    
    public function uploadAvatarImage($file, $uid)
    {
    	$ossObj = new AliOss();
    	return $ossObj->uploadAvatarImage($file, $uid);
    }
    
	public function uploadFocusImage($file, $focusid)
    {
    	$ossObj = new AliOss();
    	$filename = "focus_" . $focusid;
    	return $ossObj->uploadAvatarImage($file, $focusid);
    } 
}