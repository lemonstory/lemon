<?php
include SERVER_ROOT.'libs/getid3/getid3.php';

class AliOss extends ModelBase
{
    public $OSS_BUCKET_IMAGE = 'lemonpic';
    public $OSS_BUCKET_AVATAR = 'lemonavatar';
    public $OSS_BUCKET_MEDIA = 'lemonmedia';
    public $OSS_BUCKET_IMAGE_DOMAIN = array(
    	//'http://p.xiaoningmeng.me/',
    	//'http://p.xiaoningmeng.me/',
        'http://lemonpic.oss-cn-hangzhou.aliyuncs.com/',
        'http://lemonpic.oss-cn-hangzhou.aliyuncs.com/',
    );
    public $OSS_BUCKET_AVATAR_DOMAIN = array(
    	'http://a.xiaoningmeng.me/avatar/',
    	'http://a.xiaoningmeng.me/avatar/',
    );
    public $OSS_BUCKET_MEDIA_DOMAIN = array(
        //'http://mf.xiaoningmeng.me/',
        //'http://mf.xiaoningmeng.me/',
        'http://lemonmedia.oss-cn-hangzhou.aliyuncs.com/',
        'http://lemonmedia.oss-cn-hangzhou.aliyuncs.com/'
    );
    
    public $OSS_IMAGE_ENABLE = array('jpg','jpeg','png');
    public $OSS_MEDIA_ENABLE = array('mp4', 'mp3', 'audio');
    
    public $LOCAL_IMG_TMP_PATH = '/alidata1/tmppicfile/';
    public $LOCAL_MEDIA_TMP_PATH = '/alidata1/tmpmediafile/';
    
    
    /**
     * 上传头像图片
     * @param S $file        $_FILES['xxx']的值
     * @param I $uid
     * @return array
     */
    public function uploadAvatarImage($file, $uid)
    {
        if (empty($file)){
            $this->setError(ErrorConf::paramError());
            return "";
        }
        $obj = new alioss_sdk();
        $obj->set_debug_mode(FALSE);
        $bucket = $this->OSS_BUCKET_AVATAR;
        $tmpFile = $file['tmp_name'];
    
        $ext = array_search($file['type'], MimeTypes::$mime_types);
        if (!in_array($ext, $this->OSS_IMAGE_ENABLE)){
            $ext = "jpg";
        }
        $from = $this->LOCAL_IMG_TMP_PATH . $uid . '.' . $ext;
        move_uploaded_file($tmpFile, $from);
         
        $to = $uid;
        $responseObj = $obj->upload_file_by_file($bucket,$uid,$from);
        if ($responseObj->status==200){
            $path = $to;
            return $path;
        } else {
            $this->setError(ErrorConf::uploadImgfileFail());
            return "";
        }
    }
    
    
    /**
     * 上传焦点图图片
     * @param S $file        $_FILES['xxx']的值
     * @param I $focuspicid
     * @return array
     */
    public function uploadFocusImage($file, $focuspicid)
    {
        if (empty($file)){
            $this->setError(ErrorConf::paramError());
            return "";
        }
        $obj = new alioss_sdk();
        $obj->set_debug_mode(FALSE);
        $bucket = $this->OSS_BUCKET_IMAGE;
        $tmpFile = $file['tmp_name'];
    
        $ext = array_search($file['type'], MimeTypes::$mime_types);
        if (!in_array($ext, $this->OSS_IMAGE_ENABLE)){
            $ext = "jpg";
        }
        $from = $this->LOCAL_IMG_TMP_PATH . $focuspicid . '.' . $ext;
        move_uploaded_file($tmpFile, $from);
         
        $to = "focus/" . $focuspicid;
        $responseObj = $obj->upload_file_by_file($bucket,$to,$from);
        if ($responseObj->status==200){
            $path = $to;
            return $path;
        } else {
            $this->setError(ErrorConf::uploadImgfileFail());
            return "";
        }
    }
    
    
    /**
     * 上传抓取专辑图片
     * @param S $tmpfilename    临时目录文件名，如111
     * @param S $tmpfiletype    临时目录文件后缀，如png
     * @param S $relationid
     * @return array()
     */
    public function uploadPicImage($tmpfilename, $tmpfiletype, $relationid)
    {
        if (empty($tmpfilename) || empty($tmpfiletype) || empty($relationid)){
            $this->setError(ErrorConf::paramError());
            return array();
        }
        $tmpFile = $this->LOCAL_IMG_TMP_PATH . "/" . $tmpfilename . "." . $tmpfiletype;
        if (!file_exists($tmpFile)) {
            $this->setError(ErrorConf::noUploadTmpfile());
            return array();
        }
        
        $obj = new alioss_sdk();
        $obj->set_debug_mode(FALSE);
        $bucket = $this->OSS_BUCKET_IMAGE;
        
	    $ext = array_search($tmpfiletype, MimeTypes::$mime_types);
	    if (!in_array($ext, $this->OSS_IMAGE_ENABLE)){
	    	$ext = "jpg";
	    }
	    
	    $to = $this->formatImageFile($relationid, $ext);
    	$responseObj = $obj->upload_file_by_file($bucket, $to, $tmpFile);
    	if ($responseObj->status==200){
    	    list($width, $height, $type, $attr) = getimagesize($tmpFile);
    	    $return['path'] = $to;
    	    $return['width'] = $width;
    	    $return['height'] = $height;
    	    return $return;
    	} else {
    	    $this->setError(ErrorConf::uploadImgfileFail());
    	    return array();
    	}
    }
    
    
    /**
     * 上传抓取音频
     * @param S $tmpfilename    临时目录文件名，如111
     * @param S $tmpfiletype    临时目录文件后缀，如mp3
     * @param S $relationid
     * @return array()
     */
    public function uploadMedia($tmpfilename, $tmpfiletype, $relationid)
    {
        if (empty($tmpfilename) || empty($tmpfiletype) || empty($relationid)){
            $this->setError(ErrorConf::paramError());
            return array();
        }
        $tmpFile = $this->LOCAL_MEDIA_TMP_PATH . "/" . $tmpfilename. "." . $tmpfiletype;
        if (!file_exists($tmpFile)) {
            $this->setError(ErrorConf::noUploadTmpfile());
            return array();
        }
        
        $obj = new alioss_sdk();
        $obj->set_debug_mode(false);
        $bucket = $this->OSS_BUCKET_MEDIA;
        
        $getID3 = new getID3;
        $id3Info = $getID3->analyze($tmpFile);
        
        $ext = $id3Info['fileformat'];
        if (!in_array($ext, $this->OSS_MEDIA_ENABLE)){
            $this->setError(ErrorConf::uploadMediaInvalidateType());
        }
        $times = ceil(@$id3Info['playtime_seconds']+0);
        $width = @$id3Info['video']['resolution_x']+0;
        $height = @$id3Info['video']['resolution_y']+0;
        $size = @$id3Info['filesize'];
        
        $to = $this->formatVideoFile($relationid, $ext);
        $responseObj = $obj->upload_file_by_file($bucket, $to, $tmpFile);
        if ($responseObj->status==200){
            $return['mediapath'] = $to;
            $return['width'] = $width;
            $return['height'] = $height;
            $return['size'] = $size;
            $return['times'] = $times;
            return $return;
        } else {
            $this->setError(ErrorConf::uploadMediafileFail());
            return array();
        }
    }
    
    public function getImageUrlNg($file, $style='')
    {
        if (strstr($file, "http")) {
            // @huqq 临时使用，允许加载外部域名的图片
            return $file;
        }
        $domains = $this->OSS_BUCKET_IMAGE_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = abs(crc32($file)%$domainsCount);
        $domain = $domains[$domainIndex];
        return $domain.trim($file, '/').$style;
    }
    public function getFocusUrl($focuspicid)
    {
        $domains = $this->OSS_BUCKET_IMAGE_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = abs(crc32($file)%$domainsCount);
        $domain = $domains[$domainIndex];
        return $domain . "focus/" . $focuspicid;
    }
    
    public function getAvatarUrl($uid, $avatartime, $size='')
    {
        $domains = $this->OSS_BUCKET_AVATAR_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = $uid%$domainsCount;
        $domain = $domains[$domainIndex];
        $size = empty($size) ? '' : "/{$size}";
        return "{$domain}{$uid}/{$avatartime}{$size}";
    }
    
    public function getMediaUrl($file)
    {
        $domains = $this->OSS_BUCKET_MEDIA_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = abs(crc32($file)%$domainsCount);
        $domain = $domains[$domainIndex];
        return $domain.trim($file, '/');
    }
    
    
    /**
     * delete_object
     * delete_objects
     *  
     * @param unknown_type $file
     */
    public function deleteImageOss($object)
    {
        $obj = new alioss_sdk();
    	$bucket = $this->OSS_BUCKET_IMAGE;
    	$response = $obj->delete_object($bucket,$object);
    	if ($response->status==204){
//     	    $cdnObj = new AliCdn();
//     	    $cdnObj->clearFileCache($this->getImageUrl($object));
    	    return true;
    	}
    	return false;
    }
    
    // 同bucket复制
    public function copyImageOss($from, $to)
    {
        $obj = new alioss_sdk();
        $bucket = $this->OSS_BUCKET_IMAGE;
        $response = $obj->copy_object($bucket, $from, $bucket, $to);
        if ($response->status==200){
            return true;
        }
        return false;
    }
    
    public function moveImageOss($from, $to)
    {
        if ($this->copyImageOss($from, $to)){
            return $this->deleteImageOss($from);
        }
        return false;
    }
    
    public function copyAvatarOss($from, $to)
    {
        $obj = new alioss_sdk();
        $bucket = $this->OSS_BUCKET_AVATAR;
        $response = $obj->copy_object($bucket, $from, $bucket, $to);
        if ($response->status==200){
            return true;
        }
        return false;
    }
    
    public function moveAvatarOss($from, $to)
    {
        if ($this->copyAvatarOss($from, $to)){
            return $this->deleteAvatarOss($from);
        }
        return false;
    }
    
    public function deleteAvatarOss($object)
    {
        $obj = new alioss_sdk();
    	$bucket = $this->OSS_BUCKET_AVATAR;
    	$response = $obj->delete_object($bucket,$object);
    	if ($response->status==204){
    	    return true;
    	}
    	return false;
    }
    
    public function formatImageFile($relationid, $ext)
    {
        return date("Y/m/d/").md5($relationid).".".$ext;
    }
    
    public function formatVideoFile($relationid, $ext)
    {
        return date("Y/m/d/").md5($relationid).".".$ext;
    }
}