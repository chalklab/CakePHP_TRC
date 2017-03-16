<script type="text/javascript">
    $(document).ready(function() {
        $('#RulesnippetPropertyId').on('change', function() {
            var prop = $(this);
            var selected = prop.find("option:selected").val();
            var uniturl = "https://chalk.coas.unf.edu/springer/properties/getunit/";
            var unit=$('#RulesnippetUnitId');
            if (selected != "") {
                // Get units
                var url = uniturl + selected;
                $.getJSON(url).done(function (data) {
                    // Add select options
                    var toAppend = "<option value=''>Select Unit...</option>";
                    $.each(data,function(i,o){
                        toAppend += "<option value='" + i + "'>" + o + "</option>";
                    });
                    unit.find('option').remove().end(); // Remove any units
                    unit.append(toAppend);
                    unit.val("").attr("style","display: inline;width: 100%;");
                });
            } else {
                unit.find('option').remove().end();
                unit.val("").attr("style","display: none;");
            }
        });
    });
</script>

<h2>Add a Rule Snippet</h2>
<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <?php
        echo $this->Form->create('Rulesnippet', ['url'=>['controller'=>'rulesnippets','action'=>'add'],'role'=>'form','class'=>'form-horizontal','inputDefaults'=>['label'=>false,'div'=>false]]);
        ?>
        <div class="form-group">
            <label for="RulesnippetName" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-7">
                <?php echo $this->Form->input('name', ['type' =>'text','size'=>30,'placeholder'=>"Name",'class'=>'form-control']); ?>
            </div>
            <label for="RulesnippetCode" class="col-sm-1 control-label">Code</label>
            <div class="col-sm-3">
                <?php echo $this->Form->input('code', ['type' =>'text','size'=>20,'placeholder'=>"Six characters, e.g. ABCDEF",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RulesnippetRegex" class="col-sm-1 control-label">Regex</label>
            <div class="col-sm-5">
                <?php echo $this->Form->input('regex', ['type' =>'text','size'=>30,'placeholder'=>"Regex string",'class'=>'form-control']); ?>
            </div>
            <label for="RulesnippetMode" class="col-sm-1 control-label">Mode</label>
            <div class="col-sm-2">
                <?php
                $opts=[''=>'Select...','match'=>'Match','capture'=>'Capture'];
                echo $this->Form->input('mode', ['type' =>'select','options'=>$opts,'class'=>'form-control']);
                ?>
            </div>
            <label for="RulesnippetUrl" class="col-sm-1 control-label">URL</label>
            <div class="col-sm-2">
                <?php echo $this->Form->input('url', ['type' =>'text','size'=>30,'placeholder'=>"Regex101 URL",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RulesnippetPropertyId" class="col-sm-1 control-label">Property</label>
            <div class="col-sm-2">
                <?php
                $opts=[''=>'Select...']+$props;
                echo $this->Form->input('property_id', ['type' =>'select','options'=>$opts,'class'=>'prop form-control']);
                ?>
            </div>
            <label for="RulesnippetUnitId" class="col-sm-1 control-label"></label>
            <div class="col-sm-2">
                <?php
                $opts=[];
                echo $this->Form->input('unit_id', ['type' =>'select','options'=>$opts,'class'=>'form-control','style'=>'display: none;']);
                ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RulesnippetMetadataId" class="col-sm-1 control-label">Datatype</label>
            <div class="col-sm-3">
                <?php
                $opts=[''=>'Select...']+$meta;
                echo $this->Form->input('metadata_id', ['type' =>'select','options'=>$opts,'class'=>'form-control']);
                ?>
            </div>
            <label for="RulesnippetScidata" class="col-sm-2 control-label">Scidata Category</label>
            <div class="col-sm-3">
                <?php
                $scidata=Configure::read('scidata.selectlist');
                echo $this->Form->input('scidata', ["type"=>"select","class"=>"form-control","options"=>$scidata,"empty"=>"Select..."]);
                ?>
            </div>
        </div>
       <div class="form-group">
            <label for="RulesnippetExample" class="col-sm-1 control-label">Example</label>
            <div class="col-sm-10">
                <?php echo $this->Form->input('example', ['type' =>'text','size'=>30,'placeholder'=>"Example",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <label for="RulesnippetComment" class="col-sm-1 control-label">Comment</label>
            <div class="col-sm-10">
                <?php echo $this->Form->input('comment', ['type' =>'textarea','rows'=>3,'cols'=>30,'placeholder'=>"Comment",'class'=>'form-control']); ?>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-10 col-sm-2">
                <?php echo $this->Form->end('Add Snippet',['class'=>'btn btn-default']); ?>
            </div>
        </div>
    </div>
</div>
