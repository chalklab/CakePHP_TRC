<?php
$sers=$dump['Dataset']['Dataseries'];
foreach($sers as $ser) {
    if($ser['id']==$serid) {
        $dpts=$ser['Datapoint'];break;
    }
}
echo $this->element('dataseries',['dpts'=>$dpts]);
?>