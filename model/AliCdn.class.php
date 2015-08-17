<?php
/**
 * cdn api 调用封装累
 */
include_once SERVER_ROOT.'libs/alicdn/TopSdk.php';

class AliCdn extends ModelBase
{
    public function getClient()
    {
        $c = new AliyunClient;
        $c->accessKeyId = "xxx";
        $c->accessKeySecret = "xxx";
        $c->serverUrl="http://cdn.aliyuncs.com/";
        return $c;
    }
    
    public function clearFileCache($fileurl)
    {
        $c = $this->getClient();
        $req = new Cdn20141111RefreshObjectCachesRequest();
        $req->setObjectType("File"); // or Directory
        $req->setObjectPath($fileurl);
        try {
            $resp = $c->execute($req);
//             if(!isset($resp->Code))
//             {
//                 //刷新成功
//                 echo($resp->RequestId);
//                 print_r($resp);
//             }
//             else
//             {
//                 //刷新失败
//                 $code = $resp->Code;
//                 $message = $resp->Message;
//             }
        }
        catch (Exception $e)
        {
            // TODO: handle exception
        }
    }
}