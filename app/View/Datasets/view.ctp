<?php
// Incoming variables data and dsid
//if($this->Session->read('Auth.User.type') == 'superadmin') { pr($dump);exit; }
$sys=$dump['System'];
$set=$dump['Dataset'];
$ref=$dump['Reference'];
$anns=$dump['Annotation'];
$chems=$dump['File']['Chemical'];
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
                <?php echo $this->Html->image('jsonld.png', ['width' => '100', 'url' => '/datasets/scidata2/' . $dsid, 'alt' => 'Output as JSON-LD', 'class' => 'img-responsive pull-right']); ?>
                <ul>
                    <li><?php echo "Journal: " . $this->Html->link($ref['Journal']['name'], "/journals/view/" . $ref['Journal']['id']); ?></li>

                    <?php if (!is_null($sprops)) {
                        if (count($sprops) == 1) {
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
                                    if (isset($sprops)) {
                                        foreach ($sprops as $sprop) {
                                            echo "<li>" . $sprop['property_name'] . " by " . $sprop['method_name'] . " (Phase: " . $sprop['phase'] . ")" . "</li>";
                                        }
                                    }
                                    if (isset($rprops)) {
                                        foreach ($rprops as $rprop) {
                                            echo "<li>" . $rprop['property_name'] . " by " . $rprop['method_name'] . "</li>";
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
                        if (count($sys['Substance']) > 1) {
                            echo "<ul>";
                            foreach ($sys['Substance'] as $i => $substance) {
                                $a = "";
                                if (!is_null($anns)) {
                                    foreach ($anns as $ann) {
                                        if ($ann['substance_id'] == $substance['id']) {
                                            $a = $ann['text'] . ": ";
                                        }
                                    }
                                }
                                $f = str_replace(" ", "", $substance['formula']);
                                $n = ucfirst($substance['name']);
                                foreach ($substance['Identifier'] as $ident) {
                                    if ($ident['type'] == "casrn") {
                                        $cas = $ident['value'];
                                        break;
                                    } else {
                                        $cas = "CAS# not known";
                                    }
                                }
                                $sid = $substance['id'];
                                foreach($chems as $chem)
                                {
                                    if ($chem['substance_id'] == $sid) {
                                        $num = $chem['orgnum'];
                                        $src = $chem['source'];
                                        break;
                                    }
                                }
                                if (is_null($src)) {
                                    echo "<li>" . $this->Html->link($num  .". " . $n . " " . $f . " (" . $cas . ")", "/substances/view/" . $substance['id']) . "</li>";
                                } else {
                                    echo "<li>" . $this->Html->link($num . ". " . $n . " " . $f . " (" . $cas . ") - " . $src, "/substances/view/" . $substance['id']) . "</li>";
                                }
                            }
                            echo "</ul>";
                        } else {
                            $substance = $sys['Substance'][0];
                            $f = str_replace(" ", "", $substance['formula']);
                            $n = $substance['name'];
                            foreach ($substance['Identifier'] as $ident) {
                                if ($ident['type'] == "casrn") {
                                    $cas = $ident['value'];
                                    break;
                                } else {
                                    $cas = "CAS# not known";
                                }
                            }
                            echo $this->Html->link($n . " " . $f . " (" . $cas . ")", "/substances/view/" . $substance['id']);
                        }

                        ?>
                    </li>
                </ul>
                <?php
                // Display the citation
                //debug($ref);
                $aus = $ref['authors'];
                echo "<h4>Reference</h4>";
                if (stristr($aus, "}")) {
                    $a = json_decode($aus, true);
                    $cnt = count($a);
                    $austr = "";
                    foreach ($a as $i => $au) {
                        $austr .= implode(" ", $au);
                        if ($i == $cnt - 1) {
                            $austr .= "; ";
                        } elseif ($i == $cnt - 2) {
                            $austr .= " and ";
                        } else {
                            $austr .= ", ";
                        }
                    }
                } else {
                    $austr = $aus;
                }
                if (isset($ref['doi'])) {
                    echo $this->Html->link($ref["title"], 'http://dx.doi.org/' . $ref['doi'], ["target" => "_blank"]) . "<br/>";
                    echo $austr . "<i> " . $ref['journal'] . "</i> " . "<b>" . $ref['year'] . "</b>, " . $ref['volume'] . ", " . $ref['startpage'] . "-" . $ref['endpage'];
                } elseif (isset($ref['url']) && $ref['url'] != "no") {
                    echo $this->Html->link($ref["title"], $ref['url'], ["target" => "_blank"]) . "<br/>";
                    echo $austr . "<i> " . $ref['journal'] . "</i> " . "<b>" . $ref['year'] . "</b>, " . $ref['volume'] . ", " . $ref['startpage'] . "-" . $ref['endpage'];
                } else {
                    if ($ref['title'] == "" || $ref['title'] == "Unknown reference") {
                        echo $ref["bibliography"];
                    } else {
                        echo $ref["title"] . "<i> " . $ref['journal'] . "</i> " . "<b>" . $ref['year'] . "</b>, " . $ref['volume'] . ", " . $ref['startpage'] . "-" . $ref['endpage'];
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
if(!empty($dpts)) {
    ?>
    <div class="row">
        <?php
        $dscount=count($dpts);  // dataseries count
        if($dscount<=3) {
            $width1=6;$width2=6;
        } elseif($dscount>3&&$dscount<6) {
			$width1=8;$width2=4;
        } else {
			$width1=12;$width2=4;
        }
        ?>
        <div class="col-md-<?php echo $width1; ?>">
            <?php echo $this->element('dataseries',['dpts'=>$dpts]); ?>
        </div>
        <div class="col-md-<?php echo $width2; ?>">
            <?php
			if($xlabel!="") {
				echo $this->Element("chart");
			}
			?>
        </div>
    </div>
<?php } ?>

