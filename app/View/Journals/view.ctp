<?php
$jnl = $data['Journal'];
$refs = $data['Reference'];
?>
<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"><?php echo $jnl['name'].' ('.$this->Html->link('Website',$jnl['homepage'],['target'=>'blank']).')'; ?></h2>
            </div>
            <div class="panel-body">
                <div class="col-sm-15">
                    <p>
                    Publisher: <?php echo $jnl['publisher']; ?><br/>
                    Coden: <?php echo $jnl['coden']; ?><br/>
                    ISSN: <?php echo $jnl['issn']; ?><br/>
                    DOI Prefix(es): <?php echo $jnl['doiprefix']; ?><br/>
                    </p>
                </div>
                <div class="col-sm-2">
                    <?php
					// functionality to be shown if an admin (example only - clean and delte functions don't exist)
                    if ($this->Session->read('Auth.User.type') == 'admin') {
                        echo $this->Html->link("Add Journal","/journals/add/",['class'=>'btn btn-success btn-sm btn-block']);
                        echo $this->Html->link("Clean Journal","/journals/clean/".$jnl['id'],['class'=>'btn btn-warning btn-sm btn-block']);
                        echo $this->Html->link("Delete Journal","/journals/delete/".$jnl['id'],['class'=>'btn btn-danger btn-sm btn-block']);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
	<div class="col-sm-10 col-sm-offset-1">
		<div class="panel panel-success">
			<div class="panel-heading">
				<h2 class="panel-title">References (<?php echo count($refs); ?>)</h2>
			</div>
			<div class="list-group responsivediv400">
				<?php
				foreach($refs as $ridx=>$ref) {
					$citestr=preg_replace('/\*\d+\*/',$jnl['abbrev'],$ref['citation']);
					echo $this->Html->link($citestr,'/references/view/'.$ref['id'],['class'=>'list-group-item list-group-item-small']);
				}
				?>
			</div>
		</div>
	</div>
</div>

