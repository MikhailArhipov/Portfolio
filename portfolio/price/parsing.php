<?
//Функции
//фунция для обмена с сервером по API

function GetContentsCurl($url, $headers, $method,  $data, $type)
{
    while (true) {
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $url . $method,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        );
        curl_setopt_array($ch, $options);
        $page1 = curl_exec($ch);

        if ($page1 === false) {
            Log_("ОШИБКА! получения данных по API (метод {$method}): " . curl_error($ch), true);
            return false;
        }
        $page = [];
        if ($page1 == "") $page = false;
        else $page = json_decode($page1, true);
        if ($page === null) {
            echo "ОШИБКА! получен пустой ответ по API (метод {$method}): ";
            return false;
        }
        if (!empty($page) && (key_exists('message', $page)))
            if ($page['code'] != 'rest_post_invalid_page_number')
                echo 'Ошибка! ' . $page['code'], true;

        curl_close($ch);
        break;
    }
    return $page;
};

function GetHTML($url)
{
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36 Edg/110.0.1587.50',
        CURLOPT_FOLLOWLOCATION => true,
    );
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);
    return $content;
}

function YaDiskDownload($link, $file)
{
    $base_url = 'https://cloud-api.yandex.net/v1/disk/public/resources/download?';
    $final_url = $base_url . 'public_key=' . $link;
    $ch = curl_init($final_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($http_code === 200) {
        $json = json_decode(file_get_contents($final_url));
        $linkFile = $json->href;
        file_put_contents($file, file_get_contents($linkFile));
        return true;
    } else {
        return false;
    }
}

//Распаковка без дирректорий
function ExtractZip($inFile, $outDir)
{
    $result = [];
    $zip = new ZipArchive;
    if ($zip->open($inFile) === true) {
        $zip->extractTo($outDir);
        $zip->close();
        foreach (scandir($outDir) as $file) {
            if ($file == '.' || $file == '..' || !is_dir($outDir . '/' . $file))
                continue;
            foreach (scandir($outDir . '/' . $file) as $file1) {
                if ($file1 == '.' || $file1 == '..')
                    continue;
                if (is_dir($outDir . '/' . $file . '/' . $file1))
                    continue;
                rename($outDir . '/' . $file . '/' . $file1, $outDir . '/' . $file1);
                $result[] = $file1;
            }
            rmdir($outDir . '/' . $file);
        }
        unlink($inFile);
        return $result;
    } else
        return $result;
}

function GetDomElement($xpath, $findstring, $action = a::text, $nNodes = 0)
{
    $text = "";
    $nodes = null;
    try {
        $nodes = $xpath->query($findstring);
    } catch (Exception $e) {
        return "";
    }
    $start = 0;
    $end = 0;
    if ($nNodes != -1) {
        $start = $nNodes;
        $end = $nNodes + 1;
    } else
        $end = Count($nodes);

    for ($i = $start; $i < $end; $i++) {
        try {
            $node = $nodes[$i];
            switch ($action) {
                case a::outerHTML:
                    $text .= str_replace("\r", '', $node->C14N()) . "\r";
                    break;
                case a::href:
                    $text .= str_replace("\r", '', $node->attributes['href']) . "\r";
                    break;
                case a::src:
                    $text .= str_replace("\r", '', $node->attributes['src']) . "\r";
                    break;
                case a::content:
                    $text .= str_replace("\r", '', $node->attributes['content']) . "\r";
                    break;
                case a::title:
                    $text .= str_replace("\r", '', $node->attributes['title']) . "\r";
                    break;
                case a::value:
                    $text .= str_replace("\r", '', $node->attributes['value']) . "\r";
                    break;
                case a::class_:
                    $text .= str_replace("\r", '', $node->attributes['class']) . "\r";
                    break;
                default:
                    $text .= str_replace("\r", '', $node->textContent) . "\r";
            }
        } catch (Exception $e) {
            return "";
        }
    }
    if (strlen($text) > 0) $text = Trim($text, "\r");
    return $text;
}

//Инициализация переменных
class a
{
    public const text = 'text';
    public const href = 'href';
    public const src = 'src';
    public const content = 'content';
    public const title = 'title';
    public const outerHTML = 'outerHTML';
    public const value = 'value';
    public const class_ = 'class_';
}
$path = '';
ob_start();
if (!isset($checkFile))
    include($path . 'pricescore.php');
include($path . 'xls.php');
include($path . 'xlsx.php');
Log_("Запущен парсинг");
$t = time();
$conf = json_decode(LoadFileWithWait($path . "conf.json"), true);
if (file_exists($path . "parsing.json")) {
    $tovar = json_decode(LoadFileWithWait($path . "parsing.json"), true);
    $LastSummOfTovar = count($tovar);
} else
    $LastSummOfTovar = 0;
$tovar = [];
$timeZone = 0;
$dictionaryMS = array_values(array_unique(array_column($conf['fields'], 'fieldMS')));
if (file_exists($path . "status.json")) {
    $status = json_decode(LoadFileWithWait($path . "status.json"), true);
} else
    $status = ['parsing' => '', 'import' => '', 'lastStart' => ''];
if (file_exists($path . 'single')) {
    unlink($path . 'single');
} else
    $status['lastStart'] = strtotime("+{$timeZone} hour");

//Парсинг
foreach ($conf['links'] as $link) {

    if (file_exists($path . 'stop')) {
        Log_("Обнаружен запрет парсинга - экстренное завершение");
        die;
    }
    $arr = [];
    $file = $link['file'];
    if (strpos($file, '*') !== false && strpos($link['name'], 'disk.yandex.ru') === false) {
        Log_('Ошибка! В имени файла ' . $file . "не должно быть запрещенных символов", true);
        continue;
    }

    $dictionary = [];
    foreach ($dictionaryMS as $dic) {
        $arr1 = [];
        foreach ($conf['fields'] as $field) {
            if ($field['file'] == $link['description'] && $field['fieldMS'] == $dic)
                $arr1 = ['fieldPrice' => $field['fieldPrice'], 'fieldMS' => $field['fieldMS']];
        }
        if (count($arr1) == 0)
            $dictionary[] = ['fieldPrice' => '', 'fieldMS' => $dic];
        else
            $dictionary[] = $arr1;
    }

    if (strpos($link['name'], '.csv') !== false || strpos($link['name'], '/csv-') !== false) {
        $content = GetHTML($link['name']);
        WriteFileWithWait($file, $content);
        $fp = fopen($file, 'r');
        if (fgets($fp, 4) !== "\xef\xbb\xbf")
            rewind($fp);
        while (!feof($fp) && ($line = fgetcsv($fp, null, ';')) !== false) {
            $arr[] = $line;
        }
    } else if (strpos($link['name'], '.xml') !== false) {
        $content = GetHTML($link['name']);
        WriteFileWithWait($file, $content);
        $xlm = simplexml_load_string($content);
        $arr1 = json_decode(json_encode((array)$xlm), true);
        $arr2 = [];
        foreach ($arr1['shop']['offers']['offer'][0] as $key => $el) {
            if (!is_array($el))
                $arr2[] = $key;
        }
        $arr[] = $arr2;
        foreach ($arr1['shop']['offers']['offer'] as $key => $line) {
            $arr2 = [];
            foreach ($line as $key => $el) {
                if (!is_array($el))
                    $arr2[] = $el;
            }
            $arr[] = $arr2;
        }
    } else if (strpos($link['name'], '.htm') !== false) {
        $content = GetHTML($link['name']);
        $dom = new DomDocument;
        $dom->loadHTML($content);
        $trs = $dom->getElementsByTagName('tr');
        foreach ($trs as $tr) {
            $arr1 = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                foreach ($td->attributes as $attr)
                    if ($attr->localName == 'colspan') {
                        for ($i = 0; $i < $attr->nodeValue - 1; $i++)
                            $arr1[] = '';
                    }
                $arr1[] = $td->nodeValue;
            }
            $arr[] = $arr1;
        }
        unset($arr[0]);
        $arr = array_values($arr);
    } else if (strpos($link['name'], 'disk.yandex.ru') !== false) {
        YaDiskDownload($link['name'], 'arr.zip');
        $files = ExtractZip('arr.zip', __DIR__);
        $file1 = '';
        foreach ($files as $el1)
            if (strpos($file, '*') === false && $file == $el1)
                $file1 = $el1;
            else if (strpos($file, '*') !== false && strpos($el1, str_replace('*', '', $file)) !== false)
                $file1 = $el1;
            else
                unlink($el1);
        $ext = end(explode('.', $file1));
        $xls = false;
        if ($ext == 'xls') {
            $xls = new SimpleXLS($file1);
        } elseif ($ext == 'xlsx') {
            $xls = new SimpleXLSX($file1);
        }
        if ($xls && $xls->success()) {
            $arr = $xls->rows();
            for ($i = 0; $i < count($arr); $i++) {  //Удаляем все перед шапкой
                if ($arr[$i][1] == '')
                    unset($arr[$i]);
                else
                    break;
            }
            $arr = array_values($arr);
        }
    }

    foreach ($dictionary as $key => $dic)
        $dictionary[$key]['key'] = array_search($dic['fieldPrice'], $arr[0]);
    foreach ($arr as $key => $line) {
        if ($key == 0) continue;
        $arr1 = [];
        foreach ($dictionary as $dic) {
            if (
                strlen($dic['fieldPrice']) > 2
                && substr($dic['fieldPrice'], 0, 1) == '/' && substr($dic['fieldPrice'], -1) == '/'
            )
                $arr1[$dic['fieldMS']] = str_replace('/', '', $dic['fieldPrice']);
            else if ($dic['key'] === false)
                $arr1[$dic['fieldMS']] = '';
            else
                $arr1[$dic['fieldMS']] = $line[$dic['key']];
        }
        if ($arr1['code'] !== '')
            $tovar[] = $arr1;
    }
    Log_("Считан прайс {$link['description']}, добавлено в очередь " . count($arr) . " товаров");
    if ($LastSummOfTovar < count($tovar))
        $LastSummOfTovar = count($tovar);
    $status['parsing'] = "Идет парсинг " . count($tovar) . " из {$LastSummOfTovar} (" . round(count($tovar) * 100 / $LastSummOfTovar) . "%)";
    WriteFileWithWait($path . 'status.json', json_encode($status));
}

WriteFileWithWait('parsing.json', json_encode($tovar));
$content = jsonToTable($tovar);
WriteFileWithWait('parsing.xls', '<?xml encoding="utf-8" ?>' . $content);
$status['parsing'] = "Парсинг завершен " . Date("d.m.Y H:i", strtotime("+{$timeZone} hour"));
WriteFileWithWait($path . 'status.json', json_encode($status));
Log_('Парсинг завершен, добавлено в очередь ' . count($tovar) . ' товаров, затрачено ' . round((time() - $t) / 6) / 10 . ' минут');
ob_get_clean();
