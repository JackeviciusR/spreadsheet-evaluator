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
        
                        if ( isset($jobObj->data[$row][$col]->formula->reference) ) {
                            [$row_new, $col_new] = $this->reference($jobObj->data, $jobObj->data[$row][$col]->formula->reference);
                            $spreadsheet->jobs[$jobInd]->data[$row][$col] = $jobObj->data[$row_new][$col_new];
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->sum) ) {
                            $this->sum($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->multiply) ) {
                            $this->multiply($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->divide) ) {
                            $this->divide($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->concat) ) {
                            $this->concat($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->is_greater) ) {
                            $is_graeter = $this->comparison($jobObj->data, $row, $col, 'is_greater', $jobObj->data[$row][$col]->formula->is_greater);
        
                            if ($is_graeter != 2) {
                                $answer = [ 'value' => ['boolean' => $is_graeter == 0 ? false : true ]];
                            } else {
                                $answer = [ 'error' => 'type does not match, must be number types' ];
                            }
                            $spreadsheet->jobs[$jobInd]->data[$row][$col] = $answer;
                            
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->is_equal) ) {
                            $is = $this->comparison($jobObj->data, $row, $col, 'is_equal', $jobObj->data[$row][$col]->formula->is_equal);
                            
                            if ($is != 2) {
                                $answer = [ 'value' => ['boolean' => $is == 0 ? false : true ]];
                                
                            } else {
                                $answer = [ 'error' => 'type does not match, must be number types' ];
                            }
                            $spreadsheet->jobs[$jobInd]->data[$row][$col] = $answer;
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->{'not'}) ) {
                            $this->logical($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data, 'not');
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->{'and'}) ) {
                            $this->logical($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data, 'and');
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->{'or'}) ) {
                            $this->logical($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data, 'or');
                        }
        
                        if ( isset($jobObj->data[$row][$col]->formula->if) ) {
                            $this->ifCond($jobObj->data, $row, $col, $spreadsheet->jobs[$jobInd]->data);
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
    
        if ($type == 'number') {
            
            // $operatorArrObj == $jobData[$row][$col]->formula->sum[][]
            if ( isset($operatorArrObj->reference) && is_string($operatorArrObj->reference) ) {
                [$row_new, $col_new] = $this->reference($jobData, $operatorArrObj->reference);
                if ( isset($jobData[$row_new][$col_new]->value->number) && is_numeric($jobData[$row_new][$col_new]->value->number) ) {
                    return [$jobData[$row_new][$col_new]->value->number]; 
                } else {
                    return false;
                }
            } elseif (isset($operatorArrObj->value->number) && is_numeric($operatorArrObj->value->number) ) {
                return [$operatorArrObj->value->number];
            } else {
                return false;
            }
    
        }
    
        if ($type == 'boolean') {
            
            if ( isset($operatorArrObj->reference) && is_string($operatorArrObj->reference) ) {
                [$row_new, $col_new] = $this->reference($jobData, $operatorArrObj->reference);
                if ( isset($jobData[$row_new][$col_new]->value->boolean) && is_bool($jobData[$row_new][$col_new]->value->boolean) ) {
                    return [$jobData[$row_new][$col_new]->value->boolean]; 
                } else {
                    return false;
                }
            } elseif (isset($operatorArrObj->value->boolean) && is_bool($operatorArrObj->value->boolean) ) {
                return [$operatorArrObj->value->boolean];
            } else {
                return false;
            }
    
        }
    
        if ($type == 'text') {
            
            if ( isset($operatorArrObj->reference) && is_string($operatorArrObj->reference) ) {
                [$row_new, $col_new] = $this->reference($jobData, $operatorArrObj->reference);
                if ( isset($jobData[$row_new][$col_new]->value->text) && is_string($jobData[$row_new][$col_new]->value->text) ) {
                    return [$jobData[$row_new][$col_new]->value->text]; 
                } else {
                    return false;
                }
            } elseif (isset($operatorArrObj->value->text) && is_string($operatorArrObj->value->text) ) {
                return [$operatorArrObj->value->text];
            } else {
                return false;
            }
    
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
        
        foreach($operatorArr as $operatorArrObj) {
    
            $answer = $this->findElementValue($jobData, $operatorArrObj, 'number');
            
            if ($answer === false) {
                $error = true;
                break;
            }
    
            $values[] = $answer[0];
    
        }
    
        if (!$error) {
    
            if ($operator == 'is_greater') {
                return $values[0] > $values[1] ? 1 : 0;
    
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
    
       
    public function ifCond(array $jobData, int $row, int $col, array &$spreadsheetData) : void {
    
        $error = false;
        $value;
        $is_greater;
        
    
        if (isset($jobData[$row][$col]->formula->if[0]->is_greater)) {
            $is_greater = $this->comparison($jobData, $row, $col, 'is_greater', $jobData[$row][$col]->formula->if[0]->is_greater);
        }
    
        if ($is_greater != 2) {
            $ifArrIndex = $is_greater == 1 ? 1 : 2;
            
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


