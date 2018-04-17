<?php // Variables passed: $rules,$templates,$snippets,$userid,$actions,$path  ?>
<script type="text/javascript">
    $(document).ready(function() {
        $('.template').on('change', function(){
            var tmplurl = "https://chalk.coas.unf.edu/trc/ruletemplates/view/"
            var selected = $(this).find("option:selected").val();
            if(selected!="") {
                // Get template
                var temp = $(this);
                var url = tmplurl + selected;
                // Remove any existing select menus
                $(".meta:visible").remove();
                // Add select menus
                $.getJSON(url).done(function(data) {
                    var regx = data[0].regex;
                    var cnt = data[0].blocks;
                    var pnlbdy = temp.parents('.panel-body');
                    var id=pnlbdy.prop('id');
                    pnlbdy.find('#regex').html(regx);
                    pnlbdy.find('#template').html(regx);
                    for(var i=0; i <cnt; i++) {
                        var idx=i+1;
                        // Add snipmeta div
                        var newid = 'snipmeta' + idx;
                        var cln = $("#snipmeta").clone(true,true).prop('id', newid);
                        // Change properites of elements before putting on the page
                        cln.find("#SnipLabel").text("Snippet B" + idx);
                        cln.find("#RuleSnippet").attr("data-block","@" + "B" + idx +"@");
                        cln.find("#RuleSnippet").attr("data-pos",idx);
                        cln.find("#RuleProperty").attr("data-pos",idx);
                        cln.find("#RuleSnippet").prop("name","data[Rule][snippet][" + idx + "]");
                        cln.find("#RuleOptional").prop("name","data[Rule][optional][" + idx + "]");
                        cln.find("#RuleProperty").prop("name","data[Rule][property][" + idx + "]");
                        cln.find("#RuleScidata").prop("name","data[Rule][scidata][" + idx + "]");
                        cln.find("#RuleUnit").prop("name","data[Rule][unit][" + idx + "]");
                        // Change the id attribute last
                        cln.find("#RuleSnippet").attr("id","RuleSnippet" + idx);
                        cln.find("#RuleOptional").attr("id","RuleOptional" + idx);
                        cln.find("#RuleProperty").attr("id","RuleProperty" + idx);
                        cln.find("#RuleScidata").attr("id","RuleScidata" + idx);
                        cln.find("#RuleUnit").attr("id","RuleUnit" + idx);
                        cln.find("#Regex").attr("id","Regex" + idx);
                        cln.prop("class",'meta col-sm-12');
                        cln.prop("style",'display: inline;');
                        $("#snipdiv").append(cln);
                        $("#" + newid + " option:first").text("Choose Regex for B" + idx);
                    }
                });
            }
        });

        $('.snip').on('change', function(){
            var snipurl = "https://chalk.coas.unf.edu/trc/rulesnippets/view/";
            var snip = $(this);
            var selected = snip.find("option:selected").val();
            console.log('rulesnippet id:' + selected);
            var pnlbdy = snip.parents('.panel-body');
            var dataids = [<?php echo $dataids; ?>];
            // Get snippet
            if(selected!="") {
                var url = snipurl + selected;
                $.getJSON(url).done(function(data) {
                    var regx = data[0].regex;
                    var propid = data[0].property_id;
                    var unitid = data[0].unit_id;
                    var scidata = data[0].scidata;
                    console.log('scidata:' + scidata);

                    // Record which snippet has been used and its regex
                    snip.attr("data-snipid",selected);
                    snip.attr("data-regex",regx);
                    // Replace regex in div
                    var template=pnlbdy.find('#template').html();
                    $("select.snip:visible").each(function(index) {
                        if($(this).attr('data-snipid')!="") {
                            var regx=$(this).attr('data-regex');
                            var encodedRegx = regx.replace(/[\u00A0-\u9999<>\&]/gim, function(i) {
                                return '&#'+i.charCodeAt(0)+';';
                            });
                            template=template.replace($(this).attr('data-block'),"<mark><b>" + encodedRegx + "</b></mark>")
                        }
                    });
                    pnlbdy.find('#regex').html(template);
                    // Show property select if needed
                    var pos=snip.attr("data-pos");
                    console.log('data-pos: ' + pos);
                    var prop=$('#RuleProperty' + pos);
                    var sci=$('#RuleScidata' + pos);
                    var unit=$('#RuleUnit' + pos);
                    if(scidata && scidata!='to_be_set') {
                        sci.val(scidata);
                    } else if(scidata=='to_be_set') {
                        sci.attr("disabled",false);
                    } else {
                        sci.select("");
                    }
                    if($.inArray(parseInt(selected),dataids)>-1) {
                        if(propid) {
                            prop.select().val(propid);
                            if(unitid) {
                                var select="<option value='" + unitid + "'>Given Unit</option>";
                                unit.append(select);
                                unit.select().val(unitid);
                                unit.attr("disabled",true).show();
                            }
                        } else {
                            prop.attr("disabled",false);
                        }
                    } else {
                        prop.select("");
                        prop.attr("disabled",true);
                        unit.select("");
                        unit.attr("disabled",true);
                        unit.hide();
                    }
                    return false;
                });
            } else {
                // Record which snippet has been used and its regex
                snip.attr("data-snipid","");
                snip.attr("data-regex","");
                // Replace regex in div
                var template=pnlbdy.find('#template').html();
                $("select.snip:visible").each(function(index) {
                    if($(this).attr('data-snipid')!="") {
                        var regx=$(this).attr('data-regex');
                        var encodedRegx = regx.replace(/[\u00A0-\u9999<>\&]/gim, function(i) {
                            return '&#'+i.charCodeAt(0)+';';
                        });
                        template=template.replace($(this).attr('data-block'),"<mark><b>" + encodedRegx + "</b></mark>")
                    }
                });
                pnlbdy.find('#regex').html(template);
                var pos=snip.attr("data-pos");
                var prop=$('#RuleProperty' + pos);
                var sci=$('#RuleScidata' + pos);
                var unit=$('#RuleUnit' + pos);
                sci.select().val("").attr("style","display: none;");
                prop.select().val("").attr("style","display: none;");
                unit.select().val("").attr("style","display: none;");
                return false;
            }
        });

        $('.prop').on('change', function() {
            var uniturl = "https://chalk.coas.unf.edu/trc/properties/getunit/";
            var prop = $(this);
            var selected = prop.find("option:selected").val();
            var pos = prop.attr('data-pos');
            if (selected != "") {
                // Get template
                var prop = $(this);
                var url = uniturl + selected;
                // Add select menus
                $.getJSON(url).done(function (data) {
                    var toAppend = "<option value=''>Select Unit</option>";
                    $.each(data,function(i,o){
                        toAppend += "<option value='" + i + "'>" + o + "</option>";
                    });
                    var unit=$('#RuleUnit' + pos);
                    unit.find('option').remove().end(); // Remove any units
                    unit.append(toAppend);
                    unit.val("").attr("style","display: inline;width: 100%;");
                });
            } else {
                unit.find('option').remove().end();
                unit.val("").attr("style","display: none;");
            }
        });

        $('form').submit(function() {
            $('.scidata').attr('disabled',false);
            $('.prop').attr('disabled',false);
            $('.unit').attr('disabled',false);
        });
    });
</script>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">Add A Rule</h2>
            </div>
            <div id="rule1" class="panel-body">
                <?php echo $this->Form->create('Rule',['inputDefaults'=>['label'=>false,'div'=>false,'class'=>'form form-horizontal']]); ?>
                <div class="col-sm-8">
                    <?php echo $this->Form->input('name', ['type' =>'text','size'=>20,'placeholder'=>"Descriptive name",'class'=>'form-control']); ?>
                    <?php echo $this->Form->input('ruletemplate_id', ["type"=>"select","class"=>"template form-control","options"=>$templates,"empty"=>"Choose Template..."]); ?>
                    <?php echo $this->Form->input('user_id', ['type' =>'hidden','value'=>$userid]); ?>
                </div>
                <div class="col-sm-4">
                    <div id="mode" class="col-sm-12">
                        <?php
                        $modes=['match'=>'Match','capture'=>'Capture','mixed'=>'Mixed'];
                        echo $this->Form->input('mode', ["type"=>"select","class"=>"form-control","options"=>$modes,"empty"=>"Select Regex Mode", "dir"=>"rtl"]);
                        ?>
                    </div>
                    <div id="layout" class="col-sm-12">
                        <?php
                        $layouts=['row'=>'Conditions/Data on one line','column'=>'Conditions/Data on multiple lines'];
                        echo $this->Form->input('layout', ["type"=>"select","class"=>"form-control","options"=>$layouts,"empty"=>"Select Data Layout", "dir"=>"rtl"]);
                        ?>
                    </div>
                    <div id="url" class="col-sm-12">
                        <?php
                        echo $this->Form->input('url', ["type"=>"text","class"=>"form-control","placeholder"=>'Regex101 URL']);
                        ?>
                    </div>
                </div>
                <div class="col-sm-12" style="margin-top: 20px;">
                    <h4 style="color: blue;">Add Snippets</h4>
                    <div id="regex" style="margin: 20px 0;"></div>
                    <div id="template" style="display: none;"></div>
                </div>
                <div class="col-sm-12">
                    <div id="snipmeta" class="cloneme" style="display: none;">
                        <h5 id="SnipLabel"></h5>
                        <div id="Regex">
                            <div class="col-sm-4">
                                <?php
                                echo $this->Form->input('snippet', ["type"=>"select","class"=>"snip form-control","options"=>$snippets,"empty"=>"changeme",
                                    "data-block"=>"","data-snipid"=>"","data-regex"=>""]);
                                ?>
                            </div>
                            <div class="col-sm-2">
                                <?php
                                echo $this->Form->input('optional', ["type"=>"select","class"=>"form-control","options"=>["yes"=>"Yes","no"=>"No"],"empty"=>"Optional?"]);
                                ?>
                            </div>
                            <div id="scidata" class="col-sm-2">
                                <?php
                                $scidata=Configure::read('scidata.selectlist');
                                echo $this->Form->input('scidata', ["type"=>"select","class"=>"scidata form-control","options"=>$scidata,"empty"=>"Select Scidata...",'disabled']);
                                ?>
                            </div>
                            <div id="props" class="col-sm-2">
                                <?php
                                echo $this->Form->input('property', ["type"=>"select","class"=>"prop form-control","options"=>$props,"empty"=>"Select Property...",'disabled']);
                                ?>
                            </div>
                            <div id="units" class="col-sm-2">
                                <?php
                                $units=[];
                                echo $this->Form->input('unit', ["type"=>"select","class"=>"unit form-control","options"=>$units,"empty"=>"Select Unit...",'style'=>'display: none;']);
                                ?>
                            </div>
                        </div>
                    </div>
                    <div id="snipdiv" class="col-sm-12" style="background-color: #cceecc;margin-bottom: 20px;padding-bottom: 10px;"></div>
                </div>
                <div class="col-sm-12">
                    <?php echo $this->Form->input('example', ['type' =>'textarea','rows'=>3,'cols'=>30,'placeholder'=>"Example line (optional)",'class'=>'form-control']); ?>
                </div>
                <div class="col-sm-12" style="margin-top: 20px;">
                    <?php echo $this->Form->submit("Add Rule",['class'=>'btn btn-default pull-right']); ?>
                </div>
            </div>
        </div>
    </div>
</div>