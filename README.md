## CakePHP Website to Process NIST TRC ThermoML Data
This CakePHP website was created to ingest thermophysical property data in the IUPAC ThermoML XML format from [https://trc.nist.gov/ThermoML.html](https://trc.nist.gov/ThermoML.html) into a relational database (MySQL).  The intent was to create a REST website where the data could be searched, viewed and download in other formats, including SciData JSON-LD.

Subsequently, the code was updated to convert the script that created the SciData JSON-LD to use a library class (a model file in CakePHP) that could be used for other projects.
