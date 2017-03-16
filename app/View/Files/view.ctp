<?php
//pr($data);exit;
if(!isset($data)) { $data=[]; }
$file=$data['File'];
$pub=$data['Publication'];
$tfiles=$data['TextFile'];
$tfile="";
$sets=$data['Dataset'];
$ptype=$data['Propertytype'];
$rset=$data['Ruleset'];
?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#rulesetid').on('change', function() {
            var rset=$(this).val();
            var fileid=$('#fileid').val();
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
        $('#resolution').on('change', function() {
            var dpi=$(this).val();
            var fileid=$('#fileid').val();
            $.ajax({
                    method: "POST",
                    url: '/springer/files/updatefield/' + fileid,
                    data: { field: "resolution", value: dpi }
                }
            ).done(function (response) {
                return true;
            });

            // Change gettext url
            var get=$('#gettext');
            var ghref=get.attr('href');
            get.attr('href',ghref + '/' + dpi);
            // Change regextest url
            var reg=$('#regextest');
            var rhref=reg.attr('href');
            reg.attr('href',rhref + '/' + dpi);
            return false;
        });
    });
</script>
<?php if($file['filetype']=='pdf') { ?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Uploaded File - <?php echo $file['title']; ?></h3>
            </div>
            <div class="panel-body">
                <div class="col-sm-10">
                    Publication: <?php echo $pub['title'].' ('.$this->Html->link('View',"/publications/view/".$pub['id']).') ('.$this->Html->link('MassProcess',"/textfiles/massprocess/".$pub['id']).')'; ?><br />
                    File Size: <?php echo $file['filesize']; ?> bytes<br />
                    PDF Version: <?php echo $file['pdf_version']; ?><br />
                    Total Systems: <?php echo $file['num_systems']; ?><br />
                    <?php
                    echo $this->Form->input('fileid',['type'=>'hidden','value'=>$data['File']['id']]);
                    $rsets=$this->requestAction('/rulesets/index');
                    echo $this->Form->input('rulesetid',['type'=>'select','label'=>false,'options'=>$rsets,'selected'=>$rset['id'],'empty'=>'Choose...']);
                    ?>
                </div>
                <div class="col-sm-2">
                    <?php
                    $rezs=['72'=>'72dpi','100'=>'100dpi','120'=>'120dpi','140'=>'140dpi','160'=>'160dpi','180'=>'180dpi','200'=>'200dpi','250'=>'250dpi','300'=>'300dpi','400'=>'400dpi','500'=>'500dpi','600'=>'600dpi'];
                    echo $this->Form->input('resolution',['type'=>'select','options'=>$rezs,'selected'=>$file['resolution'],'label'=>'Resolution&nbsp;']);
                    echo $this->Html->link("Download Text File","/files/gettext/".$file['id'],['id'=>'gettext','class'=>'btn btn-success btn-sm btn-block']);
                    echo $this->Html->link("Test Regex","/files/testregex/".$file['id'],['id'=>'regextest','class'=>'btn btn-success btn-sm btn-block']);
                    if($this->Session->read('Auth.User.type')=="admin"||$this->Session->read('Auth.User.type')=="superadmin") {
                        if(count($tfiles)>0) {
                            echo $this->Html->link("Clean File","/files/clean/".$file['id'],['class'=>'btn btn-warning btn-sm btn-block','id'=>'ruleset']);
                        } elseif(count($tfiles)==0) {
                            if(empty($rset['id'])) { $state=" disabled"; } else { $state=""; }
                            echo $this->Html->link("Convert File","/textfiles/add/".$file['id'],['class'=>'btn btn-info btn-sm btn-block'.$state]);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } elseif($file['filetype']=='xml') { ?>
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Uploaded File - <?php echo $file['title']; ?></h3>
            </div>
            <div class="panel-body">
                <div class="col-sm-10">
                    Publication: <?php echo $pub['title'].' ('.$this->Html->link('View',"/publications/view/".$pub['id']).') ('.$this->Html->link('MassProcess',"/textfiles/massprocess/".$pub['id']).')'; ?><br />
                    File Size: <?php echo $file['filesize']; ?> bytes<br />
                    Total Systems: <?php echo $file['num_systems']; ?><br />
                    <?php
                    echo $this->Form->input('xslt',['type'=>'hidden','value'=>$file['id']]);
                    // Get the XSLT files currently uploaded to the VM
                    $dir = new Folder(WWW_ROOT.'files/xslt');
                    $xslts = $dir->find('.*\.xsl');
                    $xsltid=array_search($file['xslt'],$xslts);
                    echo $this->Form->input('xslt',['type'=>'select','label'=>false,'options'=>$xslts,'selected'=>$xsltid,'empty'=>'Choose...']);
                    ?>
                </div>
                <div class="col-sm-2">
                    <?php
                    echo $this->Html->link("Download XML File","/files/getxml/".$file['id'],['id'=>'getxml','class'=>'btn btn-success btn-sm btn-block']);
                    echo $this->Html->link("Download JSON File","/files/getjson/".$file['id'],['id'=>'getjson','class'=>'btn btn-success btn-sm btn-block']);
                    echo $this->Html->link("Test XSLT","/files/testxslt/".$file['id'],['id'=>'xslttest','class'=>'btn btn-success btn-sm btn-block']);
                    if($this->Session->read('Auth.User.type')=="admin"||$this->Session->read('Auth.User.type')=="superadmin") {
                        if(count($tfiles)>0) {
                            echo $this->Html->link("Clean File","/files/clean/".$file['id'],['class'=>'btn btn-warning btn-sm btn-block','id'=>'ruleset']);
                        } elseif(count($tfiles)==0) {
                            if(empty($file['xslt'])) { $state=" disabled"; } else { $state=""; }
                            echo $this->Html->link("Convert File","/textfiles/add/".$file['id'],['class'=>'btn btn-info btn-sm btn-block'.$state]);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<div class="row">
    <div class="col-sm-6">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">Text Files (<?php echo count($tfiles); ?>)</h3>
            </div>
            <div class="list-group" style="max-height: 400px;overflow-y:scroll;">
                <?php
                foreach($tfiles as $tfile) {
                    echo $this->Html->link("• ".$tfile['title'],'/textfiles/view/'.$tfile['id'],['class'=>'list-group-item']);
                }
                ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Datasets (<?php echo count($sets); ?>)</h3>
            </div>
            <div class="list-group" style="max-height: 400px;overflow-y:scroll;">
                <?php
                foreach($sets as $set) {
                    echo $this->Html->link("• ".$set['title'],'/datasets/view/'.$set['id'],['class'=>'list-group-item']);
                }
                ?>
            </div>
        </div>
    </div>
</div>