<?php
// Framed Jmol Script Element
// In a view file use the following command to display
// $this->element('molspectra',['name'='???','index'=>?,"inchikey"=>"???",'spectra'=>Array,'height'=>value,'width'=>value])
// $index (the index of this viewer on the page - allows id of jmol applet to be unique )
// $name (name of compound)
// $id (id of compound}
// $inchikey (inchikey of compound)
// $spectra (array of id/name pairs of the spectra)
// $height of div
// $width of div
// $named (show/hide compound name)
// fontsize of links to external websites
// links (show/hide external links)
if(!isset($height))     { $height=260; }
if(!isset($width))      { $width="100%"; }
if(!isset($spectra))    { $spectra=[]; }
if(!isset($named))      { $named=true; }
if(!isset($name))       { $name=''; }
if(!isset($system))     { $system=false; }
if(!isset($fontsize))   { $fontsize=11; }
if(!isset($links))      { $links=true; }
if(!isset($index))      { $index=0; }
if(!isset($cols))       { $cols=12; }
if(!isset($inchi))      { $inchi=""; }
if(!isset($casrn))      { $casrn=""; }

// Chemicals
if(isset($inchikey))
{
    echo "<div id='chemical".$index."' class='chemical col-md-".$cols."' style='padding: 0 5px;'>";
    // show jsmol
	$opts=['uid'=>$index,'height'=>$height,'width'=>$width,'inchikey'=>$inchikey,'inchi'=>$inchi,'casrn'=>$casrn,'name'=>$name];
    echo $this->element('jsmol',$opts);

	// show chemical name
    if($named) {
        if($system==true&&isset($name)) {
            $name=$this->Html->link($name,'/substances/view/'.$name);
        }
        echo "<div style='text-align: center;margin-top: 5px;'>".$name."</div>";
    }

    // show links on other sites
    if($links) {
        echo "<div style='text-align: center;margin-bottom: 0;font-size: ".$fontsize."px;'>View @ ";
        echo $this->Html->link('CommonChemistry','/identifiers/ccbykey/'.$inchikey,['target'=>'_blank'])." ";
        echo $this->Html->link('NIST','http://webbook.nist.gov/cgi/cbook.cgi?InChI='.$inchikey,['target'=>'_blank'])." ";
        echo $this->Html->link('PubChem','https://pubchem.ncbi.nlm.nih.gov/compound/'.$inchikey,['target'=>'_blank']);
        echo "</div>";
    }
    echo "</div>";
}
