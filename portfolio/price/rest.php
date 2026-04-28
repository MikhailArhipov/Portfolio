<?
echo __DIR__;
$path = '';
foreach (scandir(__DIR__) as $file) {
    if ($file == '.' || $file == '..' || is_dir(__DIR__ . '/' . $file))
        continue;
    $delete = true;
    if ($file == 'all.csv') $delete = false;
    if ($file == 'conf1.json') $delete = false;
    if ($file == 'download.php') $delete = false;
    if ($file == 'ef06b9051834f2504d319f77d344dade.107280-stockbrand-128936-3.csv') $delete = false;
    if ($file == 'index.php') $delete = false;
    if ($file == 'menu.php') $delete = false;
    if (strpos($file, '.log') !== false) $delete = false;
    if ($file == 'parsing.json') $delete = false;
    if ($file == 'parsing.php') $delete = false;
    if ($file == 'parsing.xls') $delete = false;
    if ($file == 'pricescore.php') $delete = false;
    if ($file == 'status.json') $delete = false;
    if ($file == 'stluce_mrc.xml') $delete = false;
    if ($file == 'style.css') $delete = false;
    if ($file == 'swgshop_export_full_price_qty.csv') $delete = false;
    if ($file == 'xls.php') $delete = false;
    if ($file == 'xlsx.php') $delete = false;
    if ($file == 'rest.php') $delete = false;
    if ($delete) unlink($file);
}
copy('conf1.json','conf.json');
