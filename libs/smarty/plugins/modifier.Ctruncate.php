<?php
function smarty_modifier_Ctruncate($string, $length = 80, $etc = '...',$charset = 'UTF-8')
{
    if(mb_strwidth($string,'UTF-8')<$length) return $string;
        return mb_strimwidth($string,0,$length,'',$charset) . $etc;
}
?>
