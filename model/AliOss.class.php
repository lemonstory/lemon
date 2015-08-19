<?php
/**
 * 上传到alioss
 * @author wangzhitao
 *
 */
include SERVER_ROOT.'libs/getid3/getid3.php';

class AliOss extends ModelBase
{
    public $OSS_BUCKET_IMAGE = 'lemonpic';
    public $OSS_BUCKET_AVATAR = 'lemonavatar';
    public $OSS_BUCKET_MEDIA = 'lemonmedia';
    public $OSS_BUCKET_IMAGE_DOMAIN = array(
    	'http://p.lemon.com/',
    	'http://p.lemon.com/',
    );
    public $OSS_BUCKET_AVATAR_DOMAIN = array(
    	'http://a.lemon.com/avatar/',
    	'http://a.lemon.com/avatar/',
    );
    public $OSS_BUCKET_MEDIA_DOMAIN = array(
        'http://mf.lemon.com/',
        'http://mf.lemon.com/',
    );
    
    public $OSS_IMAGE_ENABLE = array('jpg','jpeg','png');
    public $OSS_MEDIA_ENABLE = array('mp4');
    
    public $LOCAL_IMG_TMP_PATH = '/alidata1/tmppicfile/';
    public $LOCAL_MEDIA_TMP_PATH = '/alidata1/tmpmediafile/';
    
    public function uploadImage($file, $relationid)
    {
        if (empty($file)){
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $obj = new alioss_sdk();
        $obj->set_debug_mode(FALSE);
        $bucket = $this->OSS_BUCKET_IMAGE;
        $tmpFile = $file['tmp_name'];
	    $ext = array_search($file['type'], MimeTypes::$mime_types);
	    if (!in_array($ext, $this->OSS_IMAGE_ENABLE)){
	    	$ext = "jpg";
	    }
	    $from = $this->LOCAL_IMG_TMP_PATH . $relationid . rand(1, 100) . '.' . $ext;
	    move_uploaded_file($tmpFile, $from);
	    
	    $to = $this->formatImageFile($relationid, $ext);
    	$responseObj = $obj->upload_file_by_file($bucket,$to,$from);
    	if ($responseObj->status==200){
    	    list($width, $height, $type, $attr) = getimagesize($from);
    	    $return['path'] = $to;
    	    $return['width'] = $width;
    	    $return['height'] = $height;
    	    return $return;
    	} else {
    	    $this->setError(ErrorConf::uploadImgfileFail());
    	    return false;
    	}
    }
    
    /*public function uploadYunyingMedia($file)
    {
        if (empty($file)){
            $this->setError(ErrorConf::yunyingMediaEmpty());
            return false;
        }
        $obj = new alioss_sdk();
        $obj->set_debug_mode(FALSE);
        $bucket = $this->OSS_BUCKET_IMAGE;
        $tmpFile = $file['tmp_name'];
        $ext = array_search($file['type'], MimeTypes::$mime_types);
        if (!in_array($ext, $this->OSS_IMAGE_ENABLE)){
            $ext = "jpg";
        }
        $filemd5 = md5_file($tmpFile);
        $from = "{$this->LOCAL_TMP_PATH}{$filemd5}.{$ext}";
        move_uploaded_file($tmpFile, $from);
        
        $to = "yunying/{$filemd5}.$ext";
        $responseObj = $obj->upload_file_by_file($bucket,$to,$from);
        if ($responseObj->status==200){
            list($width, $height, $type, $attr) = getimagesize($from);
            $return['path'] = $to;
            $return['width'] = $width;
            $return['height'] = $height;
            return $return;
        } else {
            $this->setError(ErrorConf::yunyingMediaError());
            return false;
        }
    }*/
    
    public function uploadMedia($file, $relationid)
    {
        if (empty($file)){
            $this->setError(ErrorConf::paramError());
            return false;
        }
        $obj = new alioss_sdk();
        $obj->set_debug_mode(false);
        $bucket = $this->OSS_BUCKET_MEDIA;
        $tmpFile = $file['tmp_name'];
        
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
                
        $from = $this->LOCAL_MEDIA_TMP_PATH . $relationid . rand(1, 100) . '.' . $ext;
        move_uploaded_file($tmpFile, $from);
        
        $to = $this->formatVideoFile($relationid, $ext);
        $responseObj = $obj->upload_file_by_file($bucket,$to,$from);
        if ($responseObj->status==200){
            $return['mediapath'] = $to;
            $return['width'] = $width;
            $return['height'] = $height;
            $return['size'] = $size;
            $return['times'] = $times;
            return $return;
        } else {
            $this->setError(ErrorConf::uploadMediafileFail());
            return false;
        }
    }
    
    public function getImageUrlNg($file, $style='')
    {
        $domains = $this->OSS_BUCKET_IMAGE_DOMAIN;
        $domainsCount = count($domains);
        $domainIndex = abs(crc32($file)%$domainsCount);
        $domain = $domains[$domainIndex];
        return $domain.trim($file, '/').$style;
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