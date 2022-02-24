<?php
echo "<h4>Unit of Measurement</h4>";
echo "The output below shows an example of a data model sent to a view. See the 'UnitController.php' file for the function 'view'.<br/>";
echo "The ouptut does not include related data in the 'conditions','data', or 'sampleprops' tables due to the size of data that could be retrieved.<br/>";
echo "<em>NOTE: If either the Quantity or Quantitykind sections do not contain data the unit is not associated with the respective model.</em><br/><br/>";
pr($data);
