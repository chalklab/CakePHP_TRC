<?php
$sci=$data['scidata'];unset($data['scidata']);
$meth=$sci['methodology'];unset($data['@context']);
$sys=$sci['system'];
$sets=$sci['dataset'];
$srcs=$data['sources'];unset($data['sources']);
$rtgs=$data['rights'];unset($data['rights']);
$ref=$srcs[0];
$tml=$srcs[1];

// organize sys facets so they are easy to find
$facets=[];
foreach($sys['facets'] as $facet) {
    $idx=$facet['@id'];unset($facet['@id']);
    $facets[$idx]=$facet;
}
// gets QUDT units->symbols
$units=$this->requestAction('/units/qudtunits');
//pr($sets);exit;
?>
<script type="text/javascript">
    $(document).ready(function() {
        $('.dataset').on('click', function() {
            var setid=$(this).attr('id');
            $('.sets').hide();
            $('#' + setid + 'div').show();
            return false
        });
        $('.dataseries').on('click', function() {
            var serid=$(this).attr('id');
            var temp=serid.split("_");
            var setidx=temp[0].replace("ser","");
            $('.sers' + setidx).hide();
            $('#' + serid + 'div').show();
            return false
        });
    });
</script>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"><?php echo $data['title']; ?></h2>
            </div>
            <div class="panel-body" style="font-size: 16px;">
				<?php echo $this->Html->image('jsonld.png',['width'=>'100','url'=>'/files/view/'.str_replace('trc:file:','',$data['pid']).'/jsonld','alt'=>'Output as JSON-LD','class'=>'img-responsive pull-right']); ?>
                <ul>
                    <li><?php echo "Data from ThermoML File: ".$this->Html->link($tml["url"],$tml["url"],["target"=>"_blank"]); ?></li>
                    <li><?php echo "Description: ".$data['description']; ?></li>
                    <li><?php echo "Publisher: ".$data['publisher']; ?></li>
                </ul>
				<?php
				// Display the citation
				echo "<h4>Reference</h4>";
				echo $this->Html->link($ref["citation"],$ref["url"],["target"=>"_blank"]);
				?>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="btn-group btn-group-justified" role="group" aria-label="Datasets">
            <?php foreach($sets as $idx=>$set) { ?>
                <div class="btn-group" role="group">
                    <button id="set<?php echo $idx; ?>" type="button" class="btn btn-default btn-sm dataset">Dataset <?php echo ($idx+1); ?></button>
                </div>
            <?php } ?>
        </div>
        <?php foreach($sets as $setidx=>$set) { ?>
            <div id="set<?php echo $setidx; ?>div" class="sets panel panel-primary" style="display: <?php echo ($setidx==0) ? "inline" : "none"; ?>;">
                <?php
                $system=$facets[$set['system']];
                $cons=$system['constituents'];
                $compds=[];
                foreach ($cons as $con) {
                    $compdnum=str_replace(["compound","/"],"",$con);
                    $chm=$facets['chemical/'.$compdnum.'/'];
                    $compds[$compdnum]=array_merge($facets[$con],['chemical'=>$chm]);
                }
                ksort($compds);
                ?>
                <div class="panel-body">
                    <div class="col-sm-4">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h2 class="panel-title"><?php echo ucfirst($system['name']); ?></h2>
                            </div>
                            <div class="panel-body" style="font-size: 16px;">
								<?php
								echo "Phase: ".$system['phase'];$idx=0;
								foreach($compds as $sub) {
									$chem=[];$pw=4;$idx++;
									(isset($sub['name'])) ? $chem['name']=$sub['name'] : $chem['name']="Unknown compound";
									(isset($sub['inchi'])) ? $chem['inchi']=$sub['inchi'] : $chem['inchi']="";
									(isset($sub['inchikey'])) ? $chem['inchikey']=$sub['inchikey'] : $chem['inchikey']="";
									(isset($sub['casrn'])) ? $chem['casrn']=$sub['casrn'] : $chem['casrn']="";
									//debug($chem);
									$opts=['index'=>$setidx."_".$idx,'fontsize'=>14,'height'=>$pw*50,'system'=>true]+$chem;
									echo $this->element('molecule',$opts);
								}
								?>
							</div>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <div class="btn-group btn-group-justified" role="group" aria-label="Dataseries">
							<?php foreach($set['dataseries'] as $seridx=>$ser) { ?>
                                <div class="btn-group" role="group">
                                    <button id="ser<?php echo $setidx."_".$seridx; ?>" type="button" class="btn btn-default btn-sm dataseries">Dataseries <?php echo ($seridx+1); ?></button>
                                </div>
							<?php } ?>
                        </div>
                        <?php foreach($set['dataseries'] as $seridx=>$ser) { ?>
                            <div id="ser<?php echo $setidx."_".$seridx; ?>div" class="sers<?php echo $setidx; ?> panel panel-success" style="display: <?php echo ($seridx==0) ? "inline" : "none"; ?>;">
                                <h4>Conditions</h4>
                                <?php
                                $conds=$ser['conditions'];
                                foreach($conds as $condstr) {
                                    list($c,$cidx,$v,$vidx)=explode("/",$condstr);
                                    $conid=$c."/".$cidx."/";$valid=$v."/".$vidx."/";
                                    $cond=$facets[$conid];$value=$unit=$unitref=null;
                                    foreach($cond['value'] as $val) {
                                        if($val['@id']==$valid) {
                                            $value=$val['number'];
                                            if(isset($val['unit'])) { $unit=$val['unit']; }
											if(isset($val['unitref'])) { $unitref=$val['unitref']; }
											break;
                                        }
                                    }
                                    // get symbol for QUDT unit
                                    if(!is_null($unitref)) { $unit=$units[str_replace("qudt:","",$unitref)]; }
                                    echo $cond['property'].": ".$value." ".$unit."<br />";
                                }
                                $points=$ser['datapoints'];
                                // count columns needed
                                $ccnt=count($points[0]['conditions']);$dcnt=0;
                                if(isset($points[0]['value']["@id"])) {
									$dcnt=1;
                                } else {
                                    $dcnt=count($points[0]['value']);
                                }
                                $cols=$ccnt+$dcnt;
							    debug($points);
                                ?>
                                <table class="table table-condensed table-striped">
                                    <tr>
                                        <?php
                                        foreach($points[0]['conditions'] as $conid) {
                                            echo "<th>".$facets[$conid]['property']."<th>";
                                        }
										if(!isset($points[0]['value'][0])) {
											$points[0]['value'][0]=$points[0]['value'];
										}
										foreach($points[0]['value'] as $data) {
                                        
                                        }
										$dcnt=1;

										?>
                                    </tr>
                                    <?php
                                    foreach($points as $pnt) {
                                    
                                    }
                                    ?>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>