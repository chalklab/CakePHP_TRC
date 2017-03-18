<?php if($this->Session->read('Auth.User')) { ?>
    <style>
        .item-small {
            padding: 5px 15px;
        }
    </style>
    <div class="row">
        <div class="col-sm-12">
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Project Status</h3>
                    </div>
                    <div class="list-group">
                        <li class="list-group-item item-small">Original Publications<span class="badge"><?php echo $pubcount; ?></span></li>
                        <li class="list-group-item item-small">PDF Files Extracted<span class="badge"><?php echo $filecount; ?></span></li>
                        <li class="list-group-item item-small">Datasets Extracted<span class="badge"><?php echo $datasetcount; ?></span></li>
                        <li class="list-group-item item-small">Dataseries Extracted<span class="badge"><?php echo $dataseriescount; ?></span></li>
                        <li class="list-group-item item-small">Datapoints Extracted<span class="badge"><?php echo $datacount; ?></span></li>
                        <li class="list-group-item item-small">Equations Extracted<span class="badge"><?php echo $eqncount; ?></span></li>
                    </div>
                </div>
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">Browse Extracted Data</h3>
                    </div>
                    <div class="list-group">
                        <?php echo $this->Html->link('Files','/files/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Properties','/properties/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Publications','/publications/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('References','/references/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Substances','/substances/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Systems','/systems/index',['class'=>'list-group-item item-small']); ?>
                    </div>
                </div>
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title">Extraction System</h3>
                    </div>
                    <div class="list-group">
                        <?php echo $this->Html->link('Rules','/rules/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Rulesets','/rulesets/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Rule Snippets','/rulesnippets/index',['class'=>'list-group-item item-small']); ?>
                        <?php echo $this->Html->link('Rule Templates','/ruletemplates/index',['class'=>'list-group-item item-small']); ?>
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
    <div class="col-md-12 text-justify">
        <h2>Welcome to ChemConverter! <span class="label label-danger">Beta</span></h2>
        <p>ChemConverter is a project to take scientific data from any source and convert it to the SciData framework format.</p>
        <p>If you would like more information about this project please contact <a href="mailto:schalk@unf.edu">Stuart Chalk</a></p>
    </div>
</div>
<?php } ?>