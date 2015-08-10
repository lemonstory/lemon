<?php 
class Page 
{
    static function NumeralPager($p,$page,$baseUri,$total){
        if($page>1 && $p<=$page){
            $total = '<span><b>Total : '.$total.'</b></span>';
            $lastPage = $p == 0 ? '' : ('<a href="'.$baseUri. '&p=' . ($p - 1) . '">上一页</a>');
            $previousPages='';
            for ($i=max(array(0,$p-4));$i<$p;$i++){
                $previousPages.='<a href="'.$baseUri. '&p=' . $i . '">'.($i+1).'</a>';
            }
            $currentPage='<span>' . ($p + 1) . '</span>';
            $nextPages='';
            for ($i=$p+1,$end=min(array($page,$p+5));$i<$end;$i++){
                $nextPages.='<a href="'.$baseUri. '&p=' . $i . '">'.($i+1).'</a>';
            }
            $nextPage = $p == $page - 1 ? '' : ('<a href="'.$baseUri. '&p=' . ($p + 1) . '">下一页</a>');
            return '<div class="standardPager">'.$total.$lastPage.$previousPages.$currentPage.$nextPages.$nextPage.'</div>';
        }else{
            return '';
        }
    }
}