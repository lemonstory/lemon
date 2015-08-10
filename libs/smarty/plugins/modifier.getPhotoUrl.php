<?php
function smarty_modifier_getPhotoUrl($pic_id,$pic_type)
{
        $num = (hexdec(substr($v['pid'], -2)) % 16) + 1;
        return sprintf(PHOTO_URL, $num, $pic_type, $pic_id);
}
?>