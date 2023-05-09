<?php
$sys=$dump['System'];
$set=$dump['Dataset'];
$ref=$dump['Reference'];
$jnl=$ref['Journal'];
$sprops=$dump['Sampleprop'];
$sers=$dump['Dataseries'];
$ser=$dump['Dataseries'][0];
$mix=$dump['Mixture'];
$comps=$mix['Compohnent'];
$dpts=[];
foreach($sers as $ser) { if(!empty($ser['Datapoint'])) { $dpts[]=$ser; } }
$path = Configure::read('path');
//debug($dump);exit;
?>
<!-- metadata -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading clearfix">
                <div class="col-xs-12 col-sm-10" style="padding: 0;">
					<h2 class="panel-title">
						<?php
						// Display the citation
						$aus = $ref['authors'];
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
							echo $this->Html->link($ref["title"], 'http://dx.doi.org/' . $ref['doi'], ["target" => "_blank"])." ";
							echo $austr . "<i> " . $jnl['name'] . "</i> " . "<b>" . $ref['year'] . "</b>, " . $ref['volume'] . ", " . $ref['startpage'] . "-" . $ref['endpage'];
						} elseif (isset($ref['url']) && $ref['url'] != "no") {
							echo $this->Html->link($ref["title"], $ref['url'], ["target" => "_blank"])." ";
							echo $austr . "<i> " . $jnl['name'] . "</i> " . "<b>" . $ref['year'] . "</b>, " . $ref['volume'] . ", " . $ref['startpage'] . "-" . $ref['endpage'];
						} else {
							if ($ref['title'] == "" || $ref['title'] == "Unknown reference") {
								echo $ref["bibliography"];
							} else {
								echo $ref["title"] . "<i> " . $jnl['name'] . "</i> " . "<b>" . $ref['year'] . "</b>, " . $ref['volume'] . ", " . $ref['startpage'] . "-" . $ref['endpage'];
							}
						}
						?>
					</h2>
				</div>
				<div class="col-xs-12 col-sm-1 col-sm-offset-1" style="padding-right: 0;margin-top: 5px;">
					<?php
					if(!empty($related)) {
						$js='window.location.replace("'.$path.'datasets/view/"+this.options[this.selectedIndex].value)';
						echo $this->Form->input('related',['type'=>'select','class'=>'form-control','style'=>'color: black;width: 140px;','dir'=>'rtl','options'=>$related,'class'=>'pull-right','label'=>false,'div'=>false,'empty'=>'Related Datasets','onchange'=>$js]);
					}
					?>
				</div>
			</div>
            <div class="panel-body" style="font-size: 16px;">
                <?php echo $this->Html->image('jsonld.png', ['width' => '100', 'url' => '/datasets/scidata/' . $dsid, 'alt' => 'Output as JSON-LD', 'class' => 'img-responsive pull-right']); ?>
                <ul>
                    <li><?php echo "Journal: " . $this->Html->link($jnl['name'], "/journals/view/" . $jnl['id']); ?></li>
                    <?php if (!is_null($sprops)) {
						$phases=[];
						foreach($mix['Phase'] as $pt) { $phases[]=$pt['Phasetype']['name']; }
						$phsstr=implode(", ",$phases);
						if (count($sprops) == 1) {
							?>
                            <li><?php echo "Sample Quantity: " . $sprops[0]['quantity_name']; ?></li>
                            <li><?php echo "Methodology: " . $sprops[0]['method_name']; ?></li>
                            <li><?php echo "Phase: " . $phsstr; ?></li>
                            <?php
                        } else {
                            ?>
                            <li>Properties
                                <ul>
                                    <?php
                                    if (!empty($sprops)) {
                                        foreach ($sprops as $sprop) {
                                            echo "<li>" . $sprop['quantity_name'] . " by " . $sprop['method_name'] . " (Phase: " . $sprop['phase'] . ")" . "</li>";
                                        }
                                    }
                                    ?>
                                </ul>
                            </li>
							<li><?php echo "Phases: " . $phsstr; ?></li>
							<?php
                        }
                    } ?>
                    <li>Substances:
                        <?php
                        if (count($sys['Substance']) > 1) {
                            echo "<ul>";
							foreach($comps as $c) {
								$num=$c['compnum'];
								$chm=$c['Chemical'];
								$src=$chm['sourcetype'];
								$sub=$chm['Substance'];
								$n=strtolower($sub['name']);
								$f=$sub['formula'];
								$ids=$sub['Identifier'];
								$cas='CASRN not known';
								foreach($ids as $ident) {
									if($ident['type']=='casrn') {
										$cas=$ident['value'];
									}
								}
								if (is_null($src)) {
									echo "<li>".$this->Html->link("[".$num."] ".$n." ".$f." (".$cas.")","/substances/view/".$sub['id'],['escape'=>false])."</li>";
								} else {
									echo "<li>".$this->Html->link("[".$num."] ".$n." ".$f." (".$cas.") - ".$src,"/substances/view/".$sub['id'],['escape'=>false])."</li>";
								}
							}
							echo "</ul>";
                        } else {
                            $substance = $sys['Substance'][0];
                            $f = str_replace(" ", "", $substance['formula']);
                            $n = $substance['name'];$cas = "CAS# not known";
							foreach ($substance['Identifier'] as $ident) {
                                if ($ident['type'] == "casrn") {
                                    $cas = $ident['value'];break;
                                }
                            }
                            echo $this->Html->link($n . " " . $f . " (" . $cas . ")", "/substances/view/" . $substance['id']);
                        }
                        ?>
                    </li>
                </ul>
             </div>
        </div>
    </div>
</div>
<!-- data/graph -->
<?php
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
            <?php if($xlabel!="") { echo $this->element("chart"); } ?>
        </div>
    </div>
<?php } ?>

