<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Data</h2>
            </div>
            <div class="panel-body">
                <ul class="list-unstyled">
                    <?php
                    foreach ($data as $quantity=>$values) {
						echo "<li>".$quantity."<ul>";
						foreach($values as $id=>$value) {
							echo "<li>".$this->Html->link($value,'/data/view/'.$id)."</li>";
						}
                        echo "</ul></li>";
					}
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>
