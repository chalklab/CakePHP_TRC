<?php
// Incoming variables data and dsid
//pr($dump['Equation']);exit;
$sets=$data['Dataset'];
$ref=$data['Reference'];
$codes=$data['Refcode'];
//pr($ref);
?>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <?php
                        echo "Reference ".$ref['id'];
                    ?>
                </h2>
            </div>
            <div class="panel-body" style="font-size: 16px;">
                <?php echo $this->Html->image('jsonld.png',['width'=>'100','url'=>'/references/scidata/'.$ref['id'],'alt'=>'Output as JSON-LD','class'=>'img-responsive pull-right']); ?>
                <ul>
                    <li>
                        <?php
                        if(isset($ref['doi'])) {
                            echo $this->Html->link($ref["title"],'http://dx.doi.org/'.$ref['doi'],["target"=>"_blank"])."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                        } elseif(isset($ref['url'])&&$ref['url']!="no") {
                            echo $this->Html->link($ref["title"], $ref['url'],["target"=>"_blank"])."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                        } else {
                            if($ref['title']=="") {
                                echo $ref["bibliography"];
                            } else {
                                echo $ref["title"]."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                            }
                        }
                        ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h2 class="panel-title">Data
                    <?php if(!empty($related)) {
                        $js='window.location.replace("/trc/datasets/view/"+this.options[this.selectedIndex].value)';
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
                    for($i=0;$i<count($dump['Dataseries'][0]['Condition']);$i++) {
                        echo "<tr>";
                        foreach ($dump['Dataseries'] as $series) {
                            $columns=count($series['Datapoint'][0]['Data']);
                            if(isset($series['Datapoint'][0]['Condition'])){
                                $columns+=count($series['Datapoint'][0]['Condition']);
                            }
                            if(isset($series['Datapoint'][0]['Setting'])){
                                $columns+=count($series['Datapoint'][0]['Setting']);
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
                    for($i=0;$i<count($dump['Dataseries'][0]['Setting']);$i++) {
                        echo "<tr>";
                        foreach ($dump['Dataseries'] as $series) {
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
                    // Print table Headers
                    echo "<tr>";
                    for($i=0;$i<count($dump['Dataseries']);$i++) {
                        if (isset($dump['Dataseries'][$i]['Datapoint'][0])) {
                            foreach ($dump['Dataseries'][$i]['Datapoint'][0]['Condition'] as $condition) {
                                echo "<th title=\"".$condition['Property']['name']."\">" . $condition['Property']['symbol'];
                                if($condition['Unit']['symbol'] ) {
                                    echo " (".$condition['Unit']['symbol'].")"; // print unit if not unitless
                                }
                                echo "</th>";
                            }
                            foreach ($dump['Dataseries'][$i]['Datapoint'][0]['Data'] as $data) {
                                echo "<th title=\"".$data['Property']['name']."\">" . $data['Property']['symbol'];
                                if($data['Unit']['symbol'] ) {
                                    echo " (".$data['Unit']['symbol'].")"; // print unit if not unitless
                                }
                                echo "</th>";
                            }
                            foreach ($dump['Dataseries'][$i]['Datapoint'][0]['Setting'] as $setting) {
                                echo "<th title=\"".$setting['Property']['name']."\">" . $setting['Property']['symbol'];
                                if($setting['Unit']['symbol'] ) {
                                    echo " (".$setting['Unit']['symbol'].")"; // print unit if not unitless
                                }
                                echo "</th>";
                            }
                            foreach ($dump['Dataseries'][$i]['Datapoint'][0]['SupplementalData'] as $suppdata) {
                                if(!is_null($suppdata['text'])&&!empty($suppdata['Metadata'])) {
                                    echo "<th title=\"".$suppdata['Metadata']['description']."\">" . $suppdata['Metadata']['name']." ";
                                } else {
                                    echo "<th title=\"".$suppdata['Property']['name']."\">".$suppdata['Property']['symbol'];
                                    if($suppdata['Unit']['symbol'] ) {
                                        echo " (".$suppdata['Unit']['symbol'].")";
                                    }
                                }
                                echo "</th>";
                            }
                            foreach ($dump['Dataseries'][$i]['Datapoint'][0]['Annotation'] as $ann) {
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
                        if (count($dump['Dataseries'][$i]['Datapoint']) > $dataSize) { //count how many rows
                            $dataSize=count($dump['Dataseries'][$i]['Datapoint']);
                        }
                    }
                    echo "</tr></thead>";
                    for($i=0;$i<$dataSize;$i++) { //for each row of data we have
                        echo "<tr>";
                        foreach($dump['Dataseries'] as $series) { //loop through the series
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
                                            } elseif($condition['number']>=10&&$condition['number']<100) {
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
                                            if($data['number']>=1&&$data['number']<10) {
                                                echo number_format($data['number'],$data['accuracy']-1);
                                            } elseif($data['number']>=10&&$data['number']<100) {
                                                echo number_format($data['number'],$data['accuracy']-2);
                                            } elseif($data['number']>=100&&$data['number']<1000) {
                                                echo number_format($data['number'],$data['accuracy']-3);
                                            } elseif($data['number']>=1000&&$data['number']<10000) {
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
                                        if($ann['text']=="(empty)"||$ann['text']=="")
                                            echo "<span style='color: #bbb'>(empty)</span>";
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
                <?php
                $aus=$ref['authors'];
                if(stristr($aus,"}")) {
                    $a=json_decode($aus,true);
                    echo "<b>";
                    $cnt=count($a);
                    foreach($a as $i=>$au) {
                        echo implode(" ",$au);
                        if($i==$cnt-1) {
                            echo "; ";
                        } elseif($i==$cnt-2) {
                            echo " and ";
                        } else {
                            echo ", ";
                        }
                    }
                    echo "</b>";
                } else {
                    echo "<b>".$aus."</b>";
                }
                if(isset($ref['doi'])) {
                    echo $this->Html->link($ref["title"],'http://dx.doi.org/'.$ref['doi'],["target"=>"_blank"])."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                } elseif(isset($ref['url'])&&$ref['url']!="no") {
                    echo $this->Html->link($ref["title"], $ref['url'],["target"=>"_blank"])."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                } else {
                    if($ref['title']=="") {
                        echo $ref["bibliography"];
                    } else {
                        echo $ref["title"]."<i> ".$ref['journal']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>