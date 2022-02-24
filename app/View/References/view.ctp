<?php
$sets=$data['Dataset'];
$ref=$data['Reference'];
$jnl=$data['Journal'];
$file=$data['File'];
?>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <?php
					echo "File ".$file['filename']." ";
					echo $this->Html->link("(View on the NIST TRC Website)","https://trc.nist.gov/ThermoML/".$ref['doi'].".html",['target'=>'_blank']);
                    ?>
                </h2>
            </div>
            <div class="panel-body" style="font-size: 16px;">
                <ul>
                    <li>
                        <?php
                        if(isset($ref['doi'])) {
                            echo $this->Html->link($ref["title"],'http://dx.doi.org/'.$ref['doi'],["target"=>"_blank"])." <i>".$jnl['name']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']."-".$ref['endpage'].' (doi:'.$ref['doi'].')';
                        } elseif(isset($ref['url'])&&$ref['url']!="no") {
                            echo $this->Html->link($ref["title"], $ref['url'],["target"=>"_blank"])." <i>".$jnl['name']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']."-".$ref['endpage'];
                        } else {
                            if($ref['title']=="") {
                                echo $ref["bibliography"];
                            } else {
                                echo $ref["title"]." <i>".$jnl['name']."</i> "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
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
            <div class="list-group">
				<?php
				foreach($sets as $idx=>$set) {
					if($idx % 2 == 0){ $col='EEEEEE'; } else { $col='FFFFFF'; }
					$desc='<b>Dataset '.$set['setnum'].':</b> '.$set['points'].' datapoints in '.$set['sercnt'].' series, <b>quantities:</b> '.$set['dprps'].', <b>system:</b> '.$set['System']['name'];
					echo '<a href="/trc/datasets/view/'.$set['id'].'" class="list-group-item list-group-item-small" style="background-color: #'.$col.';">'.$desc."</a>";
				}
				?>
			</div>
        </div>
    </div>
</div>
