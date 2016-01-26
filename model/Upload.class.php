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
     * 可用于上传专辑的封面
     * 上传临时目录下的封面图片，临时目录：/alidata1/tmppicfile/
     * @param S $tmpfilename    临时目录存储的图片文件名，如111
     * @param S $tmpfiletype    文件格式，如png
     * @param I $albumid
     * @return array            图片文件的信息
     */
    public function uploadAlbumImage($tmpfilename, $tmpfiletype, $albumid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImage($ossObj->IMAGE_TYPE_ALBUM, $tmpfilename, $tmpfiletype, $albumid);
    }
    
    
    /**
     * 可用于上传故事的封面
     * 上传临时目录下的封面图片，临时目录：/alidata1/tmppicfile/
     * @param S $tmpfilename    临时目录存储的图片文件名，如111
     * @param S $tmpfiletype    文件格式，如png
     * @param I $storyid
     * @return array            图片文件的信息
     */
    public function uploadStoryImage($tmpfilename, $tmpfiletype, $storyid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImage($ossObj->IMAGE_TYPE_STORY, $tmpfilename, $tmpfiletype, $storyid);
    }
    
    
    /**
     * 可用于上传分类的封面
     * 上传临时目录下的封面图片，临时目录：/alidata1/tmppicfile/
     * @param S $tmpfilename    临时目录存储的图片文件名，如111
     * @param S $tmpfiletype    文件格式，如png
     * @param I $categoryid
     * @return array            图片文件的信息
     */
    public function uploadCategoryImage($tmpfilename, $tmpfiletype, $categoryid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImage($ossObj->IMAGE_TYPE_CATEGORY, $tmpfilename, $tmpfiletype, $categoryid);
    }
    
    
    /**
     * 上传临时目录下的音频文件， 临时目录：/alidata1/tmpmediafile/
     * @param S $tmpfilepath    临时目录,存储的音频文件名，如222
     * @param S $tmpfiletype         文件格式，如mp3
     * @param S $storyid
     * @return array 音频文件的信息
     */
    public function uploadStoryMedia($tmpfilename, $tmpfiletype, $storyid)
    {
        $ossObj = new AliOss();
        $mediainfo = $ossObj->uploadMedia($tmpfilename, $tmpfiletype, $storyid);
        return $mediainfo;
    }
    
    
    /**
     * Post上传用户头像图片
     * @param A $file    如：$_FILES['avatarfile']
     * @param I $uid
     * @return S         图片的oss文件目录及文件名称
     */
    public function uploadAvatarImageByPost($file, $uid)
    {
    	$ossObj = new AliOss();
    	return $ossObj->uploadAvatarImageByFiles($file, $uid);
    }
    
	/**
	 * Post上传焦点图
	 * @param A $file
	 * @param I $focusid        焦点图id
	 * @return S                图片的oss文件目录及文件名称
	 */
	public function uploadFocusImageByPost($file, $focusid)
    {
    	$ossObj = new AliOss();
    	return $ossObj->uploadFocusImageByFiles($file, $focusid);
    }
    
    /**
     * Post上传专辑封面
     * @param A $file
     * @param I $albumid
     */
    public function uploadAlbumImageByPost($file, $albumid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImageByFiles($ossObj->IMAGE_TYPE_ALBUM, $file, $albumid);
    }
    
    /**
     * Post上传故事封面
     * @param A $file
     * @param I $storyid
     */
    public function uploadStoryImageByPost($file, $storyid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImageByFiles($ossObj->IMAGE_TYPE_STORY, $file, $storyid);
    }
    
    /**
     * Post上传分类封面
     * @param A $file
     * @param I $categoryid
     */
    public function uploadCategoryImageByPost($file, $categoryid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImageByFiles($ossObj->IMAGE_TYPE_CATEGORY, $file, $categoryid);
    }
    
    /**
     * Post上传标签封面
     * @param A $file
     * @param I $tagid
     */
    public function uploadTagImageByPost($file, $tagid)
    {
        $ossObj = new AliOss();
        return $ossObj->uploadPicImageByFiles($ossObj->IMAGE_TYPE_TAG, $file, $tagid);
    }
}