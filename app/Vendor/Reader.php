<?php

/**
 * Created by PhpStorm.
 * User: John Turner
 * Date: 1/15/2015
 * Time: 10:04 AM
 *
 * Updated for CakePHP  - Stuart Chalk 021615
 */
class Reader {

    // Holds the config of what to look for in this file
    protected $config;

    // Holds the current file pointer being read
    protected $file;

    // Current line
    public $line=0;

    // Current line in the config array
    public $ruleLine;

    // Current position in the config array
    public $rulePosition=1;

    public $advancement;

    public $lineAdvancement;

    public $headers;

    public $anomalies=array();

    public $indexStart=0;

    public $debug = false;

    public $currentColumns=1;


    /**
     * Constructor for new class instance
     */
    function __construct(){}

    /**
     * Sets the config for the file being read
     * @param $config
     */
    function SetConfig($config)
    {
        $this->config=$config;
    }

    /**
     * Loads the file given into the file pointer or returns new exception
     * @param $filename
     * @throws Exception
     */
    function LoadFile($filename)
    {
        if (file_exists($filename)) {
            $this->file = fopen($filename, 'r');
            $this->line=0;
        } else {
            throw new Exception($filename.": File Not Found");
        }
    }

    /**
     * Loads the file given into the file pointer or returns new exception
     * @param $stream
     * @throws Exception
     */
    function setStream($stream)
    {
        if ($stream) {
            $this->file = $stream;
            $this->line=0;
        } else {
            throw new Exception(" Stream invalid");
        }
    }

    /**
     * Loads the file given into $str and then uses the replacement array to fix special characters
     * @param $text
     * @param $replacementArray
     * @returns string $text
     * @throws Exception
     */
    function FixCharacters($text,$replacementArray)
    {
        foreach($replacementArray as $index=>$replacement) {
            $text=str_replace($index,$replacement,$text); //replace each index with the replacement
        }
        return $text;
    }

    /**
     * Read a line in the file
     * @param int $lineStart
     * @return array|bool
     * @throws Exception
     */
    function ReadFile($lineStart=0)
    {
        // Set all of the things to defaults
        $this->ruleLine=1;
        $results=array();
        $this->advancement=1;
        $this->lineAdvancement=1;
        $this->headers=array();
        $this->indexStart=0;

        $end = false;
        //If debug variable is set then display a buffer to push the debug information down and the ruleset
        if($this->debug == true){
            ?>
                <div style="height: 100px">
                    test
                </div>
            <?php
            echo "<pre>";
            print_r($this->config);
            echo "</pre>";
        }

        $interations = 0;
        if ($this->file) { // Make sure the file buffer we are using is valid
            while (($buffer = fgets($this->file)) !== false && !$end) { //While its valid and contains more lines and we have not signaled the end, get a line and process it
                //echo $buffer."<br />";
                $this->line++;
                if ($this->line<$lineStart&&$lineStart!=0) {    //if the line we want to start at it greater than the current line, throw away the current line and get the next
                    continue;
                }

                if ($buffer===""&&isset($this->config['skipblank'])&&$this->config['skipblank']==true) { //if the line is blank and we ask to skip blank lines, get the next line
                    continue;
                }

                if (!isset($this->config['Rules'][$this->ruleLine])) {  //If we run out of rules in the ruleset signal the end
                    $end = true;
                    if($this->debug == true){
                        ECHO "NO MORE RULE LINES";
                    }
                    break;
                }
                if($interations >100000){
                    $end = true;
                    if($this->debug == true){
                        ECHO "We are in an infinite loop";
                    }
                    break;
                }
                $this->rulePosition=1;
                while($this->rulePosition<count($this->config['Rules'][$this->ruleLine])+1) { //If the next rule position is less than the number of rules in this line, get it
                    $matches = array();
                    $count=0;
                    $didMatch=false;
                    if($this->rulePosition==0) { //Make sure the rule position is never 0
                        $this->rulePosition=1;
                    }
                    $count = 0;
                    if($this->debug == true) { //If debug is enabled, show the current line number, rule line number, rule position, multiplier value, and line buffer
                        ?>
                        <pre style="background-color: #1c94c4"> <?php
                        Echo "Line: " . $this->line . "<br>Line Rule: " . $this->ruleLine . "<br>Line Position: " . $this->rulePosition . "<br>";
                        if (isset($this->config['Rules'][$this->ruleLine][$this->rulePosition]['pattern']))
                            Echo "Pattern:" . $this->config['Rules'][$this->ruleLine][$this->rulePosition]['pattern'] . " <br>";
                        Echo "Multi: " . $this->indexStart . "<br>Input: " . $buffer . "<br>";
                        echo "</pre>";
                    }


                    if (isset($this->config['Rules'][$this->ruleLine][$this->rulePosition]['pattern'])) { //if the rule has a pattern attempt regular expression
                        $matchMethod = "preg_match_all"; //default to preg_match_all
                        if(isset($this->config['Rules'][$this->ruleLine][$this->rulePosition]['matchMethod'])) {
                            $matchMethod= $this->config['Rules'][$this->ruleLine][$this->rulePosition]['matchMethod'];
                        };

                        switch ($matchMethod) { //preform the requested regular expression match and get the matches and result back
                            case "preg_match":
                                $didMatch = preg_match("!" . $this->config['Rules'][$this->ruleLine][$this->rulePosition]['pattern'] . "!", $buffer, $matches);
                                break;
                            // New match method SJC
                            case "preg_replace":
                                $didMatch = preg_replace("!" . $this->config['Rules'][$this->ruleLine][$this->rulePosition]['pattern'] . "!", $buffer, $matches);
                                break;
                            case "preg_match_all":
                            default:
                                $didMatch = preg_match_all("!" . $this->config['Rules'][$this->ruleLine][$this->rulePosition]['pattern'] . "!", $buffer, $matches);
                                break;
                        }

                        if($this->debug == true) { //If debug is on, show what matches we found
                            echo "<pre>";
                            print_r($matches);
                            echo "</pre>";
                        }

                        // If we matched anything then handle that, else handle the failure
                        if ($didMatch) {
                            $interations++;
                            if($interations >100000){
                                $end = true;
                                if($this->debug == true){
                                    ECHO "We are in an infinite loop";
                                }
                                break;
                            }
                            if($this->debug == true) { //If debug is on, show what matches we found
                                echo "<pre>";
                                echo "Performing:".$this->config['Rules'][$this->ruleLine][$this->rulePosition]['action'];
                                echo "</pre>";
                            }
                            $matchIndex = 0;
                            if(isset($this->config['Rules'][$this->ruleLine][$this->rulePosition]['matchIndex'])) //make sure we check if the number of matches equals the expected number
                                $matchIndex = (int)$this->config['Rules'][$this->ruleLine][$this->rulePosition]['matchIndex'];
                            if(!is_numeric($matchIndex) || ($matchIndex<=count($matches)||(isset($matchIndex[1]) && $matchIndex <=count($matchIndex[1])))) {
                                // Method of calling a variable method name
                                $count = $this->{"handle_" . $matchMethod}($buffer, $results, $matches); //handle the success and get back a break count
                            }else{
                                $count=$this->handleFailure($buffer,$results); //handle the failure and get back a break count
                            }
                        } else {
                            if($this->debug == true) { //If debug is on, show what matches we found
                                echo "<pre>";
                                echo "Performing:".$this->config['Rules'][$this->ruleLine][$this->rulePosition]['failure'];
                                echo "</pre>";
                            }
                            $interations++;
                            if($interations >100000){
                                $end = true;
                                if($this->debug == true){
                                    ECHO "We are in an infinite loop";
                                }
                                break;
                            }
                            $count=$this->handleFailure($buffer,$results); //handle the failure and get back a break count
                        }
                    }else if (isset($this->config['Rules'][$this->ruleLine][$this->rulePosition]['action'])) {
                        $interations++;
                        if($interations >100000){
                            $end = true;
                            if($this->debug == true){
                                ECHO "We are in an infinite loop";
                            }
                            break;
                        }
                        $count = $this->standard_switch($this->config['Rules'][$this->ruleLine][$this->rulePosition]['action']); // No match method, perform the default action
                    }
                    switch ($count){ //based on the value of count, break either 1 2 3 or 3 and end
                        case "END":
                            $end = true;
                            break 3;
                        case 2:
                            continue 3;
                        case 1:
                            continue 2;
                        default:
                            break;
                    }
                    $this->rulePosition += $this->advancement; // after were done processing this rule advance to the next rule and reset the advancement to 1 if it was changed
                    $this->advancement = 1;
                }
                $this->ruleLine+=$this->lineAdvancement; // after were done processing this line advance to the next line and reset the advancement to 1 if it was changed
                $this->lineAdvancement=1;
            }
            if (!feof($this->file)&& !$end) { //If we stopped for some reason and we did not trigger it and its not the end of the file, throw an exception
                throw new Exception("Error reading file at ".$this->line);
            }
            $clean=[
                'chemicalFormula',
                'chemicalName',
                'CAS',
                'Reference'
            ];
            if (isset($results)&&!empty($results)) { //if we have results then clean the results up
                foreach ($results as $key=>&$result) {
                    if(in_array($key,$clean)) {
                        if (is_array($result) && count($result) == 1) {
                            $result = reset($result);
                        }
                    }
                }
                if($this->debug == true) { //If debug is enabled, show the current line number, rule line number, rule position, multiplier value, and line buffer
                    ?>
                    <pre style="background-color: #1c94c4"> <?php
                    print_r($results);
                }
                return $results;
            } else {
                return false;
            }
        } else {
            throw new Exception("File has not been set yet");
        }
    }

    /**
     * Destructor
     */
    function __destruct()
    {
        fclose($this->file);
    }

    /**
     * Handles any failure to find the correct
     * @param $buffer
     * @param $results
     * @return int|string
     * @throws Exception
     */
    function handleFailure($buffer,&$results)
    {
        $currentRule =$this->config['Rules'][$this->ruleLine][$this->rulePosition]; //get the current rule
        if (isset($currentRule['debug'])&&$currentRule['debug']==true) { //if that rule has debugging on then dump whats stored in headers, what the rule is, and information on its processing
            var_dump($this->headers);
            var_dump($currentRule);
            echo "Line " . $this->line . "<br> Rule Line " . $this->ruleLine . "<br> Rule Position " . $this->rulePosition."<br> String:<pre>".$buffer."</pre><br><br>";
        }
        // We failed to find what we wanted, how do we handle this
        if (isset($currentRule['failure'])) { //If we have a failure then perform the required action or attempt to perform the a standard action
            switch ($currentRule['failure']) {
                case "STORE": //Failure condition states that we still need to store this value
                    if (isset($currentRule['valueName'])) {
                        $results[$currentRule['valueName']][] = null;
                    } else {
                        $results[$this->headers[$currentRule['headerIndex'] - 1]][] = null;
                    }
                    if (!isset($currentRule['notAnomaly'])||!$currentRule['notAnomaly'])
                        $this->anomalies[]="Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition ."<br> String:<pre>".$buffer."</pre><br><br>";
                    break;
                default: //if we have a failure then attempt standard action or show failure
                    if(isset($currentRule['failure']) && !empty($currentRule['failure']))
                        return $this->standard_switch($currentRule['failure'],$buffer,$results);
                    throw new Exception("Pattern Not Found and Failure Not Listed; Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition);
                    break;
            }
        }
    }

    /**
     * Process preg_match_all
     * @param $buffer
     * @param $results
     * @param $matches
     * @return int|string
     * @throws Exception
     */
    function handle_preg_match_all($buffer,&$results,$matches)
    {
        $currentRule =$this->config['Rules'][$this->ruleLine][$this->rulePosition];
        if (isset($currentRule['action'])) {
            if (isset($currentRule['debug'])&&$currentRule['debug']==true) { //if that rule has debugging on then dump whats stored in headers, what the rule is, and information on its processing
                var_dump($matches);
                var_dump($this->headers);
                var_dump($currentRule);
                echo "Line " . $this->line . "<br> Rule Line " . $this->ruleLine . "<br> Rule Position " . $this->rulePosition."<br> String:<pre>".$buffer."</pre><br><br>";
            }
            switch ($currentRule['action']) {
                case "STORE":
                    //if a name is given use that name
                    if (isset($currentRule['valueName'])) {
                        //if an index is given use that index
                        if (isset($currentRule['matchIndex'])) {
                            //check to see if a certain index was requested
                            if (!isset($matches[1][$currentRule['matchIndex']-1])) {
                                //either its going to be set as an anomaly in the store or we don't want it to be marked as such
                                if($currentRule['failure']!="STORE"||!isset($currentRule['notAnomaly'])||!$currentRule['notAnomaly'])
                                        $this->anomalies[]="Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition."<br> String:<pre>".$buffer."</pre><br><br>";
                                    return $this->handleFailure($buffer,$results);
                                break;
                            }
                            //store it
                            $results[$currentRule['valueName']][] = $matches[1][$currentRule['matchIndex']-1];
                        } else {
                            //store it
                            $results[$currentRule['valueName']][] = $matches[1][0];
                        }
                        //if a header index is given use that
                    } elseif (isset($currentRule['headerIndex'])) {
                        //check to see if a certain index was requested
                        if(isset($currentRule['matchIndex'])) {
                            //check if there is something stored there
                            if(!isset($matches[1][$currentRule['matchIndex']-1])){
                                //either its going to be set as an anomaly in the store or we don't want it to be marked as such
                                if($currentRule['failure']!="STORE"||!isset($currentRule['notAnomaly'])||!$currentRule['notAnomaly'])
                                    $this->anomalies[]="Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition."<br> String:<pre>".$buffer."</pre><br><br>";
                                return $this->handleFailure($buffer,$results);
                            }
                            if (!isset($this->headers[$currentRule['headerIndex'] - 1])) {
                                //We have more data than headers, this is a problem
                                throw new Exception("Header Not Found ; Line " . $this->line . " <br>Rule Line " . $this->ruleLine . " <br>Rule Position " . $this->rulePosition);
                            }
                            //store it
                            $results[$this->headers[$currentRule['headerIndex'] - 1]][] = $matches[1][$currentRule['matchIndex']-1];
                        } else {
                            //store it
                            $results[$this->headers[$currentRule['headerIndex'] - 1]][] = $matches[1][0];
                        }
                    }
                    break;
                case "STOREALL": //Store all the data we acquired as a key value, this is typically horizontal
                    $matchKey=1;
                    if(isset($currentRule['matchIndex'])) {
                        $matchKey=$currentRule['matchIndex'];
                    }
                    foreach($matches[$matchKey] as $match) {
                        if (isset($currentRule['valueName'])) {
                            $results[$currentRule['valueName']][$this->indexStart][] =$match;
                        } elseif (isset($currentRule['headerIndex'])) {
                            $results[$this->headers[$currentRule['headerIndex'] - 1]][$this->indexStart][]=$match;
                        }
                    }
                    break;
                case "STOREALLASDATA": //Store all of the information as data, this is typically more vertical
                    $matchKey=1;
                    if(isset($currentRule['matchIndex'])) {
                        $matchKey=$currentRule['matchIndex'];
                    }
                    foreach($matches[$matchKey] as $index=>$match) {
                        $count=$index+1;
                        if (isset($currentRule['valueName'])) {
                            $results[$currentRule['valueName']][$index+$this->indexStart][] =$match;
                        } elseif (isset($currentRule['headerIndex'])) {
                            if($currentRule['headerIndex']>=0) {
                                $results[$this->headers[$currentRule['headerIndex'] - 1]][$index + $this->indexStart][] = $match;
                            }elseif($currentRule['headerIndex']==(-1)){
                                $results['rawData'][end($results['DataUnits'])][$this->indexStart][] = $match;
                                $count=1;
                            }
                        }
                        if($count>$this->currentColumns){
                            $this->currentColumns=$count;
                        }
                    }
                    break;
                case "STOREASHEADER":
                    // Store this information as a header
                    if(isset($currentRule['matchIndex'])) {
                        $this->headers[] = $matches[1][$currentRule['matchIndex'] - 1];
                    } else {
                        $this->headers[] = $matches[1][0];
                    }
                    break;
                case "STOREALLASHEADER":
                    foreach($matches[1] as $match) {
                        $this->headers[]=$match;
                    }
                    break;
                default:
                    return $this->standard_switch($currentRule['action'],$buffer,$results,$matches);
            }
        }
    }

    /**
     * Process preg_match
     * @param $buffer
     * @param $results
     * @param $matches
     * @return int|string
     * @throws Exception
     */
    function handle_preg_match($buffer,&$results,$matches)
    {
        $currentRule =$this->config['Rules'][$this->ruleLine][$this->rulePosition];
        if (isset($currentRule['action'])) {
            if (isset($currentRule['debug'])&&$currentRule['debug']==true) {
                echo "Line " . $this->line . "<br> Rule Line " . $this->ruleLine . "<br> Rule Position " . $this->rulePosition."<br> String:<pre>".$buffer."</pre><br><br>";
            }

            switch ($currentRule['action']) {
                case "STORE":
                    // If a name is given use that name
                    if (isset($currentRule['valueName'])) {
                        //if an index is given use that index
                        if (isset($currentRule['matchIndex'])) {
                            //check to see if a certain index was requested
                            if (!isset($matches[$currentRule['matchIndex']])) {
                                //either its going to be set as an anomaly in the store or we don't want it to be marked as such
                                if ($currentRule['failure']!="STORE"||!isset($currentRule['notAnomaly'])||!$currentRule['notAnomaly']) {
                                    $this->anomalies[] = "Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition . "<br> String:<pre>" . $buffer . "</pre><br><br>";
                                    return $this->handleFailure($buffer, $results);
                                }
                                break;
                            }
                            // Store it
                            $results[$currentRule['valueName']][] = $matches[$currentRule['matchIndex']];
                        } else {
                            // Store it
                            $results[$currentRule['valueName']][] = $matches[0];
                        }
                        // If a header index is given use that
                    } elseif (isset($currentRule['headerIndex'])) {
                        // Check to see if a certain index was requested
                        if (isset($currentRule['matchIndex'])) {
                            //check if there is something stored there
                            if (!isset($matches[$currentRule['matchIndex']])) {
                                //either its going to be set as an anomaly in the store or we don't want it to be marked as such
                                if ($currentRule['failure']!="STORE"||!isset($currentRule['notAnomaly'])||!$currentRule['notAnomaly']) {
                                    $this->anomalies[] = "Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition . "<br> String:<pre>" . $buffer . "</pre><br><br>";
                                    return $this->handleFailure($buffer, $results);
                                }
                            }
                            if (!isset($this->headers[$currentRule['headerIndex'] - 1])) {
                                // We have more data than headers, this is a problem
                                throw new Exception("Header Not Found ; Line " . $this->line . " <br>Rule Line " . $this->ruleLine . " <br>Rule Position " . $this->rulePosition);
                            }
                            // Store it
                            $results[$this->headers[$currentRule['headerIndex'] - 1]][] = $matches[$currentRule['matchIndex']];

                        } else {
                            // Store it
                            $results[$this->headers[$currentRule['headerIndex'] - 1]][] = $matches[0];
                        }
                    }
                    break;

                case "STOREASHEADER":
                    //store this information as a header
                    if(isset($currentRule['matchIndex']))
                        $this->headers[] = $matches[$currentRule['matchIndex']];
                    else
                        $this->headers[] = $matches[0];
                    break;

                case "STOREALLASDATA":
                    foreach($matches as $index=>$match) {
                        if($index==0){
                            continue;
                        }
                        if (isset($currentRule['valueName'])) {
                            $results[$currentRule['valueName']][$index+$this->indexStart-1][] =$match;
                        } elseif (isset($currentRule['headerIndex'])) {
                            $results[$this->headers[$currentRule['headerIndex'] - 1]][$index+$this->indexStart][]=$match;
                        }
                        if(($index+1)>($this->currentColumns+1)){
                            $this->currentColumns=$index;
                        }
                    }
                    break;
                default:
                    return $this->standard_switch($currentRule['action'],$buffer,$results,$matches);

            }
        }
    }

    /**
     * John - What does this do? TODO
     */
    private function standard_switch($value,$buffer =null,&$results =null,$matches=null){
        $currentRule =$this->config['Rules'][$this->ruleLine][$this->rulePosition];
        switch ($value){

            case "NEXTLINE":
                return 2;
                break;

            case "USELAST":
                //use the last rule for this position as well
                $this->rulePosition--;
                $this->advancement++;
                return 1;

            case "USELASTLINE":
                //go back 1 line in the config and advance 2 lines afterwords
                $this->rulePosition = 1;
                $this->ruleLine--;
                $this->lineAdvancement++;
                return 1;

            case "USELASTLINEUNTIL":
                //go back 1 line in the config and use that until errors
                $this->rulePosition = 1;
                $this->ruleLine--;
                return 1;

            case "CONTINUE":
                $this->advancement=0;
                return 2;
                break;

            case "NEXTRULE":
                $newPosition=$this->rulePosition;
                $newLine=$this->ruleLine;
                //if we want to skip a specific number of rules, calculate where that puts us
                if(!isset($currentRule['skip'])) {
                    $currentRule['skip']=1;
                }
                if(isset($currentRule['skip'])) {
                    $newPosition += $currentRule['skip'];
                    $ready = false;
                    $tick = 0;
                    while (!$ready) {
                        $tick++; //tick to make sure we donot get stuck in infinite loop
                        if (isset($this->config['Rules'][$newLine])) {
                            if ($newPosition > count($this->config['Rules'][$newLine])) {
                                $newPosition=$newPosition-count($this->config['Rules'][$newLine]);
                                $newLine++;
                            }elseif($newPosition <= 0){
                                $newLine--;
                                if (isset($this->config['Rules'][$newLine])){
                                    $newPosition=$newPosition+count($this->config['Rules'][$newLine]);
                                }else{
                                    if($this->debug == true) {
                                        ?> Unable to find proper rule (#1) <?php
                                    }
                                    return "END";
                                }
                            }elseif(isset($this->config['Rules'][$newLine][$newPosition])){
                                $ready=true;
                                if($newPosition>=1){
                                    $newPosition--;
                                }
                            }
                        }
                        if($tick>500){
                            if($this->debug == true) {
                                ?> Unable to find proper rule (#2) <?php
                            }
                            return "END";
                        }
                    }
                    $this->rulePosition=$newPosition;
                    $this->ruleLine=$newLine;

                }
                break;

            case "PREVIOUSLINE":
                if(isset($currentRule['skip'])){
                    $this->ruleLine-=$currentRule['skip'];
                }else {
                    $this->ruleLine -= 1;
                }
                if(!isset($this->config['Rules'][$this->ruleLine])){
                    $this->ruleLine=1;
                }
                $this->rulePosition=0;
                break;

            case "INCREASEMULTIPLIER":
                $results['split'][]=$this->indexStart;
                $this->indexStart+=$this->currentColumns;
                break;

            case "STORELINE":
                // If a name is given use that name
                if (isset($currentRule['valueName'])) {
                    if(isset($results[$currentRule['valueName']]) &&isset($results[$currentRule['valueName']][ $this->indexStart])) {
                        $results[$currentRule['valueName']][$this->indexStart] .= $buffer;
                    }else {
                        $results[$currentRule['valueName']][$this->indexStart] = $buffer;
                    }
                    var_dump($results[$currentRule['valueName']]);
                } elseif (isset($currentRule['headerIndex'])) {
                    $results[$this->headers[$currentRule['headerIndex'] - 1]][ $this->indexStart] .= $buffer;
                }
                break;

            case "END":
                return "END";
                break;

            case "EXCEPTION":
                throw new Exception($currentRule['errorText']."; Line " . $this->line . " Rule Line " . $this->ruleLine . " Rule Position " . $this->rulePosition);
                break;

            case "SKIP":
            default:
                break;
        }
    }
}
