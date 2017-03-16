<h2>Add a Rule Template</h2>
<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <?php
        echo $this->Form->create('Ruletemplate', ['url'=>['controller'=>'ruletemplates','action'=>'add'],'role'=>'form','class'=>'form-horizontal','inputDefaults'=>['label'=>false,'div'=>false]]);
        ?>
        <div class="form-group">
            <label for="RuletemplateName" class="col-sm-2 control-label">Name</label>
            <div class="col-sm-10">
                <?php echo $this->Form->input('name', ['type' =>'text','size'=>30,'placeholder'=>"Name",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RuletemplateRegex" class="col-sm-2 control-label">Regex</label>
            <div class="col-sm-10">
                <?php echo $this->Form->input('regex', ['type' =>'text','size'=>30,'placeholder'=>"Regex",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RuletemplateBlocks" class="col-sm-2 control-label">Chunks</label>
            <div class="col-sm-2">
                <?php
                $opts=[''=>'Select...',1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12,13=>13,14=>14,15=>15];
                echo $this->Form->input('blocks', ['type' =>'select','options'=>$opts,'class'=>'form-control']);
                ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RuletemplateExample" class="col-sm-2 control-label">Example</label>
            <div class="col-sm-10">
                <?php echo $this->Form->input('example', ['type' =>'text','size'=>30,'placeholder'=>"Example",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RuletemplateComment" class="col-sm-2 control-label">Comment</label>
            <div class="col-sm-10">
                <?php echo $this->Form->input('comment', ['type' =>'textarea','rows'=>3,'cols'=>30,'placeholder'=>"Comment",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <?php echo $this->Form->end('Add Template',['class'=>'btn btn-default']); ?>
            </div>
        </div>
    </div>
</div>
