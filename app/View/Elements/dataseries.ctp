<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title">Data
            <?php
            if(!empty($related)) {
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
                        echo $series['Condition'][$i]['number'];
                        if((float)$series['Condition'][$i]['error']!==0.0){
                            echo " ± ".$series['Condition'][$i]['error'];
                        }
                    } else{
                        echo ((float)$series['Condition'][$i]['number']);
                        if((float)$series['Condition'][$i]['error']!==0.0){
                            echo " ± ".((float)$series['Condition'][$i]['error']);
                        }
                    }
                    if(isset($series['Condition'][$i]['Unit']['symbol'])) {
                        echo " " . $series['Condition'][$i]['Unit']['symbol'];
                    } else {
                        pr($series['Condition']);
                    }
                    if(isset($series['Condition'][$i]['Annotation'])) {
                        //pr($series['Condition'][$i]['Annotation']);
                        if(!empty($series['Condition'][$i]['Annotation'])) {
                            echo " ".$series['Condition'][$i]['Annotation']['text'];
                        }
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
                        $sprop=$data['Sampleprop'];
                        if($sprop['phase']) {
                            $title=$data['Property']['name'].' ('.$sprop['phase'].')';
                            $phase=' ('.$sprop['phase'].')';
                        } else {
                            $title=$data['Property']['name'];
                            $phase='';
                        }
                        echo "<th title=\"".$title."\">" . $data['Property']['symbol'];
                        if($data['Unit']['symbol']) {
                            echo " (".$data['Unit']['symbol'].")"; // print unit if not unitless
                        }
                        //echo $phase;
                        if(isset($data['Annotation'])) {
                            pr($data['Annotation']);
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
                                    echo "<td title='".$condition['id']."'>"; //if we didn't request exponential then convert to float and display
                                    if($condition['number']>=1&&$condition['number']<10) {
                                        echo number_format($condition['number'],$condition['accuracy']-1);
                                    } elseif(abs($condition['number'])>=10&&abs($condition['number'])<100) {
                                        echo number_format($condition['number'],$condition['accuracy']-2);
                                    } elseif($condition['number']>=100&&$condition['number']<1000) {
                                        echo number_format($condition['number'],$condition['accuracy']-3);
                                    } elseif($condition['number']>=1000&&$condition['number']<10000) {
                                        echo number_format($condition['number'],$condition['accuracy']-4);
                                    } else {
                                        echo sprintf('%.'.($condition['accuracy']-1).'E',$condition['number']);
                                        //echo number_format($condition['number'],abs($condition['exponent'])+$condition['accuracy']-1);
                                    }
                                } else {
                                    echo "<td>";
                                }


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