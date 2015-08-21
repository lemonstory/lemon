<?php

include_once '../controller.php';
class upload_oss extends controller 
{
    public function action() {
        $album = new Album();
        $album_list = $album->get_list("cover=''", 1);
        foreach ($album_list as $k => $v) {
            $r = $this->middle_upload($v['s_cover'], $v['id']);
        }
        var_dump($r);
    }

    /**
     * 功能：php完美实现下载远程图片保存到本地 
     * 将本地文件上传到oss,删除本地文件
     */
    private function middle_upload($url, $storyid){  
        if(trim($url)==''){  
            return false;
        }  
        if(trim($save_dir)==''){  
            $save_dir= sys_get_temp_dir(tempnam('pic-', 'prefix'));  
        }  
        if(trim($filename)==''){//保存文件名  
            $ext = strtolower(strrchr($url,'.'));  
            if(in_array($ext, array('.gif', '.jpg', '.jpeg', '.mp3', '.audio'))){  
                return false;
            }  
            $filename = time().'_'.mt_rand(1, 1000).$ext;  
        }  
        if(0!==strrpos($save_dir,'/')){  
            $save_dir.='/';  
        }  
        //创建保存目录  
        if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){  
            return false;
        }  
        //获取远程文件
        $ch=curl_init();  
        $timeout=3;  
        curl_setopt($ch,CURLOPT_URL,$url);  
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);  
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
        $img=curl_exec($ch);     
        curl_close($ch);
        //文件大小   
        $fp2=@fopen($save_dir.$filename,'w');  
        fwrite($fp2,$img);  
        fclose($fp2);  
        unset($img,$url);  

        // 上传文件到服务器
        if (in_array($ext, array('.mp3', '.audio'))) {
            $mediafile   = $save_dir.$filename;;
            $uploadobj = new Upload();
            $uploadobj->uploadStoryMedia($mediafile);
            return $mediaurl;  
        } else {
            $file      = $save_dir.$filename;;
            $aliossobj = new AliOss();
            $imgurl    = $aliossobj->getImageUrlNg($file);
            return $imgurl;
        }
    }
}
new upload_oss();