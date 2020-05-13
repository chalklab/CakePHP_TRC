<?php
// Displays an iframe with content
// Incoming variables: $url (required)
?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h2 class="panel-title">Regex101</h2>
            </div>
            <div class="panel-body embed-responsive embed-responsive-16by9">
                <iframe class="embed-responsive-item" src="<?php echo $url; ?>"></iframe>
            </div>
        </div>
    </div>
</div>