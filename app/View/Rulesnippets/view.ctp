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
            $('#'+snipid).css('background-color','gold');
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
<?php
// Variables from controller: $data
$s=$data['Rulesnippet'];
$m=$data['Metadata'];
$p=$data['Property'];
$u=$data['Unit'];
//debug($u);
?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">Rule Snippet - <?php echo $s['name']; ?></h3>
            </div>
            <div class="panel-body">
                SciData Category: <?php echo $s['scidata']; ?><br />
                <?php
                if(!is_null($s['metadata_id'])) {
                    echo "Metadata Type: ".$m['name']."<br />(".$m['description'].")"."<br />";
                }
                ?>
                Mode: <?php echo $s['mode']; ?><br />
                Regex:
                <?php
                echo "<mark id='snip".$s['id']."' title='".$m['name']."' class='mark' data-id='".$s['id']."' alt='".$m['name']."'>".$s['regex']."</mark>";
                echo "<div id='snip".$s['id']."old' style='display: none;'>".$s['regex']."</div>";
                if($this->Session->read('Auth.User.type')=="admin"||$this->Session->read('Auth.User.type')=="superadmin") {
                    ?>
                    <a href="#" class="snip btn btn-success btn-xs " data-snipid="<?php echo "snip".$s['id']; ?>">Edit</a>
                    <?php
                }
                ?><br />
                <?php
                if(!is_null($s['property_id'])) {
                    echo "Property: ".$p['name']."<br />";
                }
                ?>
                <?php
                if(!is_null($s['unit_id'])) {
                    echo "Unit: ".$u['name']." (".$u['symbol'].")"."<br />";
                }
                ?>
                Example: <?php echo htmlentities($s['example'],ENT_QUOTES, "UTF-8"); ?><br />
                Comment: <?php echo $s['comment']; ?>
            </div>
        </div>
    </div>
</div>
<?php
if(!empty($s['url'])&&!is_null($s['url'])) {
    echo $this->element('regex101',['url'=>$s['url']]);
}
?>