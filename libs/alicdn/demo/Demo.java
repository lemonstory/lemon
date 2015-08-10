package com.aliyun.openapi.demo;

import com.aliyun.api.AliyunClient;
import com.aliyun.api.DefaultAliyunClient;

import com.aliyun.api.cdn.cdn20141111.request.OpenCdnServiceRequest;
import com.aliyun.api.cdn.cdn20141111.request.RefreshObjectCachesRequest;
import com.aliyun.api.cdn.cdn20141111.response.OpenCdnServiceResponse;
import com.aliyun.api.cdn.cdn20141111.response.RefreshObjectCachesResponse;
import com.taobao.api.ApiException;
import com.taobao.api.internal.util.StringUtils;

public class Demo {

    private static AliyunClient client;
    static {
        String serverUrl="<serverUrl>";//例如：http://cdn.aliyuncs.com/
        String accessKeyId="<accessKeyId>";
        String accessKeySecret="<accessKeySecret>";

        //// 初始化一个AliyunClient
        client = new DefaultAliyunClient(serverUrl, accessKeyId, accessKeySecret);
    }

    /**
     * 开通cdn服务
     */
    public void openCdnService() {
        OpenCdnServiceRequest request = new OpenCdnServiceRequest();
        request.setInternetChargeType(InternetChargeType.PayByTraffic.name());
        try {
            OpenCdnServiceResponse response = client.execute(request);
            if (StringUtils.isEmpty(response.getErrorCode())) {
                //开通成功
            }else {
                //开通失败
                String errorCode = response.getErrorCode();//取得错误码
                String message = response.getMessage();//取得错误信息
            }
        } catch (ApiException e) {
            // TODO: handle exception
        }
    }

    /**
     * 刷新文件缓存
     */
    public void refreshObjectCaches() {
        RefreshObjectCachesRequest request = new RefreshObjectCachesRequest();
        request.setObjectType(ObjectType.File.name());
        request.setObjectPath("www.yourdomain.com/path/filename.ext");
        try {
            RefreshObjectCachesResponse response = client.execute(request);
            if (StringUtils.isEmpty(response.getErrorCode())) {
                //刷新成功
            }else {
                //刷新失败
                String errorCode = response.getErrorCode();//取得错误码
                String message = response.getMessage();//取得错误信息
            }
        } catch (ApiException e) {
            // TODO: handle exception
        }
    }

    /**
     * cdn计费类型
     */
    enum InternetChargeType{
        /* 按流量 */
        PayByTraffic,
        /* 按带宽 */
        PayByBandwidth;
    }

    /**
     * 刷新路径类型
     */
    enum ObjectType{
        /* 文件 */
        File,
        /* 目录 */
        Directory;
    }

}
