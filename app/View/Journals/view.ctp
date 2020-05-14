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
$pub = $data['Journal'];
//$rsets=$this->requestAction('/rulesets/index');
?>
<!-- Journal metadata -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"><?php echo $pub['name']; ?></h2>
            </div>
            <div class="panel-body">
                <div class="col-sm-15">
                    <p>
                    Publisher: <?php echo $pub['publisher']; ?></br>
                    Code: <?php echo $pub['code']; ?></br>
                    Coden: <?php echo $pub['coden']; ?></br>
                    ISSN: <?php echo $pub['issn']; ?></br>
                    Number of data files: <?php echo $pub['total']; ?></br>
                    Doiprefix: <?php echo $pub['doiprefix']; ?></br>
                    Homepage: <?php echo $pub['homepage']; ?></br>
                    </p>
                </div>
                <div class="col-sm-2">
                    <?php
                    if ($this->Session->read('Auth.User.type') == 'admin') {
                        echo $this->Html->link("Add File","/files/add/",['class'=>'btn btn-success btn-sm btn-block']);
                        echo $this->Html->link("Clean Journal","/journals/clean/".$pub['id'],['class'=>'btn btn-warning btn-sm btn-block']);
                        echo $this->Html->link("Delete Journal","/journals/delete/".$pub['id'],['class'=>'btn btn-danger btn-sm btn-block']);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

