<?php $props=$data['ps'];$quans=$data['qs']; ?>
<div class="row">
    <div class="col-sm-9">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h2 class="panel-title">Add A Property</h2>
            </div>
            <div id="new" class="panel-body">
                <?php echo $this->Form->create('Property',['inputDefaults'=>['label'=>false,'div'=>false,'class'=>'form form-horizontal']]); ?>
                <div id="form" class="col-sm-12">
                    <div class="form-group col-sm-9">
                        <label for="PropertyName" class="h4">Name</label>
                        <?php echo $this->Form->input('name', ['type' =>'text','size'=>20,'class'=>'form-control','placeholder'=>"Descriptive name"]); ?>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="PropertySymbol" class="h4">Symbol</label>
                        <?php echo $this->Form->input('symbol', ['type'=>'text','size'=>10,'class'=>'form-control','placeholder'=>"Common symbol"]); ?>
                    </div>
                    <div class="form-group col-sm-12">
                        <label for="PropertyDefinition" class="h4">Definition</label>
                        <?php echo $this->Form->input('definition', ['type' =>'textarea','cols'=>30,'rows'=>3,"class"=>"form-control",'placeholder'=>"A formal definition"]); ?>
                    </div>
                    <div class="form-group col-sm-12">
                        <label for="PropertySource" class="h4">Source</label>
                        <?php echo $this->Form->input('source', ['type'=>'text','size'=>50,'class'=>'form-control','placeholder'=>"URL of source"]); ?>
                    </div>
                    <div class="form-group col-sm-4">
                        <label for="PropertySource" class="h4">Quantity</label>
                        <?php echo $this->Form->input('quantity_id',['type'=>'select','class'=>'form-control','options'=>[''=>'Select Quantity...']+$quans,'placeholder'=>"A formal definition"]); ?>
                    </div>
                    <div class="form-group col-sm-1 col-sm-offset-5">
                        <label for="PropertySource" class="h4">&nbsp;</label>
                        <?php echo $this->Form->input('user_id', ['type' =>'hidden','value'=>$userid]); ?>
                        <?php echo $this->Form->submit("Add Property",['class'=>'btn btn-default']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h2 class="panel-title">Existing Properties</h2>
            </div>
            <div class="list-group small" style="max-height: 516px;overflow-y:scroll;">
                <?php foreach($props as $id=>$prop) { ?>
                    <a href="properties/view/<?php echo $id; ?>" class="list-group-item"><?php echo $prop; ?></a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>