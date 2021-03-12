<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title">Data
            <?php
            if(!empty($related)) {
                $js='window.location.replace("/trc/newsets/view/"+this.options[this.selectedIndex].value)';
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
            for($i=0;$i<count($dpts[0]['NewCondition']);$i++) {
                echo "<tr>";
                foreach ($dpts as $series) {
                	$columns=count($series['NewDatapoint'][0]['NewData']);
                    if(isset($series['NewDatapoint'][0]['NewCondition'])){
                        $columns+=count($series['NewDatapoint'][0]['NewCondition']);
                    }
                    if(isset($series['NewDatapoint'][0]['NewSetting'])){
                        $columns+=count($series['NewDatapoint'][0]['NewSetting']);
                    }
                    if(isset($series['NewDatapoint'][0]['NewAnnotation'])){
                        $columns+=count($series['NewDatapoint'][0]['NewAnnotation']);
                    }
                    echo "<td colspan='$columns'>";
                    echo $series['NewCondition'][$i]['NewProperty']['symbol'] . " = ";
                    if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") {
                        echo $series['NewCondition'][$i]['number'];
                        if((float)$series['NewCondition'][$i]['error']!==0.0){
                            echo " ± ".$series['NewCondition'][$i]['error'];
                        }
                    } else{
                        echo ((float)$series['NewCondition'][$i]['number']);
                        if((float)$series['NewCondition'][$i]['error']!==0.0){
                            echo " ± ".((float)$series['NewCondition'][$i]['error']);
                        }
                    }
                    if(isset($series['NewCondition'][$i]['NewUnit']['symbol'])) {
                        echo " " . $series['NewCondition'][$i]['NewUnit']['symbol'];
                    } else {
                        pr($series['NewCondition']);
                    }
                    if(isset($series['NewCondition'][$i]['NewAnnotation'])) {
                        //pr($series['Condition'][$i]['Annotation']);
                        if(!empty($series['NewCondition'][$i]['NewAnnotation'])) {
                            echo " ".$series['NewCondition'][$i]['NewAnnotation']['text'];
                        }
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            for($i=0;$i<count($dpts[0]['NewSetting']);$i++) {
                echo "<tr>";
                foreach ($dpts as $series) {
                    $columns=count($series['NewDatapoint'][0]['Data']);
                    if(isset($series['NewDatapoint'][0]['NewCondition'])){
                        $columns+=count($series['NewDatapoint'][0]['NewCondition']);
                    }
                    if(isset($series['NewDatapoint'][0]['NewSetting'])){
                        $columns+=count($series['NewDatapoint'][0]['NewSetting']);
                    }
                    echo "<td colspan='$columns'>";
                    echo $series['NewSetting'][$i]['NewProperty']['symbol'] . " = ";
                    if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") {
                        echo  $series['NewSetting'][$i]['number'];
                        if((float)$series['NewSetting'][$i]['error']!==0.0){
                            echo " ± ".$series['NewSetting'][$i]['error'];
                        }
                    } else{
                        echo  ((float)$series['NewSetting'][$i]['number']);
                        if((float)$series['NewSetting'][$i]['error']!==0.0){
                            echo " ± ".((float)$series['NewSetting'][$i]['error']);
                        }
                    }
                    if(isset($series['NewSetting'][$i]['NewUnit']['symbol'])) {
                        echo " " . $series['NewSetting'][$i]['NewUnit']['symbol'];
                    } else {
                        pr($series['NewSetting']);
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            for($i=0;$i<count($dpts[0]['NewAnnotation']);$i++) {
                echo "<tr>";
                foreach ($dpts as $series) {
                    $columns=count($series['NewDatapoint'][0]['Data']);
                    if(isset($series['NewDatapoint'][0]['NewCondition'])){
                        $columns+=count($series['NewDatapoint'][0]['NewCondition']);
                    }
                    if(isset($series['NewDatapoint'][0]['NewSetting'])){
                        $columns+=count($series['NewDatapoint'][0]['NewSetting']);
                    }
                    echo "<td colspan='$columns'>";
                    echo ucfirst($series['NewAnnotation'][$i]['type']).": ".$series['NewAnnotation'][$i]['text'];
                    echo "</td>";
                }
                echo "</tr>";
            }
            // Print table Headers
            echo "<tr>";
            for($i=0;$i<count($dpts);$i++) {
                if (isset($dpts[$i]['NewDatapoint'][0])) {
                    // conditions
					foreach ($dpts[$i]['NewDatapoint'][0]['NewCondition'] as $condition) {
						$compnum=null;
						if(isset($condition['NewComponent']['compnum'])) {
							$compnum=$condition['NewComponent']['compnum'];
						}
						if(is_null($compnum)) {
							echo "<th title=\"".$condition['NewProperty']['name']."\">";
							echo $condition['NewProperty']['symbol'];
						} else {
							echo "<th title=\"".$condition['NewProperty']['name']." (Component ".$compnum.")\">";
							echo $condition['NewProperty']['symbol']." [".$compnum."]";
						}
						 if($condition['NewUnit']['symbol']) {
                            echo "<br/>(".$condition['NewUnit']['symbol'].")"; // print unit if not unitless
                        }
                        echo "</th>";
                    }
					// data
                    foreach ($dpts[$i]['NewDatapoint'][0]['NewData'] as $data) {
						$compnum=null;
                    	if(isset($data['NewComponent']['compnum'])) {
							$compnum=$data['NewComponent']['compnum'];
						}
                    	//debug($data);exit;
						$sprop=$data['NewSampleprop'];
                        if($sprop['phase']) {
                            $title=$data['NewProperty']['name'].' ('.$sprop['phase'].')';
                            $phase=' ('.$sprop['phase'].')';
                        } else {
                            $title=$data['NewProperty']['name'];
                            $phase='';
                        }
                        if(is_null($compnum)) {
							echo "<th title=\"".$title."\">";
							echo $data['NewProperty']['symbol'];
						} else {
							echo "<th title=\"".$title." (Component ".$compnum.")\">";
							echo $data['NewProperty']['symbol']." [".$compnum."]";
						}
                        if($data['NewUnit']['symbol']) {
                            echo "<br/>(".$data['NewUnit']['symbol'].")"; // print unit if not unitless
                        }
                        //echo $phase;
                        if(isset($data['NewAnnotation'])) {
                            pr($data['NewAnnotation']);
                        }
                        echo "</th>";
                    }
                    // settings
                    foreach ($dpts[$i]['NewDatapoint'][0]['NewSetting'] as $setting) {
                        echo "<th title=\"".$setting['NewProperty']['name']."\">" . $setting['NewProperty']['symbol'];
                        if($setting['NewUnit']['symbol']) {
                            echo " (".$setting['NewUnit']['symbol'].")"; // print unit if not unitless
                        }
                        echo "</th>";
                    }
                    // annotations
                    foreach ($dpts[$i]['NewDatapoint'][0]['NewAnnotation'] as $ann) {
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
                if (count($dpts[$i]['NewDatapoint']) > $dataSize) { //count how many rows
                    $dataSize=count($dpts[$i]['NewDatapoint']);
                }
            }
            echo "</tr></thead>";
            for($i=0;$i<$dataSize;$i++) { //for each row of data we have
                echo "<tr>";
                foreach($dpts as $series) { //loop through the series
                    //debug($series);
                    if(isset($series['NewDatapoint'][$i])) { //if we have data
                        foreach ($series['NewDatapoint'][$i]['NewCondition'] as $condition) {
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
                            } else {
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
                        foreach ($series['NewDatapoint'][$i]['NewData'] as $data) {
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
                        foreach ($series['NewDatapoint'][$i]['NewSetting'] as $setting) {
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
                        foreach ($series['NewDatapoint'][$i]['NewAnnotation'] as $ann) {
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
