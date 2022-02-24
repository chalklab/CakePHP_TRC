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
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <?php //echo $this->Html->image('jsonld.png',['width'=>'100','url'=>'/files/view/'.str_replace('trc:file:','',$data['pid']).'/jsonld','alt'=>'Output as JSON-LD','class'=>'img-responsive pull-right']); ?>
                <h2 class="panel-title" style="padding: 5px 0;">Journal Article</h2>
            </div>
            <div class="panel-body" style="font-size: 16px;">
				<?php
				// Display the citation
				$url="https://doi.org/".$ref["doi"];
				$jnl=$ref['Journal']['abbrev'];
				$citestr=preg_replace('/\*\d+\*/',$jnl,$ref['citation']);
				echo $citestr." (".$this->Html->link($url,$url,["target"=>"_blank"]).")";
				?>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8 col-md-offset-2">
		<h3>Datasets</h3>
        <ul class="list-group responsivediv200">
			<?php
			foreach($sets as $setidx=>$set) {
				//if($setidx==0) { debug($set); }
				$sys=$set['System'];$prp=$set['Sampleprop'][0];$sers=$set['Dataseries'];
				$points=0;
				foreach($sers as $ser) {
					$points+=count($ser['Datapoint']);
				}
				$p="";if($points>1) { $p="s"; }
				$desc=ucfirst($sys['name']). " => ".$prp['quantity_name'].": ".$points." point".$p;
				echo '<a href="/trc/datasets/view/'.$set['id'].'" class="list-group-item">'.$desc."</a>";
			}
			?>
		</ul>
    </div>
</div>
