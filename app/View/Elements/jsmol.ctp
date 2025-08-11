<?php
// Jmol script element
// Variables - $color, $height, $width, $name, $casrn, $inchi, $inchikey, $uid
if(!isset($color))  { $color=Configure::read('jmol.color'); }
if(!isset($height)) { $height=Configure::read('jmol.height'); }
if(!isset($width))  { $width=Configure::read('jmol.width'); }
// Get molecule at CIR via id that works
$valid=false;
if(isset($pubchemid)) {
	$url=str_replace("<id>",$pubchemid,Configure::read('pc.url'));
	$hdrs=get_headers($url,true);
	if(stristr($hdrs[0],"OK")) { $valid=true; }
	debug($hdrs);exit;
}
if(isset($inchikey)&&!$valid) {
    $url=str_replace("<id>",$inchikey,Configure::read('cir.url'));
    $hdrs=get_headers($url,true);
    if(stristr($hdrs[0],"OK")) { $valid=true; }
}
if(isset($inchi)&&!$valid) {
    $url=str_replace("<id>",$inchi,Configure::read('cir.url'));
    $hdrs=get_headers($url,true);
    if(stristr($hdrs[0],"OK")) { $valid=true; }
}
if(isset($casrn)&&!$valid) {
    $url=str_replace("<id>",$casrn,Configure::read('cir.url'));
    $hdrs=get_headers($url,true);
    if(stristr($hdrs[0],"OK")) { $valid=true; }
}
if(isset($name)&&!$valid) {
    $url=str_replace("<id>",$name,Configure::read('cir.url'));
    $hdrs=get_headers($url,true);
    if(stristr($hdrs[0],"OK")) { $valid=true; }
}
// set the absolute path to jsmol
$j2spath=Configure::read('jmol.j2spath');
?>

<script type='text/javascript'>
    $(document).ready(function(){
        $("#<?php echo "jsmol".$uid; ?>").html(Jmol.getAppletHtml("jsmol<?php echo $uid; ?>", Info<?php echo $uid; ?>));
    });

    jmol_isReady = function(applet) {
        Jmol._getElement(applet, "appletdiv").style.border = "1px solid #D0D0D0"
    }

    Info<?php echo $uid; ?> = {
        width: "<?php echo $width; ?>",
        height: "<?php echo $height; ?>",
        src: "<?php echo $url; ?>",
        debug: false,
        color: "<?php echo $color; ?>",
        addSelectionOptions: false,
        serverURL: "https://trc.stuchalk.domains.unf.edu/js/jsmol/php/jsmol.php",
        use: "HTML5",
        coverImage: "",
        coverScript: "",
        deferApplet: false,
        deferUncover: false,
        jarPath: "java",
        j2sPath: "<?php echo $j2spath; ?>",
        jarFile: "JmolApplet.jar",
        isSigned: false,
        readyFunction: jmol_isReady
    }
    <?php
    //echo "var Info".$uid." = { color: '".$color."', height: ".$height.", width: ".$width.", src: '".$url."', use: 'HTML5', j2sPath: '".$j2spath."' };\n";
    //echo "Jmol.getTMApplet('chem".$uid."', Info".$uid.");\n";
    ?>
</script>
<div id="jsmol<?php echo $uid; ?>" style="width: 100%;height: 100%;"></div>
