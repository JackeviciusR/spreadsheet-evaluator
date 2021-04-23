<?php


define('DIR',__DIR__.'/');

include DIR.'app/API.php';
include DIR.'app/SpreadsheetEvaluator.php';



//$postUrl = 'https://www.wix.com/_serverless/hiring-task-spreadsheet-evaluator/submit/eyJ0YWdzIjpbXX0';

//$get_url = 'https://www.wix.com/_serverless/hiring-task-spreadsheet-evaluator/jobs';
$get_url = 'https://www.wix.com/_serverless/hiring-task-spreadsheet-evaluator/jobs?tag=is_less';



API::createAPI()->GET($get_url);
$data = (API::createAPI())->readData();
$postUrl = $data->submissionUrl;

$postData = (SpreadsheetEvaluator::create())->createPOSTdata('rokas.jackevicius@gmail.com');
// _dc($postData);
$response = (API::createAPI())->POST($postUrl, $postData);



?>


<h1> Rokas Jackevičius </h1>

<?php if (isset($response->message)) : ?>
    <h3 style='color:green'><?= $response->message ?></h3>
<?php else :  ?>
    <h3 style='color:red'><?= $response->error ?></h3>
    <h3 style='color:red'><?= $response->help ?></h3>
<?php endif ?>