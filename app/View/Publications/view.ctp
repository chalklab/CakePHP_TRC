<?php
//pr($data);exit;
?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#showuploaded').on('click', function () {
            $('.tr').hide();
            $('.extract').each(function () {
                $(this).parents(".tr").show();
            });
            $('#showuploaded').hide();
            $('#showall').show();
        });
        $('#showerrors').on('click', function () {
            $('.tr').hide();
            $('.errors').each(function () {
                $(this).parents(".tr").show();
            });
            $('#showerrors').hide();
            $('#showall').show();
        });
        $('#showall').on('click', function () {
            $('.tr').show();
            $('#showerrors').show();
            $('#showuploaded').show();
            $('#showall').hide();
        });
        $('#selectall').on('click',function() {
            var boxes=$('.process');
            boxes.each(function () {
                if($(this).is(':visible')) {
                    $(this).prop('checked',true);
                }
            });
            $('#selectall').hide();
            $('#unselectall').show();
        });
        $('#unselectall').on('click',function() {
            var boxes=$('.process');
            boxes.each(function () {
                $(this).prop('checked',false);
            });
            $('#unselectall').hide();
            $('#selectall').show();
        });
        $('#extractall').on('click',function() {
            var tfiles=$('input.process:checked');
            tfiles.each(function () {
                var id=$(this).parents('tr').find('a.extract').attr('data-id');
                extract(id);
            });
        });
        $('#cleanall').on('click',function() {
            var tfiles=$('a.clean');
            tfiles.each(function () {
                if($(this).is(':visible')) {
                    var id=$(this).attr('data-id');
                    clean(id);
                }
            });
        });
        $('.ruleset').on('change', function() {
            var rset=$(this).val();
            var fileid=$(this).attr('data-id');
            $.ajax({
                    method: "POST",
                    url: '/springer/files/updatefield/' + fileid,
                    data: { field: "ruleset_id", value: rset }
                }
            ).done(function (response) {
                if(!response) {
                    alert("Ruleset change failed");
                }
                return false;
            });
        });
        $('.extract').on('click', function () {
            var id = $(this).attr('data-id');
            extract(id);
            return false;
        });
        $('.clean').on('click', function () {
            var id = $(this).attr('data-id');
            clean(id);
            return false;
        });
        function clean(id) {
            $.ajax({
                    method: "POST",
                    url: '/springer/files/clean/' + id,
                    async: false,
                    dataType: 'json'
                }
            ).done(function (r) {
                if (r.hasOwnProperty('status')) {
                    if (r.status == "success") {
                        $('#status' + id).text('uploaded');
                        var html='<a href="#" class="extract" data-id="' + id + '">Extract</a>';
                        $('#extract' + id).empty().append(html);
                        $('#checkbox' + id).show();
                    }
                }
            });
        }
        function extract(id) {
            $.ajax({
                    method: "POST",
                    url: '/springer/textfiles/add/' + id,
                    async: false,
                    dataType: 'json'
                }
            ).done(function (r) {
                var cellid = $('#extract' + id);
                cellid.empty();
                if (r.hasOwnProperty('status')) {
                    if (r.status == "success") {
                        var tfid = r.id;
                        var title = r.title;
                        var errors = r.errors;
                        var rcount, scount;
                        if (r.hasOwnProperty('errors.rules')) {
                            rcount = errors.rules.length;
                        } else {
                            rcount = 0;
                        }
                        if (r.hasOwnProperty('errors.snippets')) {
                            scount = errors.snippets.length;
                        } else {
                            scount = 0;
                        }
                        var ecount = rcount + scount;
                        var string=title.replace("'","&apos;");
                        cellid.html("<span title='" + string + "'>" + tfid + "</span> (" + ecount + " errors)");
                    } else if (r.status == "error") {
                        cellid.html(r.message);
                    }
                    $('#status' + id).text('extracted');
                    $('#checkbox' + id).attr('checked',false);
                    $('#checkbox' + id).hide();
                } else {
                    cellid.html("Unknown error\n" + r);
                }
            }).fail(function (response) {
                $('#extract' + id).text(response);
            });


        }
    });
</script>
<style type="text/css">
    .showReports:hover {
        text-decoration: underline;
        cursor: pointer;
    }
    span {
        color: green;
    }
</style>
<?php
$pub = $data['Publication'];
$rsets=$this->requestAction('/rulesets/index');
?>
<!-- Publication metadata -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"><?php echo $pub['title']; ?></h2>
            </div>
            <div class="panel-body">
                <div class="col-sm-10">
                    <p><?php echo $pub['description']; ?></p>
                    <p>
                        ISBN: <?php echo $pub['isbn']; ?><br>
                        eISBN: <?php echo $pub['eisbn']; ?><br>
                        Number of data files: <?php echo $pub['total_files']; ?>
                    </p>
                </div>
                <div class="col-sm-2">
                    <?php
                    if ($this->Session->read('Auth.User.type') == 'admin') {
                        echo $this->Html->link("Add File","/files/add/",['class'=>'btn btn-success btn-sm btn-block']);
                        echo $this->Html->link("Clean Publication","/publications/clean/".$pub['id'],['class'=>'btn btn-warning btn-sm btn-block']);
                        echo $this->Html->link("Delete Publication","/publications/delete/".$pub['id'],['class'=>'btn btn-danger btn-sm btn-block']);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Search systems in the publication -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Publication Files
                    <span id="showall" class="btn btn-xs btn-success pull-right" style="width: 100px;display: none;">Show all</span>
                    <span id="showuploaded" class="btn btn-xs btn-warning pull-right" style="width: 100px;">Show uploaded</span>
                    <span id="showerrors" class="btn btn-xs btn-danger pull-right" style="width: 100px;">Show errors</span>
                    <span id="selectall" class="btn btn-xs btn-success pull-right" style="width: 100px;">Select all</span>
                    <span id="unselectall" class="btn btn-xs btn-success pull-right" style="width: 100px;display: none;">Unselect all</span>
                    <span id="extractall" class="btn btn-xs btn-info pull-right" style="width: 100px;">Extract selected</span>
                    <span id="cleanall" class="btn btn-xs btn-danger pull-right" style="width: 100px;">Clean all</span>
                </h3>
            </div>
            <div class="panel-body" style="max-height: 386px;overflow-y: scroll;">
                <table id="filesTable" class="table table-striped">
                    <thead>
                    <tr>
                        <th>Process?</th>
                        <th>File</th>
                        <th>Ruleset</th>
                        <th>Status</th>
                        <th>Text File</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data['File'] as $file) { ?>
                        <tr class="tr">
                        <td class="col-sm-1">
                            <input id="checkbox<?php echo $file['id']; ?>" class="process" style="width: auto;<?php echo (in_array($file['status'],["extracted","ingested","completed"])) ? "display: none;" : ""; ?>" type="checkbox">
                        </td>
                        <td class="col-sm-4">
                            <?php
                            echo $this->Html->link($file['title'],'/files/view/'.$file['id']);
                            echo " (".$this->Html->link("Clean","#",['class'=>'clean','data-id'=>$file['id']]).")";
                            ?>
                        </td>
                        <td class="col-sm-1">
                            <?php
                            if(!empty($file['Ruleset'])) {
                                $set=$file['Ruleset'];
                                echo "<span title='".$set['name']."'>".$set['id']."</span>";
                            } else {
                                echo $this->Form->input('ruleset_id',['type'=>'select','label'=>false,'options'=>$rsets,'class'=>'ruleset col-md-12','data-id'=>$file['id'],'empty'=>'Choose...']);
                            }
                            ?>
                        </td>
                        <td class="col-sm-1" id="status<?php echo $file['id']; ?>">
                            <?php echo $file['status']; ?>
                        </td>
                        <td class="col-sm-5" id="extract<?php echo $file['id']; ?>">
                            <?php
                            if(empty($file['TextFile'])) {
                                echo $this->Html->link("Extract","#",['class'=>'extract','data-id'=>$file['id']]);
                            } else {
                                foreach($file['TextFile'] as $i=>$tfile) {
                                    echo "<span title='".str_replace("'","&apos;",$tfile['title'])."'>".$tfile['id']."</span>";
                                    if($tfile['errors']=="[]") {
                                        echo " (0 errors)";
                                    } else {
                                        $errors=json_decode($tfile['errors'],true);
                                        $rcount=$scount=0;
                                        if(isset($errors['rules'])) {
                                            $rcount=count($errors['rules']);
                                        }
                                        if(isset($errors['snippets'])) {
                                            $scount=count($errors['snippets']);
                                        }
                                        $ecount=$rcount+$scount;
                                        echo " (".$this->Html->link($ecount." errors",'/textfiles/view/'.$tfile['id'],['class'=>'errors']).")<br />";
                                    }
                                    if($i!=count($file['TextFile'])-1) { echo "<br />"; }
                                }
                            }
                            echo "<br />";
                            ?>
                        </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>