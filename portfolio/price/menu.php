<?
//Инициализация переменных
$path = '';
$conf = json_decode(LoadFileWithWait($path . "conf.json"), true);
if (!auth($conf))
    die;
if (isset($_POST['parsing'])) {
    //shell_exec('php -f ' . __DIR__ . "/pars_$profile.php > /dev/null 2>&1 &");
    file_put_contents($path . 'single', '');
    include($path . 'parsing.php');
}
$status = []; //item - текущая позиция парсинга, from - надо спарсить, date - дата
if (file_exists($path . "status.json")) {
    $status = json_decode(LoadFileWithWait($path . "status.json"), true);
} else
    $status = ['parsing' => '', 'import' => '', 'lastStart' => ''];
if ($status['lastStart'] != '') {
    $nextstart = (time() - $status['lastStart']) / (60 * 60);
    if ($nextstart > 24) {
        $status['lastStart'] = '';
        $nextstart = 'ранее парсинг через</br>Crone не производился';
        WriteFileWithWait($path . 'status.json', json_encode($status));
    } else
        $nextstart = 'Следующий запуск через </br>' . (round($nextstart / 10) * 10) . ' часов';
} else
    $nextstart = 'ранее парсинг через</br>Crone не производился';

//Обработка изменений формы
$change = false;
$delRows['links'] = [];
$delRows['fields'] = [];
foreach ($_POST as $key => $el) {

    if ($key == 'fields-fieldPrice_2')
        $a = 1;

    $first = '';
    $second = '';
    $index = -1;
    if (strpos($key, '-') !== false) {
        $first = explode('-', $key)[0];
        $second = strpos($key, '_') === false ? explode('-', $key)[1] : explode('-', explode('_', $key)[0])[1];
    } else
        $first = strpos($key, '_') === false ? $key : explode('_', $key)[0];
    if (strpos($key, '_') !== false)
        $index = explode('_', $key)[1] * 1;
    if (key_exists($first, $conf) && $second == '' && $conf[$first] != $el) {
        $conf[$first] = $el;
        $change = true;
    } else if (key_exists($first, $conf) && $second != '' && $conf[$first][$index][$second] != $el) {
        $conf[$first][$index][$second] = $el;
        $change = true;
    }
    if ($first == 'add') {
        for ($i = 0; $i < $_POST[$first . $second . 'N']; $i++) {
            $arr = [];
            foreach ($conf[$second][0] as $key1 => $el1)
                $arr[$key1] = '';
            $conf[$second][] = $arr;
        }
        $change = true;
    }
    if ($first == 'del') {
        foreach ($_POST as $key1 => $el1) {
            if (strpos($key1, 'check-' . $second) === false)
                continue;
            $index = explode('_', $key1)[1];
            $delRows[$second][] = $index * 1;
        }
        $change = true;
    }
}
foreach ($delRows as $key => $delRow) {
    foreach ($delRow as $el) {
        if ($el == 0 && count($delRow) == count($conf[$key])) {
            foreach ($conf[$key][$el] as $key1 => $el1)
                $conf[$key][$el][$key1] = '';
        } else
            unset($conf[$key][$el]);
    }
    if (count($delRow) == 0)
        continue;
    $arr = array_values($conf[$key]);
    $conf[$key] = $arr;
}
foreach ($conf as $key => $el) { //Проверка и восстановление целостности массива
    if (!is_array($el)) continue;
    foreach ($el as $key1 => $el1) {
        $restore = false;
        foreach ($el1 as $el2)
            if ($el2 == '')
                $restore = true;
        if ($restore)
            foreach ($el1 as $key2 => $el2)
                $conf[$key][$key1][$key2] = '';
    }
}
if (isset($_POST['exit'])) {
    setcookie("PricesId", "", time() - 60 * 60 * 24);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}
if (isset($_POST['stop'])) {
    if (file_exists($path . 'stop'))
        unlink($path . 'stop');
    else
        file_put_contents($path . 'stop', '');
}
if (file_exists($path . 'stop')) $nextstart = 'парсинг и импорт остановлен';
if ($change || key_exists('save', $_POST))
    WriteFileWithWait($path . "conf.json", json_encode($conf));

//Вывод формы
?>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Обновление цен поставщиков</title>
    <link rel="stylesheet" href="style.css" />
    <script>
        function download(d) {
            if (d == 'Выбрать документы') return;
            window.location = 'download.php?file=' + d;
        }
        window.onload = function() {
            var authH = document.getElementById('pass');
            var authV = document.getElementById('passV');
            authV.onfocus = function() {
                authV.className = "auth hidden";
                authH.className = "auth visible";
                authH.focus();
            }
            authH.onblur = function() {
                authH.className = "auth hidden";
                authV.className = "auth visible";
            }
            var authH1 = document.getElementById('pass1');
            var authV1 = document.getElementById('passV1');
            authV1.onfocus = function() {
                authV1.className = "auth hidden";
                authH1.className = "auth visible";
                authH1.focus();
            }
            authH1.onblur = function() {
                authH1.className = "auth hidden";
                authV1.className = "auth visible";
            }
        }
    </script>
</head>

<body>
    <form method="POST">
        <title>Обновление цен поставщиков</title>
        <h2 align="center">Обновление цен поставщиков:</h2>

        <p><strong>Статус:</strong>
        <table style="width: 95%;">
            <tr>
                <td style="width: 25%; text-align:center" rowspan="2"><?= $nextstart ?></td>
                <td align="center" rowspan="2">
                    <button class="c-button" type="submit" name="parsing" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Парсить прайсы&nbsp;</button>
                    <button disabled class="c-button" type="submit" name="import" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Импорт на Мой склад&nbsp;</button>
                    <button class="c-button" type="submit" name="stop" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;<?= file_exists($path . 'stop') ? 'Возобновить' : 'Остановить' ?>&nbsp;</button>
                </td>
                <td style="width: 25%; text-align:center"><?= $status['parsing'] ?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td><?= $status['import'] ?></td>
            </tr>
        </table>
        </p>

        <p><strong>Ссылки на прайсы </strong>(для изменения заполните все поля):
        <table style="width: 95%;">
            <tr>
                <td align="center">Ссылка</td>
                <td align="center">Имя файла</td>
                <td align="center">Описание</td>
            </tr>
            <? for ($i = 0; $i < count($conf['links']); $i++) { ?>
                <tr>
                    <td style="width: 56%;">
                        <input name="<?= 'links-name_' . $i ?>" value="<?= htmlspecialchars($conf['links'][$i]['name']) ?>" type="text" style="width: 100%;" />
                    </td>
                    <td style="width: 20%;">
                        <input name="<?= 'links-file_' . $i ?>" value="<?= htmlspecialchars($conf['links'][$i]['file']) ?>" type="text" style="width: 100%;" />
                    </td>
                    <td style="width: 20%;">
                        <input name="<?= 'links-description_' . $i ?>" value="<?= htmlspecialchars($conf['links'][$i]['description']) ?>" type="text" style="width: 100%;" />
                    </td style="width: 4%;">
                    <td align="center"><input type="checkbox" name="<?= 'check-links_' . $i ?>" /></td>
                </tr>
            <? } ?>
            <tr>
                <td colspan="4">
                    <button class="c-button" type="submit" name="add-links" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Добавить ссылки&nbsp;</button>
                    <input type="text" style="width: 50px; text-align:center;" name="addlinksN" value="1" />
                    <button class="c-button" type="submit" name="del-links" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Удалить выбранные ссылки&nbsp;</button>
                </td>
            </tr>
        </table>
        </p>

        <p><strong>Поля для обновления </strong>(для изменения заполните все поля, /значение/ - прямое занесение значений):
        <table style="width: 95%;">
            <tr>
                <td align="center">Прайс</td>
                <td align="center">Поле в прайсе</td>
                <td align="center">Поле в Мой Склад</td>
            </tr>
            <? for ($i = 0; $i < count($conf['fields']); $i++) { ?>
                <tr>
                    <td>
                        <input name="<?= 'fields-file_' . $i ?>" value="<?= htmlspecialchars($conf['fields'][$i]['file']) ?>" type="text" style="width: 100%;" />
                    </td>
                    <td style="width: 32%;">
                        <input name="<?= 'fields-fieldPrice_' . $i ?>" value="<?= htmlspecialchars($conf['fields'][$i]['fieldPrice']) ?>" type="text" style="width: 100%;" />
                    </td>
                    <td style="width: 32%;">
                        <input name="<?= 'fields-fieldMS_' . $i ?>" value="<?= htmlspecialchars($conf['fields'][$i]['fieldMS']) ?>" type="text" style="width: 100%;" />
                    </td style="width: 4%;">
                    <td align="center"><input type="checkbox" name="<?= 'check-fields_' . $i ?>" /></td>
                </tr>
            <? } ?>
            <tr>
                <td colspan="4">
                    <button class="c-button" type="submit" name="add-fields" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Добавить поля&nbsp;</button>
                    <input type="text" style="width: 50px; text-align:center;" name="addfieldsN" value="1" />
                    <button class="c-button" type="submit" name="del-fields" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Удалить выбранные поля&nbsp;</button>
                </td>
            </tr>
        </table>
        </p>

        <p><strong>Прайсы и логи:</strong>
            <select style="width: 20%;" onChange="if (this.selectedIndex) download(this.value)">
                <option value=-1>Посмотреть документ</option>
                <?
                foreach (scandir(__DIR__) as $file) {
                    if ($file == '.' || $file == '..' || is_dir(__DIR__ . '/' . $file))
                        continue;
                    $check = true;
                    foreach ($checkFile as $el)
                        if (strpos($file, $el) !== false) $check = false;
                    if ($check)
                        continue;
                    $index = array_search($file, array_column($conf['links'], 'file'));
                    if ($index === false) {
                        foreach ($conf['links'] as $key => $el) {
                            if (
                                strpos($el['file'], '*') !== false &&
                                strpos($file, str_replace('*', '', $el['file'])) !== false
                            )
                                $index = $key;
                        }
                    }
                    if ($file == 'parsing.xls')
                        $name = "Свод прайсов";
                    else if (strpos($file, 'parser') !== false) {
                        $name = str_replace('.log', '', $file);
                        $name = str_replace('.err', '', $name);
                        $name = str_replace('parser_', '', $name);
                        if (strpos($file, '.log') !== false)
                            $name = "Лог за " . $name;
                        else
                            $name = "Ошибки за " . $name;
                    } else if ($index === false)
                        $name = $file;
                    else
                        $name = "Прайс " . $conf['links'][$index]['description'];
                    echo "<option value=\"{$file}\">{$name}</option>";
                }
                ?>
            </select>
        </p>

        <p><strong>Доступы:</strong>
        <table>
            <tr>
                <td><label>Текущий логин:</label>&nbsp;</td>
                <td><input type="text" name="login" value="<?= $conf['login'] ?>" /></td>
                <td>&nbsp;&nbsp;<label>Текущий пароль:</label>&nbsp;</td>
                <td>
                    <input type="text" id="pass" name="password" class="auth hidden" value="<?= $conf['password'] ?>" />
                    <input type="password" id="passV" class="auth visible" value="Пароль" />
                </td>
            </tr>
            <tr>
                <td><label>Логин Мой Склад:</label>&nbsp;</td>
                <td><input type="text" name="loginMS" value="<?= $conf['loginMS'] ?>" /></td>
                <td>&nbsp;&nbsp;<label>Пароль Мой склад:</label>&nbsp;</td>
                <td>
                    <input type="text" id="pass1" name="passwordMS" class="auth hidden" value="<?= $conf['passwordMS'] ?>" />
                    <input type="password" id="passV1" class="auth visible" value="Пароль" />
                </td>
            </tr>

        </table>
        </br>
        <button class="c-button" type="submit" name="save" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Запомнить изменения&nbsp;</button>
        <button class="c-button" type="submit" name="exit" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Выйти из аккаунта&nbsp;</button>
        </p>
    </form>
</body>