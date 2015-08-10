using Aliyun.Api;
using Aliyun.Api.CDN.CDN20141111.Request;
using Aliyun.Api.CDN.CDN20141111.Response;
using System;

namespace com.aliyun.openapi.cdn.demo
{
    class Demo
    {
        private static string serverUrl = "<serverUrl>";//http://cdn.aliyuncs.com/
        private static string accessKeyId = "<accessKeyId>";
        private static string accessKeySecret = "<accessKeySecret>";

        private static IAliyunClient client = new DefaultAliyunClient(serverUrl, accessKeyId, accessKeySecret);

        /// <summary>
        /// 开通CDN服务
        /// </summary>
        public void OpenCdnService()
        {
            OpenCdnServiceRequest request = new OpenCdnServiceRequest();
            request.InternetChargeType = InternetChargeType.PayByTraffic.ToString();

            try
            {
                OpenCdnServiceResponse response = client.Execute(request);
                if (string.IsNullOrEmpty(response.Code))
                {//开通成功               
                }
                else
                {//开通失败
                    String errorCode = response.Code;//取得错误码
                    String message = response.Message;//取得错误信息
                }
            }
            catch (Exception e)
            {
                // TODO: handle exception
            }
        }

        /// <summary>
        /// 刷新缓存
        /// </summary>
        public void RefreshObjectCaches()
        {
            RefreshObjectCachesRequest request = new RefreshObjectCachesRequest();

            request.ObjectType = ObjectType.File.ToString();
            request.ObjectPath = "www.yourdomain.com/1.txt";

            try
            {
                RefreshObjectCachesResponse response = client.Execute(request);               
                if (string.IsNullOrEmpty(response.Code))
                {//刷新成功             
                }
                else
                {//刷新失败
                    String errorCode = response.Code;//取得错误码
                    String message = response.Message;//取得错误信息
                }
            }
            catch (Exception e)
            {
                // TODO: handle exception
            }
        }
    }

    /// <summary>
    /// cdn 刷新路径类型
    /// </summary>
    public enum ObjectType
    {
        /* 文件 */
        File,
        /* 目录 */
        Directory
    }

    /// <summary>
    /// cdn 计费类型
    /// </summary>
    public enum InternetChargeType
    {
        /* 按流量 */
        PayByTraffic,
        /* 按带宽 */
        PayByBandwidth
    }
}

