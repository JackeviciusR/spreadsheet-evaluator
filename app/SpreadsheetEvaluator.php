<?php


class SpreadsheetEvaluator {

    private static $spreadsheetObj;


    public static function create() : Object {
        return self::$spreadsheetObj ?? self::$spreadsheetObj = new self;
    }

    public function createPOSTdata(string $email) : array {

       $data_json = [
            "email" => $email,
            "results" => $this->evaluater()->jobs,
        ];

        return $data_json;
    }

    public function readData() : Object {
        $data = file_get_contents(DIR.'data/get-data.json');
        return json_decode($data);
    }
    

    public function evaluater() : Object {
        
        $spreadsheet = $this->readData();

        foreach($spreadsheet->jobs as $jobInd=>$jobObj) {
            foreach($jobObj->data as $row => $arr) {
                foreach($arr as $col => $arrObj) {
        

                    if ( isset($arrObj->formula) ) {
        
                        $cell = $jobObj->data[$row][$col];

                        if ( isset($cell->formula->reference) ) {
                            [$row_new, $col_new] = $this->reference($jobObj->data, $cell->formula->reference);
                            $spreadsheet->jobs[$jobInd]->data[$row][$col] = $jobObj->data[$row_new][$col_new];
                        }
        
                        if ( isset($cell->formula->sum) ) {
                            $this->sum($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($cell->formula->multiply) ) {
                            $this->multiply($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($cell->formula->divide) ) {
                            $this->divide($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($cell->formula->concat) ) {
                            $this->concat($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        $saveComparisonValues = function($result, $row, $col, &$spreadsheet) {
                            
                            if ($result != 2) { // jei neklaida
                                $answer = [ 'value' => ['boolean' => $result == 0 ? false : true ]];
                            } else {
                                $answer = [ 'error' => 'type does not match, must be number types' ];
                            }
                            $spreadsheet[$row][$col] = $answer;
                            
                        };

                        if ( isset($cell->formula->is_greater) ) {
                            $result = $this->comparison($jobObj->data, $row, $col, 'is_greater', $cell->formula->is_greater);
        
                            $saveComparisonValues($result, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                            
                        }

                        if ( isset($cell->formula->is_less) ) {
                            $result = $this->comparison($jobObj->data, $row, $col, 'is_less', $cell->formula->is_less);
        
                            $saveComparisonValues($result, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                            
                        }
        
                        if ( isset($cell->formula->is_equal) ) {
                            $result = $this->comparison($jobObj->data, $row, $col, 'is_equal', $cell->formula->is_equal);
                            
                            $saveComparisonValues($result, $row, $col, $spreadsheet->jobs[$jobInd]->data);

                        }
        
                        if ( isset($cell->formula->{'not'}) ) {
                            $this->logical($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data, 'not');
                        }
        
                        if ( isset($cell->formula->{'and'}) ) {
                            $this->logical($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data, 'and');
                        }
        
                        if ( isset($cell->formula->{'or'}) ) {
                            $this->logical($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data, 'or');
                        }
        
                        if ( isset($cell->formula->if) ) {

                            $this->ifCond($jobObj->data, $row, $col, key($cell->formula->if[0]), $spreadsheet->jobs[$jobInd]->data);
                        }
        
        
                    }
                }
            }
        }
    
        return $spreadsheet;
    
    }


    public function reference(array $jobData, string $ref) : array {

        preg_match('/(^[A-Z]+)(\d+)/i', $ref, $matches);
        $row = $matches[2] - 1;

        // if we wil have AA1, AB1, ...
        $splited = str_split($matches[1]);
        $lettersCount = count($splited);
        $lettersMap = array_map(function($val) {
            return ord($val);
            }, $splited);
        $col = array_sum($lettersMap) - 65 - ($lettersCount-1)*(65-25);

        if ( !empty($jobData[$row][$col]->formula->reference) ) {
            return $this->reference($jobData, $jobData[$row][$col]->formula->reference);
        }
    
        return [$row, $col];
    }
    

    public function findElementValue(array $jobData, Object $operatorArrObj, string $type) {
    
        
        $istype = function($x) use ($type) {

            if ($type == 'number') {
                return is_numeric($x);
            }
            if ($type == 'boolean') {
                return is_bool($x);
            }
            if ($type == 'text') {
                return is_string($x);
            }

        };


        if ( isset($operatorArrObj->reference) && is_string($operatorArrObj->reference) ) {
            [$row_new, $col_new] = $this->reference($jobData, $operatorArrObj->reference);
            if ( isset($jobData[$row_new][$col_new]->value->{$type}) && $istype($jobData[$row_new][$col_new]->value->{$type}, $type) ) {
                return [$jobData[$row_new][$col_new]->value->{$type}]; 
            } else {
                return false;
            }
        } elseif (isset($operatorArrObj->value->{$type}) && $istype($operatorArrObj->value->{$type}) ) {
            return [$operatorArrObj->value->{$type}];
        } else {
            return false;
        }
    
        
        
    }

    
    public function sum(array $jobData, int $row, int $col, array &$spreadsheetData) : void {
    
        $error = false;
        $values = [];
    
        foreach($jobData[$row][$col]->formula->sum as $operatorArrObj) {
    
            $answer = $this->findElementValue($jobData, $operatorArrObj, 'number');
            
            if ($answer === false) {
                $error = true;
                break;
            }
    
            $values[] = $answer[0];
    
        }
    
        if (!$error) {
            $spreadsheetData[$row][$col] = [ 'value' => ['number' => array_sum($values)] ];
        } else {
            $spreadsheetData[$row][$col] = [ 'error' => 'type does not match' ];
        }
    }
    
    
    
    public function multiply(array $jobData, int $row, int $col, array &$spreadsheetData) : void {
    
        $error = false;
        $values = [];
    
        foreach($jobData[$row][$col]->formula->multiply as $operatorArrObj) {
    
            $answer = $this->findElementValue($jobData, $operatorArrObj, 'number');
            
            if ($answer === false) {
                $error = true;
                break;
            }
    
            $values[] = $answer[0];
    
         }
    
        if (!$error) {
            $spreadsheetData[$row][$col] = [ 'value' => ['number' => array_product($values)] ];
        } else {
            $spreadsheetData[$row][$col] = [ 'error' => 'type does not match' ];
        }
    }

    
    public function divide(array $jobData, int $row, int $col, array &$spreadsheetData) : void{
    
        $error = false;
        $values=[];
    
        foreach($jobData[$row][$col]->formula->divide as $operatorArrObj) {
    
            $answer = $this->findElementValue($jobData, $operatorArrObj, 'number');
            
            if ($answer === false) {
                $error = true;
                break;
            }
    
            $values[] = $answer[0];
    
        }
    
        if (!$error && $values[1] != 0) {
            $spreadsheetData[$row][$col] = [ 'value' => ['number' => $values[0]/$values[1] ]];
        } else {
            $spreadsheetData[$row][$col] = [ 'error' => 'type does not match, or divisor cant be 0' ];
        }
    }
    
    
    public function comparison(array $jobData, int $row, int $col, string $operator, array $operatorArr) : int {
    
        $error = false;
        $values=[];
        

        $isValues = function($jobData, $operatorArr, $type) {
            
            $values=[];

            foreach($operatorArr as $operatorArrObj) {
    
                $answer = $this->findElementValue($jobData, $operatorArrObj, $type);
                
                if ($answer === false) {
                    return null;
                }
        
                $values[] = $answer[0];

            }
            return $values;
        };

        $values = $isValues($jobData, $operatorArr, 'number');

        if (!isset($values)) {
            $values = $isValues($jobData, $operatorArr, 'text'); 
        }
        
    
        if (isset($values)) {
    
            if ($operator == 'is_greater') {
                return $values[0] > $values[1] ? 1 : 0;
    
            }
            if ($operator == 'is_less') {
                return $values[0] < $values[1] ? 1 : 0;
    
            }
            if ($operator == 'is_equal') {
                return $values[0] == $values[1] ? 1 : 0;
            }
            
        } else {
            return 2;
        }
    }
    
     
    public function logical(array $jobData, int $row, int $col, array &$spreadsheetData, string $operator) {
    
        $error = false;
        $values=[];
        
        if ($operator == 'not') {
            $answer = $this->findElementValue($jobData, $jobData[$row][$col]->formula->{$operator}, 'boolean');
                if ($answer === false) {
                    $error = true;
                }
                $values[] = $answer[0];
    
        } else {
            foreach($jobData[$row][$col]->formula->{$operator} as $operatorArrObj) {
                $answer = $this->findElementValue($jobData, $operatorArrObj, 'boolean');
                if ($answer === false) {
                    $error = true;
                    break;
                }
                $values[] = $answer[0];
            }
        }
    
        if (!$error) {
            if ($operator == 'not') {
                $spreadsheetData[$row][$col] = [ 'value' => ['boolean' => !$values[0] ]];
            }
    
            if ($operator == 'and') {
                $res = $values[0];
    
                foreach($values as $key=>$val) {
                    if ($key != 0) {
                        $res = $res && $val;
                    }
                }
                $spreadsheetData[$row][$col] = [ 'value' => ['boolean' => $res ] ];
            }
    
            if ($operator == 'or') {
                $res = $values[0];
    
                foreach($values as $key=>$val) {
                    if ($key != 0) {
                        $res = $res || $val;
                    }
                }
                $spreadsheetData[$row][$col] = [ 'value' => ['boolean' => $res] ];
            }
            
        } else {
            $spreadsheetData[$row][$col] = [ 'error' => 'type does not match, must be boolean types or bad reference' ];
        }
    
    }
    
       
    public function ifCond(array $jobData, int $row, int $col, $operator, array &$spreadsheetData) : void {
    
        $error = false;
        $value;
        $result;
        
    
        if (isset($jobData[$row][$col]->formula->if[0]->{$operator})) {
            $result = $this->comparison($jobData, $row, $col, $operator, $jobData[$row][$col]->formula->if[0]->{$operator});
        }
    
        if ($result != 2) {
            $ifArrIndex = $result == 1 ? 1 : 2;
            
            if ( isset($jobData[$row][$col]->formula->if[$ifArrIndex]->reference) && is_string($jobData[$row][$col]->formula->if[$ifArrIndex]->reference) ) {
                [$row_new, $col_new] = $this->reference($jobData, $jobData[$row][$col]->formula->if[$ifArrIndex]->reference);
    
                if ( isset($jobData[$row_new][$col_new]->value->boolean) ) {
                    $value = ['boolean', $jobData[$row_new][$col_new]->value->boolean];
                } elseif ( isset($jobData[$row_new][$col_new]->value->number) ) {
                    $value = ['number', $jobData[$row_new][$col_new]->value->number];
                } elseif ( isset($jobData[$row_new][$col_new]->value->text) ) {
                    $value = ['text', $jobData[$row_new][$col_new]->value->text];
                } else {
                    $error = true;
                }
                    
            } elseif ( isset($jobData[$row][$col]->formula->if[$ifArrIndex]->value->boolean) ) {
                $value = ['boolean', $jobData[$row][$col]->formula->if[$ifArrIndex]->value->boolean];
            } elseif ( isset($jobData[$row][$col]->formula->if[$ifArrIndex]->value->number) ) {
                $value = ['number', $jobData[$row][$col]->formula->if[$ifArrIndex]->value->number];
            } elseif ( isset($jobData[$row][$col]->formula->if[$ifArrIndex]->value->text) ) {
                $value = ['text', $jobData[$row][$col]->formula->if[$ifArrIndex]->value->text];
            } else {
                $error = true;
            }
    
        } else {
            $error = true;
        }
    
        if (!$error) {
             
            if($value[0] == 'boolean') {
                $spreadsheetData[$row][$col] = [ 'value' => ['boolean' => $value[1]] ];
            } elseif($value[0] == 'number') {
                $spreadsheetData[$row][$col] = [ 'value' => ['number' => $value[1]] ];
            } elseif($value[0] == 'text') {
                $spreadsheetData[$row][$col] = [ 'value' => ['text' => $value[1]] ];
            }
            
        } else {
            $spreadsheetData[$row][$col] = [ 'error' => 'type does not match, values types can be: boolean, number and text' ];
        }
    
    }
    
     
    public function concat(array $jobData, int $row, int $col, array &$spreadsheetData) : void {
    
        $error = false;
        $values=[];
    
        foreach($jobData[$row][$col]->formula->concat as $operatorArrObj) {
    
            $answer = $this->findElementValue($jobData, $operatorArrObj, 'text');
            
            if ($answer === false) {
                $error = true;
                break;
            }
    
            $values[] = $answer[0];
            
        }
    
        if (!$error) {
            $spreadsheetData[$row][$col] = [ 'value' => ['text' => join("", $values) ]];
        } else {
            $spreadsheetData[$row][$col] = [ 'error' => 'type does not match, must be text type' ];
        }
    }
    
    
}


