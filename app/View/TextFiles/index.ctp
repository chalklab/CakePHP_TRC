<h2>Text Files</h2>

<?php
foreach ($pubs as $pid => $pubtitle) {
if (isset($files[$pid])) { ?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"> <?php echo "<h3>" . $pubtitle . "</h3>"; ?></h2>
            </div>
            <div class="panel-body">
                <?php echo "<a href='#' class='hideFiles'>Hide Files</a>";
                echo "<ul>";
                foreach ($files[$pid] as $fid => $filename) {
                    if (isset($data[$fid])) {

                        foreach ($data[$fid] as $vid => $version) {
                            echo "<li>" . $this->Html->link($filename, '/textfiles/view/' . $vid) . ' (v' . $version . ')';
                        }
                        if (!isset($dataset[$fid])) {
                            echo ' (' . $this->Html->link('Ingest', '/datarectification/ingest/' . $fid) . ')</li>';
                        }
                    }
                }
    echo "</ul>";
                } ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>


<div class="row">
    <div class="col-sm-12">
        <div class="panel text-center">
            <?php echo $this->Html->link("Add New Text File", ['action' => 'add']); ?>
            <br>
            <script type="application/javascript">
                $("body").on("click", ".hideFiles", function (e) {
                    if ($(this).text() == "Hide Files") {
                        $(this).html("Show Files");
                        $(this).next().hide();
                    } else {
                        $(this).html("Hide Files");
                        $(this).next().show();
                    }
                })
            </script>
        </div>
    </div>
</div>