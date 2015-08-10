# -*- coding: utf-8 -*-

import aliyun.api

aliyun.setDefaultAppInfo("<accessKeyId>", "<accessKeySecret>")

#开通Cdn服务
a = aliyun.api.Cdn20141111OpenCdnServiceRequest()
a.InternetChargeType = "PayByTraffic" # or PayByBandwidth
 
try:
	f = a.getResponse();
    if("Code" in f):
        print("失败")
        print(f["Code"])
        print(f["Message"]) 
    else:
        print("成功")
        print(f)
except Exception,e:
    print(e)     


#刷新缓存
a = aliyun.api.Cdn20141111RefreshObjectCachesRequest()

a.ObjectType = "File"; # or Directory
a.ObjectPath = "www.yourdomain.com/path/filename.ext"

try:
    f = a.getResponse()
    if("Code" in f):
        print("失败")
        print(f["Code"])
        print(f["Message"]) 
    else:
        print("成功")
        print(f)      
except Exception,e:
    print(e)
    