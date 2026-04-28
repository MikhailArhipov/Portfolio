<?

//Функции
function Log_($messsage, $type = false)
{
    if (!key_exists('logFile', $GLOBALS)) {
        //$task = str_replace('.php', '', __FILE__);
        //$task = str_replace('\\', '/', $task);
        //$task = substr($task, strrpos($task, '/') + 1);
        $task = "parser";
        $path = '';
        $dateOfParsing = strtotime('now');
        $GLOBALS['logFile'] = "{$path}{$task}_" . date('d_m_Y', $dateOfParsing) . '.log';
    }
    $logFile = $GLOBALS['logFile'];
    $errorFile = str_replace('.log', '.err', $logFile);
    if ($type == true)
        file_put_contents($errorFile, $messsage . "\r\n", FILE_APPEND);
    if (is_array($messsage)) {
        file_put_contents($logFile, print_r($messsage, true), FILE_APPEND);
        return;
    } else if ($messsage == '')
        $log_message = "\r\n" . $messsage;
    else
        $log_message = "\r\n" . date('d.m.Y H:i', strtotime('now')) . "\t" . $messsage;
    file_put_contents($logFile, $log_message, FILE_APPEND);
    echo $messsage . '<br>';
}

function LoadFileWithWait($file)
{
    $t = strtotime('+60 seconds');
    $result = null;
    while ($t > strtotime('now')) {
        try {
            $result = file_get_contents($file);
            return $result;
        } catch (Exception $e) {
            sleep(5);
        }
    }
    return null;
}

function WriteFileWithWait($file, $content)
{
    $t = strtotime('+60 seconds');
    while ($t > strtotime('now')) {
        try {
            file_put_contents($file, $content);
            return true;;
        } catch (Exception $e) {
            sleep(5);
        }
    }
    return false;
}

function jsonToTable($data)
{
    $table = '<table border="1">';
    foreach ($data as $key => $jsons) {
        if ($key == 0) {
            $table .= '<tr>';
            foreach ($jsons as $rkey => $rvalue) {
                $table .= '<th>' . $rkey . '</th>';
            }
            $table .= '</tr>';
        }
        $table .= '<tr>';
        foreach ($jsons as $rkey => $rvalue) {
            if (is_array($rvalue)) $table .= '<td>' . json_encode($rvalue) . '</td>';
            else $table .= '<td>' . $rvalue . '</td>';
        }
        $table .= '</tr>';
    }
    $table .= '</table>';
    return $table;
}

function auth($conf)
{
    if (isset($_COOKIE["PricesId"])) {
        $data_array = explode(":", $_COOKIE["PricesId"]);
        $cookies_hash = $data_array[1];
        $evaluate_hash = md5($conf["secretkey"] . ":" . $_SERVER["REMOTE_ADDR"] . ":" . $conf["lastDateTime"]);
        if ($cookies_hash == $evaluate_hash) {
            return true;
        }
    }
    return false;
}

$checkFile[] = '.log';
$checkFile[] = '.err';
$checkFile[] = '.csv';
$checkFile[] = '.xml';
$checkFile[] = '.htm';
$checkFile[] = '.xlsx';
$checkFile[] = '.xls';