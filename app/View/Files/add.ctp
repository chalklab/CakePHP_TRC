<script type="text/javascript">
    $(document).ready(function() {
        $('.pub').on('change', function(){
            var pubid = $(this).find("option:selected").val();
            var url = "https://chalk.coas.unf.edu/springer/publications/view/" + pubid;
            if(pubid!="") {
                $.getJSON(url).done(function(data) {
                    var set=data[0].Ruleset;
                    var props=data[0].Property;
                    var pub=data[0].Publication;

                    if($.isEmptyObject(set)==false) {
                        $('.set').val(set.id);
                    }
                    if(props.length==1) {
                        $('.prop').val(props[0].id);
                    } else if(props.length > 1) {
                        $('.prop > option').hide();
                        $(".prop option[value='']").show();
                        $.each(props,function (i,prop) {
                            $(".prop option[value=" + prop.id + "]").show();
                        });
                    }
                    $('.title').val(pub.abbrev + ": ");
                    //alert(set.id);
                    return false;
                });
            } else {
                $('.title').val("");
                $('.set').val("");
                $('.prop > option').show();
                $('.prop').val("");
            }
        });
        $('.filetype').on('change', function(){
            var choice=$(this).find("option:selected").val();
            if(choice=='xml') {
                $('#xslt').show();
                $('#ruleset').hide();
            } else if(choice=='') {
                $('#xslt').hide();
                $('#ruleset').hide();
            } else {
                $('#xslt').hide();
                $('#ruleset').show();
            }
        });
    });
</script>

<div class="container" style="margin-top: 10px;">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">Add a File to be Extracted</h3>
                </div>
                <div class="panel-body">
                    <?php
                    $options=['class'=>'form-horizontal','enctype'=>'multipart/form-data','inputDefaults'=>['label'=>false,'selected'=>'empty','div'=>false]];
                    echo $this->Form->create('File',$options);
                    ?>
                    <div id="publicationid" class="form-group">
                        <label for="PublicationId" class="col-md-2 control-label">Publication:</label>
                        <div class="col-md-10">
                            <?php echo $this->Form->input('publication_id',['type'=>'select','options'=>$pubs,'empty'=>'Select Publication','class'=>'pub form-control']); ?>
                        </div>
                    </div>
                    <div id="publicationid" class="form-group">
                        <label for="PublicationId" class="col-md-2 control-label">File Title:</label>
                        <div class="col-md-10">
                            <?php echo $this->Form->input('title',['type'=>'text','placeholder'=>'Indicate chapter in title... (NOTE: For ZIP upload, use \'*C*\' to auto add chapters)','class'=>'title form-control']); ?>
                        </div>
                    </div>
                    <div id="numsystems" class="form-group">
                        <label for="NumSystems" class="col-md-2 control-label"># Systems:</label>
                        <div class="col-md-4">
                            <?php echo $this->Form->input('num_systems', ['type'=>'text','class'=>'form-control','placeholder'=>'# systems (compounds/mixtures)?']); ?>
                        </div>
                        <div class="col-md-2 col-md-offset-1">
                            <?php echo $this->Form->Input('layout',['type' => 'select','empty'=>'Data Layout','class'=>'form-control','options'=>['rows'=>'Row Based','columns'=>'Column Based','mixed'=>'Mixed']]); ?>
                        </div>
                        <div class="col-md-3">
                            <?php echo $this->Form->input('numsubs', ['type' =>'select','empty'=>'System Composition','class'=>'form-control','options'=>['1'=>'Pure Compound','2'=>'Binary Mixture','3'=>'Ternary Mixture','4'=>'Quaternary Mixture']]); ?>
                        </div>
                    </div>
                    <div id="properties" class="form-group">
                        <label for="PropCode" class="col-md-2 control-label">Property Code:</label>
                        <div class="col-md-4">
                            <?php echo $this->Form->input('propCode', ['type'=>'text','class'=>'form-control','placeholder'=>'(Optional) Needed for \'Heats of Mixing and Solution\'']); ?>
                        </div>
                        <label for="PropertyId" class="col-md-2 control-label">Property:</label>
                        <div class="col-md-4">
                            <?php echo $this->Form->input('property_id', ['options'=>$properties,'class'=>'prop form-control','empty'=>'Select...']); ?>
                        </div>
                    </div>
                    <div id="file" class="form-group">
                        <label for="file" class="col-md-2 control-label">Select File:</label>
                        <div class="col-md-5">
                            <?php echo $this->Form->input('file',['type'=>'file','class'=>'form-control']); ?>
                        </div>
                        <div class="col-md-2">
                            <?php echo $this->Form->input('filetype', ['type' =>'select','empty'=>'File Type','class'=>'filetype form-control','options'=>Configure::read('filetypes')]); ?>
                        </div>
                        <label for="Uploaded" class="col-md-2 control-label text-right">Already Uploaded?</label>
                        <div class="col-md-1">
                            <?php echo $this->Form->Input('uploaded',['type' => 'checkbox','class'=>'form-control']); ?>
                        </div>
                    </div>

                    <div id="xslt" class="form-group" style="display: none;">
                        <label for="Xslt" class="col-md-2 control-label">XSLT:</label>
                        <div class="col-md-5">
                            <?php echo $this->Form->input('xslt', ['options'=>$xslts,'class'=>'set form-control','empty'=>'Select...']); ?>
                        </div>
                    </div>
                    <div id="ruleset" class="form-group" style="display: none;">
                        <label for="RulesetId" class="col-md-2 control-label">Ruleset:</label>
                        <div class="col-md-5">
                            <?php echo $this->Form->input('ruleset_id', ['options'=>$rulesets,'class'=>'set form-control','empty'=>'Select...']); ?>
                        </div>
                    </div>
                    <div id="button" class="col-md-12">
                        <button type="submit" class="btn btn-default pull-right">Add File</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>