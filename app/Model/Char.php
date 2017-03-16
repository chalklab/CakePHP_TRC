<?php

/**
 * Class Char
 * Char model
 */
class Char extends AppModel
{
    /**
     * Clean a string by replacing unicode characters with regular versions (hyphen, space, etc.)
     * @param string $text
     * @return string $text
     */
    public function clean($text) {
        $chars=$this->find('all');
        foreach($chars as $char) {
            $c=$char['Char'];
            if($c['action']=='replace') {
                if(is_null($c['find'])) {
                    $text=preg_replace("/\x{".$c['hexcode']."}/u",$c['replacement'],$text);
                } else {
                    $text=str_replace($c['find'],$c['replacement'],$text);
                }
            } elseif($c['action']=='delete') {
                $text = preg_replace("/\x{" . $c['hexcode'] . "}/u", "", $text);
            }
        }
        return $text;
    }

}