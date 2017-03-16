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
        <h2>Welcome to ChemExtractor! <span class="label label-danger">Beta</span></h2>
        <p>ChemExtractor is an online system for taking text out of PDF files and identifying it relative to chemical property data.  Rather than use natural language processing (NLP), the system employs the concepts of rules and rulesets.  In a sense, the ruleset allows identification of the data by processing it as a human does.  A human can look at data and disect line by line what it is from the layout and the symbols/headers on the page.  If a computer is given a set of rules about where data should be be found and what the data looks like (usually identified by a regex expression) then it can process text, ignoring some things and capturing others.</p>
        <p>To make such a system work the ruleset has to be designend by a human.  Starting with a generic set of rules (i.e. if the second chunk of text on this line looks like "^\d{2,6}-\d{2}-\d$" - regex for a CAS Registry Number) the user can build a ruleset that fits the data structure. Flexibility is afforded where data can be chunked based on n or more same characters (e.g. space).  In this way the knowledge about the locations of the data and domain knowledge about the what each peice of data is are encoded in the ruleset.</p>
        <p>Current development focusses on; a complete formal specification for this process, identification of places where scripts written to implement the processing of the rulesets contain knowledge of the data structure (i.e. it is not encoded in the ruleset as it should), and design and coding on a visual tool to develop a ruleset.</p>
        <p>If you would like more information about this project please contact <a href="mailto:schalk@unf.edu">Stuart Chalk</a></p>
    </div>
</div>
<?php } ?>