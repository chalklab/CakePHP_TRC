<?php if($this->Session->read('Auth.User')) { ?>
    <style>
        .item-small {
            padding: 5px 15px;
        }
    </style>
    <div class="row">
        <div class="col-sm-12">
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">Browse Extracted Data</h3>
                    </div>
                    <div class="list-group">
                        <?php echo $this->Html->link('Datasets','/datasets/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Files','/files/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Properties','/properties/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('References','/references/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Substances','/substances/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Systems','/systems/index',['class'=>'list-group-item item-small']); ?>
                    </div>
                </div>
			</div>
            <div class="col-md-9">
                <?php echo $this->element('recent'); ?>
            </div>
        </div>
    </div>
<?php } else { ?>
<div class="row">
    <div class="col-md-10 col-md-offset-1 text-justify" style="font-size: 18px;">
        <h2>Welcome to the ThermoMLConverter! <span class="label label-danger">Beta</span></h2>
        <p>The ThermoMLConverter is a website where data represented in the IUPAC ThermoML XML Specification and currently published at
            the <?php echo $this->Html->link('NIST TRC website','https://trc.nist.gov/ThermoML/',['target'=>'_blank']); ?>
            that has been ingested into a MySQL database and made accessible on this website via a REST API. If you
            would like more information about this project please contact <a href="mailto:schalk@unf.edu">Stuart Chalk</a></p>
    </div>
	<div class="col-md-5 col-md-offset-1">
		<div class="panel panel-success">
			<div class="panel-heading">
				<h3 class="panel-title">Browse Extracted Data</h3>
			</div>
			<div class="list-group">
				<?php echo $this->Html->link('Datasets','/datasets/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('Properties','/properties/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('References','/references/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('Substances','/substances/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('Systems','/systems/index',['class'=>'list-group-item']); ?>
			</div>
		</div>
	</div>
</div>
<?php } ?>
