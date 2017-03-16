<script type="text/javascript">
    $(document).ready(function() {

        // Fires when user selects a different rule from any select list
        $('.rule').on('change', function(){
            var rule = $(this);
            var pnl = rule.parents('.panel');
            var regxdiv = pnl.find('#regex');
            var selected = rule.find("option:selected").val();
            if(selected!="") {
                // Get rule info (template and snippets)
                var url = "https://chalk.coas.unf.edu/springer/rules/view/" + selected;
                $.getJSON(url).done(function(data) {
                    var tmpl = data[0].Ruletemplate;
                    var tregx = tmpl.regex;
                    var snips = data[0].Rulesnippet;
                    $.each(snips,function(i,snip) {
                        // Get block that snippet is part of
                        var block = "@B" + snip['RulesRulesnippet'].block + "@";
                        var sname = snip.name;
                        var sregx = snip.regex;
                        var encodedSregx = sregx.replace(/[\u00A0-\u9999<>\&]/gim, function(i) {
                            return '&#'+i.charCodeAt(0)+';';
                        });
                        tregx=tregx.replace(block,"<mark title='" + sname + ": " + sregx + "'><b>" + block + "</b></mark>")
                    });
                    regxdiv.html(tregx);
                });
            } else {
                regxdiv.html('');
            }
        });

        // Fires when user clicks add button for a rule
        $('.step').on('click', function(){
            var oldstep = Number($("#stepnum").html());
            var newstep = oldstep + 1;
            var newid = "step" + newstep;
            //alert(newid);
            var cln = $("#step1").clone(true,true).prop('id', newid);
            // Change title of previous rule
            cln.find('.panel-title').html('Step ' + newstep + " (add rule)");
            // Update ids of rule div and button
            cln.find('#rule1').attr('id','rule' + newstep);
            cln.find('#step1btn').show();
            cln.find('#step1btn').attr('id','step' + newstep + 'btn');
            // Update form input ids, clear regex and update stepnum
            cln.find('#Rule1RuleId').attr('id','Rule' + newstep + 'RuleId').attr('name','data[Rule][' + newstep + '][rule_id]');
            cln.find('#Rule1Action').attr('id','Rule' + newstep + 'Action').attr('name','data[Rule][' + newstep + '][action]');
            cln.find('#Rule1Rows').attr('id','Rule' + newstep + 'Rows').attr('name','data[Rule][' + newstep + '][rows]');
            cln.find('#Rule1Step').attr('value',newstep);
            cln.find('#Rule1Step').attr('id','Rule' + newstep + 'Step').attr('name','data[Rule][' + newstep + '][step]');
            cln.find('#Rule1Layout').attr('id','Rule' + newstep + 'Layout').attr('name','data[Rule][' + newstep + '][layout]');
            cln.find('#regex').html('');
            cln.find('#rule' + newstep).show();
            // Add clone
            $("#steps").append(cln);
            // Update stepnum
            $("#stepnum").html(newstep);
            // Hide add button of previous step
            $('#step' + oldstep +'btn').hide();
            // Update title of previous step
            $("#step" + oldstep).find('.panel-title').html('Step ' + oldstep);
            return false;
        });

    });
</script>

<div class="row">
    <?php echo $this->Form->create('Ruleset',['inputDefaults'=>['label'=>false,'div'=>false],'class'=>'form form-horizontal']); ?>
    <div id="ruleset" class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Create a Ruleset</h2>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="RulesetName" class="col-sm-2 control-label">Name</label>
                    <div class="col-sm-9">
                        <?php
                        if(!empty($ruleset)) {
                            echo $this->Form->input('name', ['type' => 'text', 'size' => 20, 'placeholder' => "Helpful name of the ruleset", 'class' => 'form-control',"default" =>$ruleset['Ruleset']['name']]);
                        } else {
                            echo $this->Form->input('name', ['type' => 'text', 'size' => 20, 'placeholder' => "Helpful name of the ruleset", 'class' => 'form-control']);
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="RulesetComment" class="col-sm-2 control-label">Comment</label>
                    <div class="col-sm-9">
                        <?php
                        if(!empty($ruleset)) {
                            echo $this->Form->input('comment', ['type' => 'text', 'size' => 20, 'placeholder' => "What this ruleset is used for?", 'class' => 'form-control',"default" =>$ruleset['Ruleset']['comment']]);
                        } else {
                            echo $this->Form->input('comment', ['type' => 'text', 'size' => 20, 'placeholder' => "What this ruleset is used for?", 'class' => 'form-control']);
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="RulesetPropertyId" class="col-sm-2 control-label">Property</label>
                    <div class="col-sm-4">
                        <?php
                        $opts=[''=>'Select...']+$properties;
                        echo $this->Form->input('property_id', ['type' => 'select', 'class' => 'form-control', 'options' => $opts]);
                        ?>
                    </div>
                    <label for="RulesetXslt" class="col-sm-2 col-sm-offset-1 control-label">Use XSLT?</label>
                    <div class="col-sm-2">
                        <?php
                        echo $this->Form->input('xslt', ['type' => 'checkbox', 'class' => 'form-control']);
                        ?>
                    </div>
                </div>
                <div id="user">
                    <?php echo $this->Form->input('user_id', ["type"=>"hidden","value"=>$userid]); ?>
                </div>
            </div>
        </div>
    </div>
    <div id="steps">
        <div id="step1" class="col-sm-10 col-sm-offset-1">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <span id="regex" class="text-right pull-right"></span><h2 class="panel-title">Step 1 (add rule)</h2>
                </div>
                <div id="rule1" class="panel-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <?php echo $this->Form->input('Rule.1.rule_id', ["type"=>"select","class"=>"rule form-control","options"=>$rules,"empty"=>"Choose Rule..."]); ?>
                        </div>
                        <div class="col-sm-2">
                            <?php echo $this->Form->input('Rule.1.action', ["type"=>"select","class"=>"action form-control","options"=>$actions,"empty"=>"Action..."]); ?>
                        </div>
                        <div class="col-sm-2">
                            <?php
                            $rowopts=['single'=>'Single row','multiple'=>'One to many'];
                            echo $this->Form->input('Rule.1.rows', ["type"=>"select","class"=>"rows form-control","options"=>$rowopts,"empty"=>"Rows..."]);
                            ?>
                        </div>
                        <div class="col-sm-2">
                            <?php
                            $layoutopts=['row'=>'Condition/Expt data is in one row','column'=>'Condition/Expt data are on different rows'];
                            echo $this->Form->input('Rule.1.layout', ["type"=>"select","class"=>"layout form-control","options"=>$layoutopts,"empty"=>"Data Layout..."]);
                            ?>
                        </div>
                        <div class="col-sm-1">
                            <?php echo $this->Form->input('Rule.1.step', ["type"=>"hidden","value"=>1]); ?>
                            <div id="step1btn" class="step btn btn-default">Add</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="submit" class="col-sm-10 col-sm-offset-1">
        <div id="stepnum" class="hidden">1</div>
        <?php echo $this->Form->submit('Add RuleSet',['class'=>'form-control pull-right']); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>