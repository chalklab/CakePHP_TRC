<script type="application/javascript">
	$(document).ready(function() {
		// search and show/hide systems in substances/index.ctp
		$("#syssrc").on('keyup',function(){
			let val=$(this).val().toLowerCase().trim();
			let reps=$('.showReports');
			let syss=$("#systems");
			reps.show();
			if(val!=='') {
				reps.not(':contains(' + val + ')').parent().hide();
				let cnt = reps.not('.hidden').length;
				syss.find("h2").text('Systems (' + cnt + ')');
			}
		});
		$(".showReports").click(function(e){
			e.preventDefault();
			if($(this).parents("li").find(".systemReports").css("display")!=="block") {
				$(".systemReports").hide();
				$(this).parents("li").find(".systemReports").css("display", "block");
			}else{
				$(".systemReports").hide();
			}
		})
	})
</script>
<?php
$substance=$data['Substance'];
$identifiers=$data['Identifier'];
$systems=$data['System'];
$idents=[];
foreach ($identifiers as $ident) { $idents[$ident['type']]=$ident['value']; }
//pr($idents);exit;
$idnicetext=Configure::read('identlabels')
?>
<div class="row">
    <div class="col-sm-6 col-sm-offset-2">
        <div class="panel panel-success" style="font-size: 18px;">
            <div class="panel-heading">
                <h1 class="panel-title">Substance</h1>
            </div>
            <div class="panel-body">
				<ul class="list-unstyled" style="margin-bottom: 0;">
					<li><?php echo "Substance Name: ".$substance['name'];?></li>
					<li><?php echo "Formula: ".$substance['formula'];?></li>
					<li><?php echo "Molecular Weight: ".$substance['mw']." g/mol";?></li>
					<?php
					foreach($identifiers as $identifier) {
						if(isset($idnicetext[$identifier['type']])) {
							echo "<li>".$idnicetext[$identifier['type']].": ".$identifier['value']."</li>";
						}
					}
					?>
				</ul>
            </div>
        </div>
    </div>
	<div class="col-sm-2" style="padding-left: 0;">
		<?php echo $this->element('molecule',$idents+['name'=>$substance['name'],'named'=>false]); ?>
	</div>
</div>
<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <div class="panel panel-primary">
            <div id="systems" class="panel-heading clearfix">
				<div class="row">
					<div class="col-sm-9">
						<h2 class="panel-title">Systems<?php echo ' ('.count($systems).')'; ?></h2>
					</div>
					<div class="col-sm-3">
						<?php if(count($systems)>15) { ?>
							<input id="syssrc" placeholder="Search in results..." style="padding: 10px;" class="form-control input-sm pull-right" data-search-override="true" type="text"/>
						<?php } ?>
					</div>
				</div>
            </div>
            <div class="list-group responsivediv500">
                <?php foreach($systems as $sys) {
                    if(count($sys['Dataset'])>0) { ?>
                        <li class="list-group-item list-group-item-small">
                            <div class="showReports" style="display:inline;cursor: pointer;">
                                <?php echo $sys['name']; ?> (<?php echo count($sys['Dataset']); ?>)
                            </div>
                            <div class="systemReports" style="display:none;">
                            	<ul class="list-unstyled">
                                	<?php
									foreach($sys['Dataset'] as $dataset) {
										echo "<li>".$this->Html->link($dataset['title'],'/datasets/view/'.$dataset['id'])."</li>";
									}
								?>
                            	</ul>
                        	</div>
                    	</li>
                    <?php
                    }
                } ?>
            </div>
        </div>
    </div>
</div>
