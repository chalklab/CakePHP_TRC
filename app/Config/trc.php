<?php
// Store all project configuration parameters here

$config['server']="https://sds.coas.unf.edu";
$config['path']="/trc";
$config['filepath']="files/trc/";

$config['filetypes']=['pdf'=>'PDF','txt'=>'Text','xml'=>'XML','html'=>'HTML'];

$config['journal']['abbrevs']=[
    'Thermochim. Acta'=>'tca',
    'J. Chem. Thermodyn.'=>'jct',
    'Int. J. Thermophys.'=>'ijt',
    'J. Chem. Eng. Data'=>'jced',
    'Fluid Phase Equilib.'=>'fpe'
];
// Jmol configuration parameters
$config['jmol']['j2spath']=$config['path']."/js/jsmol/j2s";
$config['jmol']['color']="#E0E0E0";
$config['jmol']['height']=190;
$config['jmol']['width']=190;

// online data repository settings
$config['cir']['url']="https://cactus.nci.nih.gov/chemical/structure/<id>/file?format=sdf&get3d=True";
$config['pc']['url']="https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/<id>/SDF";

// labels for identifier types
$config['identlabels']=['inchi'=>'InChI String','inchikey'=>'InChi Key','casrn'=>'CASRN','smiles'=>'SMILES','pubchemId'=>'PubChem ID','chemspiderId'=>'ChemSpider ID']?>
