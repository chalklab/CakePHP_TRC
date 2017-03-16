<?php
// View of the textfile
//$temp=str_replace(["\n","\r","\t","  "],"",$tfile['captured']);
//$temp=utf8_encode($temp);
//$temp=html_entity_decode($temp);
//$temp=str_replace("'","\'",$temp);
//echo $temp;exit;
$captured=json_decode($tfile['captured'], true);

$fileerrors=json_decode($tfile['errors'], true);
(isset($fileerrors['rules'])) ? $rcount=count($fileerrors['rules']) : $rcount=0;
(isset($fileerrors['snips'])) ? $scount=count($fileerrors['snips']) : $scount=0;
$fcount=$rcount+$scount;
?>
<script type="application/javascript">
    $("body").on("focus", "#textEdit", function (e) {
        $("#ingest").toggle();
        $("#saveEdit").toggle();
    });
    $("body").on("blur", "#textPanel", function (e) {
        // Save the text in 'textEdit' to the textfiles table, rerun the extraction, and refresh the page
        $("#ingest").toggle();
        $("#saveEdit").toggle();
        var origtext=$("#origText").text();
        var newtext=$("#textEdit").text();
        if(newtext!=origtext) {
            $.ajax({
                type: 'POST',
                dataType: 'text',
                context: $(this),
                url: '<?php echo $path."/textfiles/newversion/".$tfile['id']; ?>',
                data: {text: newtext},
                success: function (data, textStatus, XHR) {
                    window.location.href = data;
                    return false;
                },
                error: function (data, textStatus, XHR) {
                    alert("Error saving new version!");
                    return false;
                }
            });
        } else {
            alert("Text unchanged");
        }
    });
    $("body").on("click", "#submitGithubIssue", function (e) {
        $('<div></div>').appendTo('body')
            .html('<div><h6>Are you sure you want to submit this issue?</h6></div>')
            .dialog({
                modal: true,
                title: 'Submit Issue',
                zIndex: 10000,
                autoOpen: true,
                width: 'auto',
                resizable: false,
                buttons: {
                    Yes: function () {
                        $.ajax({
                            type: 'POST',
                            url: '?submitGithubIssue',
                            data: "body=" + $("#githubBody").val(),
                            success: function (data, textStatus, XHR) {
                                alert('Issue submitted');
                            }
                        });

                        $(this).dialog("close");
                    },
                    No: function () {
                        $(this).dialog("close");
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
    })
    $("body").on("click", "#ingest", function (e) {
        window.location.href = '<?php echo $path . "/datarectification/ingest/" . $tfile['id']; ?>';
    })
</script>
<div class="row">
    <div class="col-md-7">
        <div id="textPanel" class="panel panel-info">
            <div class="panel-heading">
                <h5 class="panel-title clearfix">
                    <?php echo $tfile['title']; ?> (Version: <?php echo $tfile['version']; ?>)
                    <span id="ingest" class="btn btn-xs btn-primary pull-right" style="width: 100px;">Ingest File</span>
                    <span id="saveEdit" class="btn btn-xs btn-success pull-right" style="display: none;">Save and reprocess</span>
                </h5>
            </div>
            <div id="origText" class="hidden"><?php echo htmlentities($tfile['text']); ?></div>
            <pre id="textEdit" class="panel-body pre-scrollable" contenteditable="true" style="overflow-x:scroll;overflow-y:scroll;height: 300px;"><?php echo htmlentities($tfile['text']); ?></pre>
        </div>
        <div class="panel with-nav-tabs panel-primary">
            <div class="panel-heading">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab1primary" data-toggle="tab">Captured</a></li>
                    <li><a href="#tab2primary" data-toggle="tab">Trash</a></li>
                    <li><a href="#tab3primary" data-toggle="tab">Errors<?php echo " (".$fcount.")"; ?></a></li>
                    <li><a href="#tab4primary" data-toggle="tab">Debug</a></li>
                    <li><a href="#tab5primary" data-toggle="tab">GitHub</a></li>
                </ul>
            </div>
            <div class="panel-body">
                <div class="tab-content" style="overflow-x:scroll;overflow-y:scroll;height: 200px;">
                    <div class="tab-pane fade in active" id="tab1primary">
                        <?php
                        $captured=json_decode($tfile['captured'], true);
                        $anns=$captured['annotations'];
                        (isset($captured['citations'])) ? $cites=$captured['citations'] : $cites=[];
                        $comps=$captured['compounds'];
                        $conds=$captured['conditions'];
                        $data=$captured['data'];
                        (isset($captured['datafactors'])) ? $datafactors=$captured['datafactors'] : $datafactors=[];
                        $errors=$captured['errors'];
                        (isset($captured['eqndiffs'])) ? $eqndiffs=$captured['eqndiffs'] : $eqndiffs=[];
                        $eqnops=$captured['eqnoperators'];
                        $eqnprops=$captured['eqnprops'];
                        $eqnunits=$captured['eqnpropunits'];
                        $eqnterms=$captured['eqnterms'];
                        $eqnvars=$captured['eqnvariables'];
                        $eqnvarlimits=$captured['eqnvariablelimits'];
                        (isset($captured['metadata'])) ? $meta=$captured['metadata'] : $meta=[];
                        $props=$captured['properties'];
                        $headers=$captured['propheaders'];
                        $refs=$captured['references'];
                        $series=$captured['series'];
                        (isset($captured['seriesanns'])) ? $sanns=$captured['seriesanns'] : $sanns=[];
                        $sconds=$captured['seriesconds'];
                        $setts=$captured['settings'];
                        $supps=$captured['suppdata'];
                        if(!empty($anns)) {
                            echo "<h4>Annotations</h4>";
                            echo "<ul>";
                            foreach($anns as $a) {
                                $loc=$a['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$a['label'].": ".$a['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($cites)) {
                            echo "<h4>Citations</h4>";
                            echo "<ul>";
                            foreach($cites as $c) {
                                echo "<li>".$c['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($comps)) {
                            echo "<h4>Compounds</h4>";
                            echo "<ul>";
                            foreach($comps as $c) {
                                $loc=$c['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$c['label'].": ".$c['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($conds)) {
                            echo "<h4>Conditions</h4>";
                            echo "<ul>";
                            foreach($conds as $c) {
                                $loc=$c['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$c['label'].": ".$c['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($data)) {
                            echo "<h4>Data</h4>";
                            echo "<ul>";
                            foreach($data as $d) {
                                $loc=$d['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$d['label'].": ".$d['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($datafactors)) {
                            echo "<h4>Data Factors</h4>";
                            echo "<ul>";
                            foreach($datafactors as $d) {
                                $loc=$d['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$d['label'].": ".$d['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($errors)) {
                            echo "<h4>Errors</h4>";
                            echo "<ul>";
                            foreach($errors as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqndiffs)) {
                            echo "<h4>Experiment Differences (from equation)</h4>";
                            echo "<ul>";
                            foreach($eqndiffs as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqnops)) {
                            echo "<h4>Equation Operators</h4>";
                            echo "<ul>";
                            foreach($eqnops as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqnprops)) {
                            echo "<h4>Equation Properties</h4>";
                            echo "<ul>";
                            foreach($eqnprops as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqnunits)) {
                            echo "<h4>Equation Property Unit</h4>";
                            echo "<ul>";
                            foreach($eqnunits as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqnterms)) {
                            echo "<h4>Equation Terms</h4>";
                            echo "<ul>";
                            foreach($eqnterms as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqnvars)) {
                            echo "<h4>Equation Variables</h4>";
                            echo "<ul>";
                            foreach($eqnvars as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($eqnvarlimits)) {
                            echo "<h4>Equation Variable Limits</h4>";
                            echo "<ul>";
                            foreach($eqnvarlimits as $e) {
                                $loc=$e['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$e['label'].": ".$e['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($meta)) {
                            echo "<h4>Metadata</h4>";
                            echo "<ul>";
                            foreach($meta as $type=>$value) {
                                echo "<li>".$type.": ".$value."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($props)) {
                            echo "<h4>Properties</h4>";
                            echo "<ul>";
                            foreach($props as $p) {
                                $loc=$p['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$p['label'].": ".$p['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($headers)) {
                            echo "<h4>Property Headers</h4>";
                            echo "<ul>";
                            foreach($headers as $h) {
                                $loc=$h['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$h['label'].": ".$h['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($refs)) {
                            echo "<h4>References</h4>";
                            echo "<ul>";
                            foreach($refs as $r) {
                                $loc=$r['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$r['label'].": ".$r['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($supps)) {
                            echo "<h4>Supplemental Data</h4>";
                            echo "<ul>";
                            foreach($supps as $s) {
                                $loc=$s['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$s['label'].": ".$s['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($series)) {
                            echo "<h4>Series</h4>";
                            echo "<ul>";
                            foreach($series as $s) {
                                $loc=$s['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$s['label'].": ".$s['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($sanns)) {
                            echo "<h4>Series Annotations</h4>";
                            echo "<ul>";
                            foreach($sanns as $c) {
                                $loc=$c['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$c['label'].": ".$c['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($sconds)) {
                            echo "<h4>Series Conditions</h4>";
                            echo "<ul>";
                            foreach($sconds as $c) {
                                $loc=$c['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$c['label'].": ".$c['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        if(!empty($setts)) {
                            echo "<h4>Series Settings</h4>";
                            echo "<ul>";
                            foreach($setts as $s) {
                                $loc=$s['location'];
                                $title="Step: ".$loc['step'].", Line: ".$loc['line'].", Block: ".$loc['block'];
                                echo "<li title='".$title."'>(line ".$loc['line'].") ".$s['label'].": ".$s['value']."</li>";
                            }
                            echo "</ul>";
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="tab2primary">
                        <pre class="pre-scrollable"><?php echo htmlentities($tfile['trash']); ?></pre>
                    </div>
                    <div class="tab-pane fade" id="tab3primary">
                        <?php
                        // Checking headers for consistency with series
                        // (some new page headers get included when table splits pages)
                        $hlines=[];
                        foreach($headers as $h) {
                            $loc=$h['location'];
                            $hlines[$loc['line']]=$loc['line'];
                        }
                        if(count($hlines)!=count($series)) {
                            $cerrors[]="Number of table headers does not match # of series";
                        }
                        if(isset($cerrors)) {
                            echo "<h4>Consistency Errors</h4>";
                            echo '<ul>';
                            foreach ($cerrors as $e) {
                                echo "<li><b>".$e."</b></li>";
                            }
                            echo '</ul>';
                        }
                        if(isset($fileerrors['rules'])) {
                            $rules=$fileerrors['rules'];
                            echo "<h4>Rule Errors</h4>";
                            echo '<ul>';
                            foreach ($rules as $e) {
                                echo "<li><b>".$e['id']."</b>: ".$e['issue']." (step ".$e['step'].", line ".$e['line'].")</b><br/></li>";
                            }
                            echo '</ul>';
                        }
                        if(isset($fileerrors['snippets'])) {
                            $snips=$fileerrors['snippets'];
                            echo "<h4>Snippet Errors</h4>";
                            echo '<ul>';
                            foreach ($snips as $e) {
                                echo "<li><b>".$e['id']."</b>: ".$e['issue']." (step ".$e['step'].", line ".$e['line'].", block ".$e['block'].")</b><br/></li>";
                            }
                            echo '</ul>';
                        }?>
                    </div>
                    <div class="tab-pane fade" id="tab4primary">
                        <?php
                        $debug=json_decode($tfile['debug'], true);
                        echo '<ul>';
                        foreach($debug as $step=>$temp) {
                            foreach($temp as $line=>$temp2) {
                                foreach($temp2 as $block=>$meta) {
                                    echo "<li><b>Step: ".$step."/Line: ".$line."/Block: ".$block."</b><br/>";
                                    echo "Info: ".$meta['label']."<br />";
                                    echo "Type: ".$meta['scidata']."<br />";
                                    echo "Format: ".$meta['datatype']."<br />";
                                    echo "Value: ".$meta['value']."<br />";
                                    echo "</li>";
                                }
                            }
                        }
                        echo '</ul>';
                        ?>
                    </div>
                    <div class="tab-pane fade" id="tab5primary">
                        <input type="button" value="Submit Github Issue" id="submitGithubIssue" class="btn btn-primary" style="width: 160px;height: 40px;">
                        <textarea id="githubBody" placeholder="Issue Body Text" rows="5" cols="30" style="display: inline;vertical-align: top;"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5" style="height: 650px;">
        <iframe id="pdfFile" data="<?php echo $pdf; ?>" src="https://docs.google.com/viewer?url=http://chalk.coas.unf.edu<?php echo $pdf; ?>&embedded=true" style="width:500px; height:650px;" frameborder="0"></iframe>
    </div>
</div>