<?php
class SensitiveWord extends ModelBase
{
    const PCRE_PATTERN = '/[^a-zA-Z0-9\x{4e00}-\x{9f5a}\x{ff10}-\x{ff19}\x{ff21}-\x{ff5a}\x{2160}-\x{2179}\x{2460}-\x{2469}\x{2474}-\x{2487}\x{2488}-\x{249B}\x{3220}-\x{3229}]/u';
    const SENSITIVE_WORD_MONEY = '安全提醒：如果聊天中涉及财产操作，请一定先核实好友身份。';
    public static $SENSITIVE_WORD_LIST = array(
            "淘宝", "支付宝", "卡号", "银行卡", "假钞", "人民币", "假币", "账号", "款", "钱", "转账"
            );
    
    /**
     * 检测私信内容是否涉及财产安全
     * @param I $uid        发送用户
     * @param I $toUid      接收用户
     * @param S $content    发送内容
     * @return boolean      true/false    存在财产安全内容/正常内容
     */
    public static function checkIsMoneyMessage($uid, $toUid, $content)
    { 
        if ($uid == $_SERVER['sysuid'] || $toUid == $_SERVER['sysuid']) {
            return false;
        }
        
        $content = strip_tags($content);
        $content = preg_replace(self::PCRE_PATTERN, '', $content);
        
        $sensitiveWords = self::$SENSITIVE_WORD_LIST;
        if (empty($sensitiveWords)) {
            return false;
        }
        foreach($sensitiveWords as $word){
            $pos = strpos($content, $word);
            if($pos !== false){
                return true;
            }
        }
        return false;
    }
}