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
                        <?php echo $this->Html->link('Keywords','/keywords/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('References','/references/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Substances','/substances/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Systems','/systems/index',['class'=>'list-group-item item-small']); ?>
                    </div>
                </div>
			</div>
            <div class="col-md-9">
                <?php echo $this->element('most'); ?>
            </div>
        </div>
    </div>
<?php } else { ?>
<div class="row">
    <div class="col-md-7 col-md-offset-1 text-justify" style="font-size: 18px;">
        <h2>Welcome to the ThermoML Converter!</h2>
        <p>The ThermoMLConverter is a website where data represented in the
			<?php echo $this->Html->link('IUPAC ThermoML XML','https://iupac.org/what-we-do/digital-standards/thermoml/',['target'=>'_blank']); ?>
			 specification and currently published at the
			<?php echo $this->Html->link('NIST TRC website','https://trc.nist.gov/ThermoML/',['target'=>'_blank']); ?>
            that has been ingested into a MySQL database and made accessible on this website. This site is built using
			<?php echo $this->Html->link('CakePHP 2','https://book.cakephp.org/2/en/index.html',['target'=>'_blank']); ?>
			If you would like more information about this project please contact <a href="mailto:schalk@unf.edu">Stuart Chalk</a>.</p>
		<p>This site also provides functionality to download the data as JSON-LD in the
			<?php echo $this->Html->link('SciData framework','https://stuchalk.github.io/scidata/',['target'=>'_blank']); ?>
			 format.  The SciData framework is a generalized data model for storing scientifc data in an organized
			semantic representation, and as this site outputs the data in JSON-LD it can be converted to RDF or ingested
		    into a graph database for use in machine learning.</p>
		<p>This website is described in this paper, the code and complete datasets in MySQL and JSON-LD are availabl online via GitHub.</p>
		<p><em>NOTE: This site is not affiliated with nor supported by NIST TRC, however the developer is appreciative of
				the quality of data in the TRC ThermoML repository and has created this website to make the data
				available in additional formats.</em></p>
    </div>
	<div class="col-md-3">
		<div class="panel panel-success" style="font-size: 18px;">
			<div class="panel-heading">
				<h3 class="panel-title">Browse Extracted Data</h3>
			</div>
			<div class="list-group">
				<?php echo $this->Html->link('Datasets ('.$setcount.')','/datasets/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('Keywords ('.$keycount.')','/keywords/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('References ('.$refcount.')','/references/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('Substances ('.$subcount.')','/substances/index',['class'=>'list-group-item']); ?>
				<?php echo $this->Html->link('Systems ('.$syscount.')','/systems/index',['class'=>'list-group-item']); ?>
			</div>
		</div>
		<?php
			echo $this->element('most');
		?>
	</div>
</div>
<?php } ?>
