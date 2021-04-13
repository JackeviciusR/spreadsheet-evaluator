<?php

class API {
    
    private static $apiObj;

    public static function createApi() : Object {
        return self::$apiObj ?? self::$apiObj = new self;
    }

    public function GET(string $getUrl) : void {
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $getUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        $response = curl_exec($curl); 
        curl_close($curl);

        $this->writeData($response);
    }

    public function POST(string $postUrl, array $data) : Object {
        //$postUrl = 'https://www.wix.com/_serverless/hiring-task-spreadsheet-evaluator/submit/eyJ0YWdzIjpbXX0';
        $data_json = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);

        curl_close($ch);

        return json_decode($response);
    }

    public function writeData($data) : void {
        file_put_contents(DIR.'data/get-data.json', $data);
    }

    public function readData() : Object {
        $data = file_get_contents(DIR.'data/get-data.json');
        return json_decode($data);
    }






}




?>