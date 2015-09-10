<?php
class Csrf extends ModelBase
{
    /**
     * 客户端每次请求之前，需要获取新的csrftoken到本地'_csrf'参数中，并存储到cookie的'csrftoken'中
     * 请求时，将_csrf的值作为参数提交，并将cookie的'csrftoken'提交
     * @return string
     */
    public function createCsrfParam()
    {
        $csrftoken = sha1(uniqid(mt_rand(),true));
        if (empty($csrftoken)) {
            return '';
        }
        $ssoobj = new Sso();
        $ssoobj->setCsrfCookie($csrftoken);
        return $csrftoken;
    }
    
    /**
     * 校验参数的'_csrf'与cookie的'csrftoken'是否一致
     * @return boolean    true/false    合法/不合法
     */
    public function validateCsrfToken()
    {
        $postcsrf = $_REQUEST['_csrf'];
        $csrftoken = $_COOKIE['csrftoken'];
        if (empty($postcsrf) || empty($csrftoken)) {
            $this->setError(ErrorConf::requestCsrfValidateError());
            return false;
        }
        if ($postcsrf != $csrftoken) {
            $this->setError(ErrorConf::requestCsrfValidateError());
            return false;
        }
        return true;
    }
}