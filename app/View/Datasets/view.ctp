<?php
// Incoming variables data and dsid
//if($this->Session->read('Auth.User.type') == 'superadmin') { pr($dump);exit; }
$rep=$dump['Report'];
$sys=$dump['System'];
$set=$dump['Dataset'];
$ref=$dump['Reference'];
$anns=$dump['Annotation'];
$file=$dump['File'];
$tfile=$dump['TextFile'];
$ptype=$dump['Propertytype'];
$sers=$dump['Dataseries'];
$ser=$dump['Dataseries'][0];
//if($this->Session->read('Auth.User.type') == 'superadmin') { pr($sers);exit; }
?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h2 class="panel-title"><?php echo $set['title']; ?></h2>
                </div>
                <div class="panel-body" style="font-size: 16px;">
                    <?php echo $this->Html->image('jsonld.png',['width'=>'100','url'=>'/datasets/scidata/'.$dsid,'alt'=>'Output as JSON-LD','class'=>'img-responsive pull-right']); ?>
                    <ul>
                        <li><?php echo "Publication: ".$this->Html->link($file['Publication']['title'],"/publications/view/".$file['Publication']['id']); ?></li>
                        <li><?php echo "PDF File: ".$this->Html->link($file['title'],"/files/view/".$file['id'],['target'=>'_blank']); ?></li>
                        <li><?php echo "Text File: ".$this->Html->link($tfile['title'],"/textfiles/view/".$tfile['id'],['target'=>'_blank']); ?></li>
                        <li><?php echo "Property Type: ".$ptype['code']; ?></li>
                        <li><?php echo "Phase: ".ucfirst($ptype['states']).": ".$ptype['phases']." (".$ptype['num_components']." components)"; ?></li>
                        <?php if($ptype['method'] !="") {
                            //echo "<li>Method: ".$ptype['method']."</li>";
                        } ?>
                        <?php if(!empty($rep['file_code'])) { ?>
                            <li>
                                <?php echo "File Number: ".$this->Html->link((isset($rep['file_code'])?$rep['file_code']:"N/A"),"/files/view/".$file['id']);?>
                            </li>
                        <?php } ?>
                        <li>Substances:
                            <?php
                            if(count($sys['Substance'])>1) {
                                foreach($sys['Substance'] as $i=>$substance) {
                                    foreach($anns as $ann) {
                                        if($ann['substance_id']==$substance['id']) {
                                            $a=$ann['text'];
                                        }
                                    }
                                    $f=str_replace(" ","",$substance['formula']);$n=$substance['name'];
                                    foreach($substance['Identifier'] as $ident) {
                                        if($ident['type']=="casrn") {
                                            $cas=$ident['value'];break;
                                        } else {
                                            $cas="CAS# not known";
                                        }
                                    }
                                    echo "<br />";
                                    echo $a.": ".$this->Html->link($n." ".$f." (".$cas.")","/substances/view/".$substance['id']);
                                }
                            } else {
                                $substance=$sys['Substance'][0];
                                $f=str_replace(" ","",$substance['formula']);$n=$substance['name'];
                                foreach($substance['Identifier'] as $ident) {
                                    if($ident['type']=="casrn") {
                                        $cas=$ident['value'];break;
                                    } else {
                                        $cas="CAS# not known";
                                    }
                                }
                                echo $this->Html->link($n." ".$f." (".$cas.")","/substances/view/".$substance['id']);
                            }
                            ?>
                        </li>
                    </ul>
                    <?php
                    // Display the citation
                    //debug($ref);
                    $aus=$ref['authors'];
                    echo "<h4>Reference</h4>";
                    if(stristr($aus,"}")) {
                        $a=json_decode($aus,true);
                        $cnt=count($a);$austr="";
                        foreach($a as $i=>$au) {
                            $austr.=implode(" ",$au);
                            if($i==$cnt-1) {
                                $austr.="; ";
                            } elseif($i==$cnt-2) {
                                $austr.=" and ";
                            } else {
                                $austr.=", ";
                            }
                        }
                    } else {
                        $austr=$aus;
                    }
                    if(isset($ref['doi'])) {
                        echo $this->Html->link($ref["title"],'http://dx.doi.org/'.$ref['doi'],["target"=>"_blank"])."<br/>";
                        echo $austr."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']."-".$ref['endpage'];
                    } elseif(isset($ref['url'])&&$ref['url']!="no") {
                        echo $this->Html->link($ref["title"], $ref['url'],["target"=>"_blank"])."<br/>";
                        echo $austr."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']."-".$ref['endpage'];
                    } else {
                        if($ref['title']==""||$ref['title']=="Unknown reference") {
                            echo $ref["bibliography"];
                        } else {
                            echo $ref["title"]."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']."-".$ref['endpage'];
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
$dpts=$eqns=[];
foreach($sers as $ser) {
    if(!empty($ser['Equation'])) {
        $eqns[]=$ser;
    } elseif(!empty($ser['Datapoint'])) {
        $dpts[]=$ser;
    } else {
        // No data!
    }
}
//debug($eqns);

?>
<?php
if(!empty($eqns)) {
    $eqntypes=[];
    foreach($eqns as $ser) {
        if(isset($ser['Equation'])) {
            $eqn=$ser['Equation'];
            $eqntypes[$eqn['eqntype_id']][]=$eqn;
        }
    }
    foreach($eqntypes as $type=>$eqns) {
        ?>
        <div class="row">
            <?php
            //debug($eqns[0]);
            $layout=[];
            foreach($eqns as $eqn) {
                $terms=$eqn['Eqnterm'];$vars=$eqn['Eqnvar'];$anns=$eqn['Annotation'];$sups=$eqn['SupplementalData'];$setts=$eqn['Setting'];
                foreach($terms as $term) {
                    if($term['Unit']['symbol']=='') {
                        $header=$term['Property']['symbol'];
                    } else {
                        $header=$term['Property']['symbol'].' ('.$term['Unit']['symbol'].')';
                    }
                    $layout[$term['code']]=['type'=>'term','id'=>$term['code'],'header'=>$header,'title'=>$term['Property']['name']];
                }
                foreach($vars as $var) {
                    if($var['Unit']['symbol']=='') {
                        $header=$var['Property']['symbol'];
                    } else {
                        $header=$var['Property']['symbol'].' ('.$var['Unit']['symbol'].')';
                    }
                    $layout[$var['code']]=['type'=>'var','id'=>$var['code'],'header'=>$header,'title'=>$var['Property']['name']];
                }
                foreach($anns as $ann) {
                    $layout[$ann['type']]=['type'=>'ann','id'=>$ann['type'],'header'=>ucfirst($ann['type']),'title'=>''];
                }
                foreach($sups as $sup) {
                    if($sup['Unit']['symbol']=='') {
                        $header=$sup['Property']['symbol'];
                    } else {
                        $header=$sup['Property']['symbol'].' ('.$sup['Unit']['symbol'].')';
                    }
                    $layout[$sup['property_id']]=['type'=>'sup','id'=>$sup['property_id'],'header'=>$header,'title'=>$sup['Property']['name']];
                }
                foreach($setts as $sett) {
                    if($sett['Unit']['symbol']=='') {
                        $header=$sett['Property']['symbol'];
                    } else {
                        $header=$sett['Property']['symbol'].' ('.$sett['Unit']['symbol'].')';
                    }
                    $layout[$sett['property_id']]=['type'=>'sett','id'=>$sett['property_id'],'header'=>$header,'title'=>$sett['Property']['name']];
                }}
            $totcount=count($layout);
            if($totcount==1||$totcount==2) {
                $width=6;$offset=3;
            } elseif($totcount==3) {
                $width=8;$offset=2;
            } elseif($totcount==4) {
                $width=10;$offset=1;
            } else {
                $width=12;$offset=0;
            }
            //if($this->Session->read('Auth.User.type') == 'superadmin') { debug($layout);exit; }
            ?>
            <div class="col-md-12">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h2 class="panel-title" style="padding-bottom: 10px;">
                            <?php echo $eqn['title']; ?>
                            <?php
                            if(!empty($related)&&empty($dpts)) {
                                $js='window.location.replace("/springer/datasets/view/"+this.options[this.selectedIndex].value)';
                                echo $this->Form->input('related',['type'=>'select', 'style'=>'width: 163px;margin-top: -3px;','dir'=>'rtl','options'=>$related,'class'=>'pull-right','label'=>false,'div'=>false,'empty'=>'Related Datasets','onchange'=>$js]);
                            }
                            ?>
                            <span class="pull-right">
                                <?php echo "**".$eqns[0]['Eqntype']['latex']."**&nbsp;&nbsp;"; ?>
                            </span>
                        </h2>
                    </div>
                    <div class="panel-body" style="font-size: 16px;">
                        <table class="table table-condensed table-striped">
                            <tr>
                                <?php
                                foreach($layout as $column) {
                                    echo "<td title='".$column['title']."'><b>".$column['header']."</b></td>";
                                }
                                ?>
                            </tr>
                            <?php foreach($eqns as $eqn) { ?>
                                <?php //eif($this->Session->read('Auth.User.type') == 'superadmin') { debug($eqn);exit; } ?>
                                <tr>
                                <?php
                                foreach($layout as $column) {
                                    $content='';
                                    if($column['type']=='term') {
                                        foreach($eqn['Eqnterm'] as $term) {
                                            if($term['code']==$column['id']) {
                                                $content=$term['value'];
                                                break;
                                            }
                                        }
                                    } elseif($column['type']=='var') {
                                        foreach($eqn['Eqnvar'] as $var) {
                                            if($var['code']==$column['id']) {
                                                if(is_numeric($var['min'])&&is_numeric($var['min'])) {
                                                    $content=$var['min'].'/'.$var['max'];
                                                } else {
                                                    $content=$var['min'].' '.$var['max'];
                                                }
                                                break;
                                            }
                                        }
                                    } elseif($column['type']=='ann') {
                                        foreach($eqn['Annotation'] as $ann) {
                                            if($ann['type']==$column['id']) {
                                                $content=$ann['text'];
                                                break;
                                            }
                                        }
                                    } elseif($column['type']=='sup') {
                                        foreach($eqn['SupplementalData'] as $sup) {
                                            if($sup['property_id']==$column['id']) {
                                                if(!is_null($sup['number'])) {
                                                    if($sup['number']>=1&&$sup['number']<10) {
                                                        $content=number_format($sup['number'],$sup['accuracy']-1);
                                                    } elseif($sup['number']>=10&&$sup['number']<100) {
                                                        $content=number_format($sup['number'],$sup['accuracy']-2);
                                                    } elseif($sup['number']>=100&&$sup['number']<1000) {
                                                        $content=number_format($sup['number'],$sup['accuracy']-3);
                                                    } elseif($sup['number']>=1000&&$sup['number']<10000) {
                                                        $content=number_format($sup['number'],$sup['accuracy']-4);
                                                    } else {
                                                        $content=number_format($sup['number'],abs($sup['exponent'])+$sup['accuracy']-1);
                                                    }
                                                 } elseif(!is_null($sup['text'])) {
                                                    $content=$sup['text'];
                                                }
                                                break;
                                            }
                                        }
                                    } elseif($column['type']=='sett') {
                                        foreach($eqn['Setting'] as $sett) {
                                            if($sett['property_id']==$column['id']) {
                                                if(!is_null($sett['number'])) {
                                                    if($sett['number']>=1&&$sett['number']<10) {
                                                        $content=number_format($sett['number'],$sett['accuracy']-1);
                                                    } elseif($sett['number']>=10&&$sett['number']<100) {
                                                        $content=number_format($sett['number'],$sett['accuracy']-2);
                                                    } elseif($sett['number']>=100&&$sett['number']<1000) {
                                                        $content=number_format($sett['number'],$sett['accuracy']-3);
                                                    } elseif($sett['number']>=1000&&$sett['number']<10000) {
                                                        $content=number_format($sett['number'],$sett['accuracy']-4);
                                                    } else {
                                                        $content=number_format($sett['number'],abs($sett['exponent'])+$sett['accuracy']-1);
                                                    }
                                                } elseif(!is_null($sett['text'])) {
                                                    $content=$sett['text'];
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    echo '<td>'.$content.'</td>';
                                }
                                ?>
                            </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
<?php
if(!empty($dpts)) { ?>
    <div class="row">
        <?php
        $dscount=count($dpts);
        if($dscount==1||$dscount==2) {
            $width=8;$offset=2;
        } elseif($dscount==3) {
            $width=10;$offset=1;
        } else {
            $width=12;$offset=0;
        }
        ?>
        <div class="col-md-<?php echo $width; ?> col-md-offset-<?php echo $offset; ?>">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h2 class="panel-title">Data
                        <?php
                        if(!empty($related)) {
                            $js='window.location.replace("/springer/datasets/view/"+this.options[this.selectedIndex].value)';
                            echo $this->Form->input('related',['type'=>'select', 'style'=>'width: 163px;margin-top: -3px;','dir'=>'rtl','options'=>$related,'class'=>'pull-right','label'=>false,'div'=>false,'empty'=>'Related Datasets','onchange'=>$js]);
                        }
                        ?>
                    </h2>
                </div>
                <div class="panel-body">
                    <table class="table table-condensed table-striped">
                        <thead>
                        <?php
                        $dataSize=0;
                        for($i=0;$i<count($dpts[0]['Condition']);$i++) {
                            echo "<tr>";
                            foreach ($dpts as $series) {
                                $columns=count($series['Datapoint'][0]['Data']);
                                if(isset($series['Datapoint'][0]['Condition'])){
                                    $columns+=count($series['Datapoint'][0]['Condition']);
                                }
                                if(isset($series['Datapoint'][0]['Setting'])){
                                    $columns+=count($series['Datapoint'][0]['Setting']);
                                }
                                if(isset($series['Datapoint'][0]['Annotation'])){
                                    $columns+=count($series['Datapoint'][0]['Annotation']);
                                }
                                echo "<td colspan='$columns'>";
                                echo $series['Condition'][$i]['Property']['symbol'] . " = ";
                                if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") {
                                    echo  $series['Condition'][$i]['number'];
                                    if((float)$series['Condition'][$i]['error']!==0.0){
                                        echo " ± ".$series['Condition'][$i]['error'];
                                    }
                                } else{
                                    echo  ((float)$series['Condition'][$i]['number']);
                                    if((float)$series['Condition'][$i]['error']!==0.0){
                                        echo " ± ".((float)$series['Condition'][$i]['error']);
                                    }
                                }
                                if(isset($series['Condition'][$i]['Unit']['symbol'])) {
                                    echo " " . $series['Condition'][$i]['Unit']['symbol'];
                                } else {
                                    pr($series['Condition']);
                                }
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                        for($i=0;$i<count($dpts[0]['Setting']);$i++) {
                            echo "<tr>";
                            foreach ($dpts as $series) {
                                $columns=count($series['Datapoint'][0]['Data']);
                                if(isset($series['Datapoint'][0]['Condition'])){
                                    $columns+=count($series['Datapoint'][0]['Condition']);
                                }
                                if(isset($series['Datapoint'][0]['Setting'])){
                                    $columns+=count($series['Datapoint'][0]['Setting']);
                                }
                                echo "<td colspan='$columns'>";
                                echo $series['Setting'][$i]['Property']['symbol'] . " = ";
                                if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") {
                                    echo  $series['Setting'][$i]['number'];
                                    if((float)$series['Setting'][$i]['error']!==0.0){
                                        echo " ± ".$series['Setting'][$i]['error'];
                                    }
                                } else{
                                    echo  ((float)$series['Setting'][$i]['number']);
                                    if((float)$series['Setting'][$i]['error']!==0.0){
                                        echo " ± ".((float)$series['Setting'][$i]['error']);
                                    }
                                }
                                if(isset($series['Setting'][$i]['Unit']['symbol'])) {
                                    echo " " . $series['Setting'][$i]['Unit']['symbol'];
                                } else {
                                    pr($series['Setting']);
                                }
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                        for($i=0;$i<count($dpts[0]['Annotation']);$i++) {
                            echo "<tr>";
                            foreach ($dpts as $series) {
                                $columns=count($series['Datapoint'][0]['Data']);
                                if(isset($series['Datapoint'][0]['Condition'])){
                                    $columns+=count($series['Datapoint'][0]['Condition']);
                                }
                                if(isset($series['Datapoint'][0]['Setting'])){
                                    $columns+=count($series['Datapoint'][0]['Setting']);
                                }
                                echo "<td colspan='$columns'>";
                                echo ucfirst($series['Annotation'][$i]['type']).": ".$series['Annotation'][$i]['text'];
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                        // Print table Headers
                        echo "<tr>";
                        for($i=0;$i<count($dpts);$i++) {
                            if (isset($dpts[$i]['Datapoint'][0])) {
                                foreach ($dpts[$i]['Datapoint'][0]['Condition'] as $condition) {
                                    echo "<th title=\"".$condition['Property']['name']."\">" . $condition['Property']['symbol'];
                                    if($condition['Unit']['symbol']) {
                                        echo " (".$condition['Unit']['symbol'].")"; // print unit if not unitless
                                    }
                                    echo "</th>";
                                }
                                foreach ($dpts[$i]['Datapoint'][0]['Data'] as $data) {
                                    echo "<th title=\"".$data['Property']['name']."\">" . $data['Property']['symbol'];
                                    if($data['Unit']['symbol']) {
                                        echo " (".$data['Unit']['symbol'].")"; // print unit if not unitless
                                    }
                                    echo "</th>";
                                }
                                foreach ($dpts[$i]['Datapoint'][0]['Setting'] as $setting) {
                                    echo "<th title=\"".$setting['Property']['name']."\">" . $setting['Property']['symbol'];
                                    if($setting['Unit']['symbol']) {
                                        echo " (".$setting['Unit']['symbol'].")"; // print unit if not unitless
                                    }
                                    echo "</th>";
                                }
                                foreach ($dpts[$i]['Datapoint'][0]['SupplementalData'] as $suppdata) {
                                    if(!is_null($suppdata['text'])&&!empty($suppdata['Metadata'])) {
                                        echo "<th title=\"".$suppdata['Metadata']['description']."\">" . $suppdata['Metadata']['name']." ";
                                    } else {
                                        echo "<th title=\"".$suppdata['Property']['name']."\">".$suppdata['Property']['symbol'];
                                        if($suppdata['Unit']['symbol']) {
                                            echo " (".$suppdata['Unit']['symbol'].")";
                                        }
                                    }
                                    echo "</th>";
                                }
                                foreach ($dpts[$i]['Datapoint'][0]['Annotation'] as $ann) {
                                    echo "<th>";
                                    if(!is_null($ann['text'])) {
                                        if(!is_null($ann['type'])) {
                                            echo ucfirst($ann['type']);
                                        } else {
                                            echo "Note";
                                        }
                                    }
                                    echo "</th>";
                                }
                            }
                            if (count($dpts[$i]['Datapoint']) > $dataSize) { //count how many rows
                                $dataSize=count($dpts[$i]['Datapoint']);
                            }
                        }
                        echo "</tr></thead>";
                        for($i=0;$i<$dataSize;$i++) { //for each row of data we have
                            echo "<tr>";
                            foreach($dpts as $series) { //loop through the series
                                //debug($series);
                                if(isset($series['Datapoint'][$i])) { //if we have data
                                    foreach ($series['Datapoint'][$i]['Condition'] as $condition) {
                                        if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") { //if exponential is requested
                                            if($condition['number']!==null) {
                                                echo "<td>".$condition['number']; //if we didn't request exponential then convert to float and display
                                            } else {
                                                echo "<td>";
                                            }
                                            if((float)$condition['error']!==0.0) { //if the error is not 0.0
                                                echo " ± ".$condition['error']; //print error
                                            }
                                            echo "</td>";
                                        }else{
                                            if($condition['number']!==null) {
                                                echo "<td>"; //if we didn't request exponential then convert to float and display
                                                if($condition['number']>=1&&$condition['number']<10) {
                                                    echo number_format($condition['number'],$condition['accuracy']-1);
                                                } elseif(abs($condition['number'])>=10&&abs($condition['number'])<100) {
                                                    echo number_format($condition['number'],$condition['accuracy']-2);
                                                } elseif($condition['number']>=100&&$condition['number']<1000) {
                                                    echo number_format($condition['number'],$condition['accuracy']-3);
                                                } elseif($condition['number']>=1000&&$condition['number']<10000) {
                                                    echo number_format($condition['number'],$condition['accuracy']-4);
                                                } else {
                                                    echo number_format($condition['number'],abs($condition['exponent'])+$condition['accuracy']-1);
                                                }
                                            } else {
                                                echo "<td>";
                                            }
                                            if((float)$condition['error']!==0.0) {//if the error is not 0.0
                                                echo " ± " . ((float)$condition['error']);//print error
                                            }
                                            echo "</td>";
                                        }
                                    }
                                    foreach ($series['Datapoint'][$i]['Data'] as $data) {
                                        if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") { //if exponential is requested
                                            if($data['number']!==null) {
                                                echo "<td>" . $data['number']; //if we didn't request exponential then convert to float and display
                                            } else {
                                                echo "<td>";
                                            }
                                            if((float)$data['error']!==0.0) { //if the error is not 0.0
                                                echo " ± " . $data['error']; //print error
                                            }
                                            echo "</td>";
                                        } else {
                                            if($data['number']!==null) {
                                                echo "<td>"; //if we didn't request exponential then convert to float and display
                                                if(abs($data['number'])>=1&&abs($data['number'])<10) {
                                                    echo number_format($data['number'],$data['accuracy']-1);
                                                } elseif(abs($data['number'])>=10&&abs($data['number'])<100) {
                                                    echo number_format($data['number'],$data['accuracy']-2);
                                                } elseif(abs($data['number'])>=100&&abs($data['number'])<1000) {
                                                    echo number_format($data['number'],$data['accuracy']-3);
                                                } elseif(abs($data['number'])>=1000&&abs($data['number'])<10000) {
                                                    echo number_format($data['number'],$data['accuracy']-4);
                                                } else {
                                                    echo number_format($data['number'],abs($data['exponent'])+$data['accuracy']-1);
                                                }
                                            } else {
                                                echo "<td>";
                                            }
                                            if((float)$data['error']!==0.0) {//if the error is not 0.0
                                                echo " ± " . ((float)$data['error']);//print error
                                            }
                                            echo "</td>";
                                        }
                                    }
                                    foreach ($series['Datapoint'][$i]['Setting'] as $setting) {
                                        if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") { //if exponential is requested
                                            if($setting['number']!==null) {
                                                echo "<td>" . $setting['number']; //if we didn't request exponential then convert to float and display
                                            } else {
                                                echo "<td>";
                                            }
                                            if((float)$setting['error']!==0.0) { //if the error is not 0.0
                                                echo " ± " . $setting['error']; //print error
                                            }
                                            echo "</td>";
                                        } else {
                                            if($setting['number']!==null) {
                                                echo "<td>" . ((float)$setting['number']); //if we didn't request exponential then convert to float and display
                                            } else {
                                                echo "<td>";
                                            }
                                            if((float)$setting['error']!==0.0) { //if the error is not 0.0
                                                echo " ± " . ((float)$setting['error']);//print error
                                            }
                                            echo "</td>";
                                        }
                                    }
                                    foreach ($series['Datapoint'][$i]['SupplementalData'] as $suppdata) {
                                        echo "<td>";
                                        if (!is_null($suppdata['text'])&&!empty($suppdata['Metadata'])) {
                                            echo $suppdata['text'];
                                        } else {
                                            if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") { //if exponential is requested
                                                if($suppdata['number']!==null) {
                                                    echo $suppdata['number']; //if we didn't request exponential then convert to float and display
                                                }
                                                if((float)$suppdata['error']!==0.0) { //if the error is not 0.0
                                                    echo " ± " . $suppdata['error']; //print error
                                                }
                                            } else {
                                                if($suppdata['number']!==null) {
                                                    echo ((float)$suppdata['number']); //if we didn't request exponential then convert to float and display
                                                }
                                                if((float)$suppdata['error']!==0.0) { //if the error is not 0.0
                                                    echo " ± " . ((float)$suppdata['error']);//print error
                                                }
                                            }
                                        }
                                        echo "</td>";
                                    }
                                    foreach ($series['Datapoint'][$i]['Annotation'] as $ann) {
                                        echo "<td>";
                                        if (!is_null($ann['text'])) {
                                            if($ann['text']=="(empty)"||$ann['text']=="") {
                                                echo "<span style='color: #bbb'>(empty)</span>";
                                            } else {
                                                echo $ann['text'];
                                            }
                                        }
                                        echo "</td>";
                                    }
                                } else {
                                    for($p=0;$p<$columns;$p++) {
                                        echo "<td></td>";
                                    }
                                }
                            }
                            echo "</tr>";
                        }
                        //TODO: Add second dataseries tables. Add display:inline-block to the style of each dataseries.  ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php } ?>