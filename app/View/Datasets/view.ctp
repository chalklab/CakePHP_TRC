<?php
// Incoming variables data and dsid
//if($this->Session->read('Auth.User.type') == 'superadmin') { pr($dump);exit; }
$sys=$dump['System'];
$set=$dump['Dataset'];
$ref=$dump['Reference'];
$anns=$dump['Annotation'];
$file=$dump['File'];
$chems=$file['Chemical'];
$sprops=$dump['Sampleprop'];
$rprops=$dump['Reactionprop'];
$sers=$dump['Dataseries'];
$ser=$dump['Dataseries'][0];
//debug($dump);exit;
//if($this->Session->read('Auth.User.type') == 'superadmin') { pr($sers);exit; }
?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h2 class="panel-title"><?php echo $ref['title']; ?></h2>
                </div>
                <div class="panel-body" style="font-size: 16px;">
                    <?php echo $this->Html->image('jsonld.png',['width'=>'100','url'=>'/datasets/scidata/'.$dsid,'alt'=>'Output as JSON-LD','class'=>'img-responsive pull-right']); ?>
                    <ul>
                        <li><?php echo "Journal: ".$this->Html->link($ref['Journal']['name'],"/journals/view/".$ref['Journal']['id']); ?></li>
                        <li><?php echo "File: ".$this->Html->link($file['filename'],"/files/view/".$file['id'],['target'=>'_blank']); ?></li>
                        <?php if(!is_null($sprops)) {
                            if(count($sprops)==1) {
						        ?>
                                <li><?php echo "Sample Property: " . $sprops[0]['property_name']; ?></li>
                                <li><?php echo "Methology: " . $sprops[0]['method_name']; ?></li>
                                <li><?php echo "Phase: " . $sprops[0]['phase']; ?></li>
						    <?php
						    } else {
                                ?>
                                <li>Properties
                                    <ul>
                                    <?php
                                    if(isset($sprops)) {
										foreach($sprops as $sprop) {
											echo "<li>".$sprop['property_name']." by ".$sprop['method_name']." (Phase: ".$sprop['phase'].")"."</li>";
										}
									}
									if(isset($rprops)) {
										foreach($rprops as $rprop) {
											echo "<li>".$rprop['property_name']." by ".$rprop['method_name']."</li>";
										}
									}
									?>
                                    </ul>
                                </li>
							<?php
                            }
                        } ?>
                        <li>Substances:
                            <?php
                            //debug($chems);
                            if(count($sys['Substance'])>1) {
								echo "<ul>";
								foreach($sys['Substance'] as $i=>$substance) {
									$a="";
									if(!is_null($anns)) {
										foreach($anns as $ann) {
											if($ann['substance_id']==$substance['id']) {
												$a=$ann['text'].": ";
											}
										}
									}
                                    $f=str_replace(" ","",$substance['formula']);$n=ucfirst($substance['name']);
                                    foreach($substance['Identifier'] as $ident) {
                                        if($ident['type']=="casrn") {
                                            $cas=$ident['value'];break;
                                        } else {
                                            $cas="CAS# not known";
                                        }
                                    }
                                    $sid=$substance['id'];
                                    foreach($chems as $chem) {
                                        if($chem['substance_id']==$sid) {
                                            $num=$chem['orgnum'];
                                            $src=$chem['source'];
                                            break;
                                        }
                                    }
                                    if(is_null($src)) {
										echo "<li>".$this->Html->link($num.". ".$n." ".$f." (".$cas.")","/substances/view/".$substance['id'])."</li>";
									} else {
										echo "<li>".$this->Html->link($num.". ".$n." ".$f." (".$cas.") - ".$src,"/substances/view/".$substance['id'])."</li>";
									}
                                }
								echo "</ul>";
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
$sprops=[];
foreach($dump['Sampleprop'] as $sprop) {
    $sprops[$sprop['propnum']]=$sprop['phase'];
}
?>
<?php
// Reaction
if(!empty($rprops)) { ?>
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
	<?php
    foreach($rprops as $rprop) {
        $title=$rprop['type'];
        $rxn=json_decode($rprop['reaction'],true);
        ?>
        <div class="col-md-<?php echo $width; ?> col-md-offset-<?php echo $offset; ?>">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h2 class="panel-title"><?php echo $title; ?></h2>
                </div>
                <div class="panel-body text-center" style="font-size: 20px;">
                    <?php
                    $rs=$ps=$chms=[];
                    foreach($chems as $chm) {
                        $chms[$chm['orgnum']]=$chm['formula'];
                    }
                    foreach($rxn as $com) {
                        $c=[];
						$c['s']=abs($com['stoichcoef']);
						if(stristr($com['phase'],'crystal')) {
                            $c['p']='(s)';
                        } elseif(stristr($com['phase'],'liquid')||stristr($com['phase'],'solution')||stristr($com['phase'],'glass')||stristr($com['phase'],'fluid')) {
							$c['p']='(l)';
						} elseif(stristr($com['phase'],'gas')||stristr($com['phase'],'air')) {
							$c['p']='(g)';
						}
						$c['f']=$chms[$com['orgnum']];
                        if($com['stoichcoef']>0) {
							$ps[]=$c;
                        } else {
							$rs[]=$c;
						}
                    }
                    foreach($rs as $i=>$r) {
                        if($r['s']>1) { echo $r['s']; }
                        echo preg_replace('/([0-9]+)/i','<sub>$1</sub>',$r['f']);
                        echo $r['p'];
                        if($i<(count($rs)-1 )) { echo " + "; }
                    }
                    echo " &rarr; ";
					foreach($ps as $i=>$p) {
						if($p['s']>1) { echo $p['s']; }
						echo preg_replace('/([0-9]+)/i','<sub>$1</sub>',$p['f']);
						echo $p['p'];
						if($i<(count($ps)-1 )) { echo " + "; }
					}
					?>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    </div>
    <?php
}
?>
<?php
// Datapoints
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
                                    echo $phase;
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