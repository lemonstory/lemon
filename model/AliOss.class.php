<?php
//include SERVER_ROOT.'libs/getid3/getid3.php';

class AliOss extends ModelBase
{
    public $OSS_BUCKET_IMAGE = 'lemonpic';
    public $OSS_BUCKET_AVATAR = 'lemonavatar';
    public $OSS_BUCKET_MEDIA = 'lemonmedia';
    public $OSS_BUCKET_IMAGE_DOMAIN = array(
    	'http://p.xiaoningmeng.net/',
    	'http://p.xiaoningmeng.net/',
    );
    public $OSS_BUCKET_AVATAR_DOMAIN = array(
    	'http://a.xiaoningmeng.net/avatar/',
    	'http://a.xiaoningmeng.net/avatar/',
    );
    public $OSS_BUCKET_MEDIA_DOMAIN = array(
        'http://mf.xiaoningmeng.net/',
        'http://mf.xiaoningmeng.net/',
    );
    
    public $OSS_IMAGE_ENABLE = array('jpg','jpeg','png');
    public $OSS_MEDIA_ENABLE = array('mp4', 'mp3', 'audio');
    
    public $LOCAL_IMG_TMP_PATH = '/alidata1/tmppicfile/';
    public $LOCAL_MEDIA_TMP_PATH = '/alidata1/tmpmediafile/';
    
    public $OSS_IMAGE_THUMB_SIZE_LIST = array(100, 200, 230, 460); // lemonpic图片缩略尺寸
    
    public $IMAGE_TYPE_ALBUM = 'album';
    public $IMAGE_TYPE_STORY = 'story';
    public $IMAGE_TYPE_CATEGORY = 'category';
    
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
     * @param I $focusid
     * @return array
     */
    public function uploadFocusImage($file, $focusid)
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
        $from = $this->LOCAL_IMG_TMP_PATH . $focusid . '.' . $ext;
        move_uploaded_file($tmpFile, $from);
         
        $to = "focus/" . $focusid . ".png";
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
     * 通过$_FILES方式上传pic图片到OSS
     * @param S $imagetype   图片类型：album/story/category
     * @param S $file        $_FILES['xxx']的值
     * @param I $relationid
     */ 
    public function uploadPicImageByFiles($imagetype, $file, $relationid)
    {
        if (empty($imagetype) || empty($file) || empty($relationid)){
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
        $from = $this->LOCAL_IMG_TMP_PATH . $relationid . '.' . $ext;
        move_uploaded_file($tmpFile, $from);
        
        $to = $this->formatImageFile($imagetype, $relationid, $ext);
        $responseObj = $obj->upload_file_by_file($bucket, $to, $from);
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
    public function uploadPicImage($imagetype, $tmpfilename, $tmpfiletype, $relationid)
    {
        if (empty($imagetype) || empty($tmpfilename) || empty($tmpfiletype) || empty($relationid)){
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
	    
	    $to = $this->formatImageFile($imagetype, $relationid, $ext);
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
        if (!in_array($tmpfiletype, $this->OSS_MEDIA_ENABLE)) {
            $this->setError(ErrorConf::uploadMediaInvalidateType());
            return array();
        }
        
        $obj = new alioss_sdk();
        $obj->set_debug_mode(false);
        $bucket = $this->OSS_BUCKET_MEDIA;
        
        /* $getID3 = new getID3;
        $id3Info = $getID3->analyze($tmpFile);
        if (empty($id3Info['fileformat'])) {
            // getid3 解析失败，将源文件上传
            if (!in_array($tmpfiletype, $this->OSS_MEDIA_ENABLE)) {
                $this->setError(ErrorConf::uploadMediaInvalidateType());
                return array();
            }
            $ext = $tmpfiletype;
            $times = 0;
            $size = 0;
        } else {
            // 解析成功，转换成MP3,再上传
            if (!in_array($id3Info['fileformat'], $this->OSS_MEDIA_ENABLE)) {
                $this->setError(ErrorConf::uploadMediaInvalidateType());
                return array();
            }
            $ext = $id3Info['fileformat'];
            $times = ceil(@$id3Info['playtime_seconds']+0);
            $size = @$id3Info['filesize'];
        } */
        
        $ext = $tmpfiletype;
        $times = 0;
        $size = 0;
        $command = "mediainfo \"--Inform=General;%Duration% %FileSize%\" {$tmpFile}";
        exec($command, $output);
        if (!empty($output)) {
            $mediainfo = explode(" ", $output[0]);
            if (!empty($mediainfo[0])) {
                $times = floor($mediainfo[0] / 1000) + 0;
            }
            if (!empty($mediainfo[1])) {
                $size = $mediainfo[1] + 0;
            }
        }
        
        $to = $this->formatVideoFile($relationid, $ext);
        $responseObj = $obj->upload_file_by_file($bucket, $to, $tmpFile);
        if ($responseObj->status==200){
            $return['mediapath'] = $to;
            $return['size'] = $size;
            $return['times'] = $times;
            return $return;
        } else {
            $this->setError(ErrorConf::uploadMediafileFail());
            return array();
        }
    }
    
    /**
     * 获取lemonpic图片url
     * @param S $imagetype      类型：album/story/category
     * @param S $file           文件路径和文件名：2015/11/01/xxx.jpg
     * @param S $size           缩略尺寸：如100
     * @param I $covertime      封面最新更新时间戳，用于更新cdn缓存
     * @return string           图片url
     */
    public function getImageUrlNg($imagetype, $file, $size = '', $covertime = 0)
    {
        if (strstr($file, "http")) {
            // @huqq 临时使用，允许加载外部域名的图片
            return $file;
        }
        $domains = $this->OSS_BUCKET_IMAGE_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = abs(crc32($file)%$domainsCount);
        $domain = $domains[$domainIndex];
        $file = $this->getImageFile($imagetype, $file);
        if ($size > 0) {
            if (!in_array($size, $this->OSS_IMAGE_THUMB_SIZE_LIST)) {
                $size = 100;
            }
            $fileurl = $domain . $file . "@!{$size}x{$size}";
        } else {
            $fileurl = $domain . trim($file, "/");
        }
        if (!empty($covertime)) {
            $fileurl .= "?v={$covertime}";
        }
        return $fileurl;
    }
    
    public function getFocusUrl($focusid, $covertime = 0, $isthumb = 1)
    {
        $domains = $this->OSS_BUCKET_IMAGE_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = abs(crc32($focusid)%$domainsCount);
        $domain = $domains[$domainIndex];
        if ($isthumb == 1) {
            $fileurl = $domain . "focus/{$focusid}.png@!640x260";
        } else {
            $fileurl = $domain . "focus/{$focusid}.png";
        }
        if (!empty($covertime)) {
            $fileurl .= "?v={$covertime}";
        }
        return $fileurl;
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
    /* public function deleteImageOss($object)
    {
        $obj = new alioss_sdk();
    	$bucket = $this->OSS_BUCKET_IMAGE;
    	$response = $obj->delete_object($bucket,$object);
    	if ($response->status==204){
    	    //$cdnObj = new AliCdn();
    	    //$cdnObj->clearFileCache($this->getImageUrl($object));
    	    return true;
    	}
    	return false;
    } */
    
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
    
    /* public function moveImageOss($from, $to)
    {
        if ($this->copyImageOss($from, $to)){
            return $this->deleteImageOss($from);
        }
        return false;
    } */
    
    /* public function copyAvatarOss($from, $to)
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
    } */
    
    private function getImageFile($imagetype, $file)
    {
        if (!in_array($imagetype, array($this->IMAGE_TYPE_ALBUM, $this->IMAGE_TYPE_STORY, $this->IMAGE_TYPE_CATEGORY))) {
            return false;
        }
        return $imagetype . "/" . $file;
    }
    
    /**
     * 图片pic的存储路径
     * @param S $imagetype      类型：专辑、故事、分类的图片
     * @param I $relationid     专辑ID/故事ID/分类ID
     * @param S $ext            后缀
     * @return string
     */
    private function formatImageFile($imagetype, $relationid, $ext)
    {
        if (empty($imagetype) || empty($relationid)) {
            return false;
        }
        if (!in_array($imagetype, array($this->IMAGE_TYPE_ALBUM, $this->IMAGE_TYPE_STORY, $this->IMAGE_TYPE_CATEGORY))) {
            return false;
        }
        
        return $imagetype . "/" . date("Y/m/d/") . md5($relationid) . "." . $ext;
    }
    
    private function formatVideoFile($relationid, $ext)
    {
        return date("Y/m/d/").md5($relationid).".".$ext;
    }
}
