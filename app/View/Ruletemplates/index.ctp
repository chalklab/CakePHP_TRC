<?php
//pr($tmpls);exit;
if(!isset($tmpls)) { $tmpls=[]; };
?>
<script type="application/javascript">
    $(document).ready(function() {
        $('.tmpl').on('click', function() {
            // Reset everything
            $('.regexcell').css('background-color','transparent');
            $('.mark').attr('contenteditable','false');
            $('.mark').css('background-color','lightgreen');
            $('.mark').css('font-size','14px');
            // Process
            var tmplid=$(this).attr('data-tmplid');
            $('#'+tmplid).attr('contenteditable','true');
            $('#'+tmplid).parents('td').css('background-color','#cccccc');
            $('#'+tmplid).css('background-color','#ffffff');
            $('#'+tmplid).css('font-size','18px');
            return false;
        });
        $('.mark').on('blur', function() {
            var id=$(this).attr('id');
            var oldtmpl=$('#' + id + 'old').text();
            var newtmpl=$(this).text();
            if(newtmpl!=oldtmpl) {
                // If the snippet has changed then save and update the old div
                var dbid=$(this).attr('data-id');
                $.ajax({
                        method: "POST",
                        url: '/springer/ruletemplates/updatetmpl/' + dbid,
                        data: { regex: newtmpl }
                    }
                ).done(function (response) {
                    if(response) {
                        $('#' + id +'old').text(newtmpl);
                        alert("Template updated...");
                    } else {
                        alert("An error occured!");
                    }
                });
            }
            $('.regexcell').css('background-color','transparent');
            $('.mark').attr('contenteditable','false');
            $('.mark').css('background-color','lightgreen');
            $('.mark').css('font-size','14px');
            return false;
        });
    });
</script>
<h1>Rule Templates
    <a href="add" class="btn btn-success btn-sm pull-right">Add Rule Template</a>
    <a href="/springer/rulesnippets/add" class="btn btn-info btn-sm pull-right">Add Rule Snippet</a>
</h1>
<div class="col-sm-12" style="max-height: 650px;overflow-y:scroll;">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>Template</th>
                <th<?php if($this->Session->read('Auth.User.type')=="admin") { echo ' colspan="2"'; } ?>>Regex</th>
                <th>Comment</th>
                <th>Updated</th>
            </tr>
            </thead>
            <?php foreach($tmpls as $idx=>$t) {
                $tmpl=$t['Ruletemplate'];
                ?>
                <tr>
                    <td><?php echo $this->Html->link($tmpl['name'],'/ruletemplates/view/'.$tmpl['id']); ?></td>
                    <td class="regexcell">
                        <?php
                        echo "<mark id='tmpl".$tmpl['id']."' title='".$tmpl['name']."' class='mark' data-id='".$tmpl['id']."' alt='".$tmpl['name']."'>".$tmpl['regex']."</mark>";
                        echo "<div id='tmpl".$tmpl['id']."old' style='display: none;'>".$tmpl['regex']."</div>";
                        ?>
                    </td>
                    <?php
                    if($this->Session->read('Auth.User.type')=="admin") {
                        ?>
                        <td>
                            <a href="#" class="tmpl btn btn-success btn-sm pull-right" data-tmplid="<?php echo "tmpl".$tmpl['id']; ?>">Edit</a>
                        </td>
                        <?php
                        }
                    ?>
                    <td><?php echo $tmpl['comment']; ?></td>
                    <td><?php echo $tmpl['updated']; ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>