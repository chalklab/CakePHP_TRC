<?php
//pr($snips);exit;
if(!isset($snips)) { $snips=[]; };
?>
<script type="application/javascript">
    $(document).ready(function() {
        $('.snip').on('click', function() {
            // Reset everything
            $('.regexcell').css('background-color','transparent');
            $('.mark').attr('contenteditable','false');
            $('.mark').css('background-color','lightgreen');
            $('.mark').css('font-size','14px');
            // Process
            var snipid=$(this).attr('data-snipid');
            $('#'+snipid).attr('contenteditable','true');
            $('#'+snipid).parents('td').css('background-color','#cccccc');
            $('#'+snipid).css('background-color','#ffffff');
            $('#'+snipid).css('font-size','18px');
            return false;
        });
        $('.mark').on('blur', function() {
            var id=$(this).attr('id');
            var oldsnip=$('#' + id + 'old').text();
            var newsnip=$(this).text();
            if(newsnip!=oldsnip) {
                // If the snippet has changed then save and update the old div
                var dbid=$(this).attr('data-id');
                $.ajax({
                    method: "POST",
                    url: '/springer/rulesnippets/updatesnip/' + dbid,
                    data: { regex: newsnip }
                    }
                ).done(function (response) {
                    if(response) {
                        $('#' + id +'old').text(newsnip);
                        alert("Snippet updated...");
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
<h1>Rule Snippets <a href="add" class="btn btn-success btn-sm pull-right">Add Snippet</a></h1>
<div class="col-sm-12" style="max-height: 650px;overflow-y:scroll;">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>Snippet</th>
                <th>Scidata</th>
                <th>Regex</th>
                <th>Mode</th>
                <th>Example</th>
                <th>Updated</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($snips as $idx=>$s) {
                $snip=$s['Rulesnippet'];
                $meta=$s['Metadata'];
                ?>
                <tr class="">
                    <td><?php echo $this->Html->link($snip['name'],'/rulesnippets/view/'.$snip['id']); ?></td>
                    <td><?php echo $snip['scidata']; ?></td>
                    <td class="regexcell">
                        <?php
                        echo "<mark id='snip".$snip['id']."' title='".$meta['name']."' class='mark' data-id='".$snip['id']."' alt='".$meta['name']."'>".$snip['regex']."</mark>";
                        echo "<div id='snip".$snip['id']."old' style='display: none;'>".$snip['regex']."</div>";
                        if($this->Session->read('Auth.User.type')=="admin"||$this->Session->read('Auth.User.type')=="superadmin") {
                            ?>
                            <a href="#" class="snip btn btn-success btn-sm pull-right" data-snipid="<?php echo "snip".$snip['id']; ?>">Edit</a>
                            <?php
                        }
                        ?>
                    </td>
                    <td><?php echo $snip['mode']; ?></td>
                    <td><?php echo $snip['example']; ?></td>
                    <td><?php echo substr($snip['updated'],0,10); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>