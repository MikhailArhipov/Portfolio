<?
//Инициализация переменных
$path ='';
include('pricescore.php');
if (file_exists($path . "conf.json")) {
    $conf = json_decode(LoadFileWithWait($path . "conf.json"), true);
} else {
    $secret_key = uniqid();
    $conf = [
        'login' => 'dmitanisimov',
        'password' => md5('' . ":" . $secret_key),
        'secretkey' => $secret_key,
        'lastDateTime' => '',
        'loginMS' => '',
        'passwordMS' => '',
        'links' => [['name' => '', 'file' => '', 'description' => '']],
        'fields' => [['file' => '', 'fieldPrice' => '', 'fieldMS' => '']],
    ];
}

//Авторизация
if (auth($conf)) {
    include('menu.php');
    echo '</html>';
    die;
}

$error_login=false;
if (isset($_POST['login']) and isset($_POST['password']) and $_POST['login'] !== '') {
    //if (preg_match("/^[a-zA-Z0-9]{3,30}$/", $_POST['login'])==0)
    //    $syntax_error = true;
    if ($_POST['login'] != $conf['login'])
        $error_login = true;
    if ($_POST['password'] != $conf['password'])
        $error_login = true;
    if (@!$error_login) {
        $secretKey = uniqid();
        $currDate = time();
        $conf['secretkey'] = $secretKey;
        $conf['lastDateTime'] = $currDate;
        setcookie("PricesId", $_POST['login'] . ":" . md5($secretKey . ":" . $_SERVER['REMOTE_ADDR'] . ":" . $currDate), time() + 60 * 60 * 24);
        header("Location: " . $_SERVER['PHP_SELF']);
        WriteFileWithWait($path . "conf.json", json_encode($conf));
        die;
    }
}

//Меню
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Авторизация</title>
    <link rel="stylesheet" href="style.css"/>
</head>
<body>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <table class="center-screen">
            <tr>
                <td colspan="2" style="text-align: center"><strong>Авторизация:</strong></td>
            </tr>
            <tr>
                <td><span>Логин:</span></td>
                <td><input name="login" type="text" size="20" /></td>
            </tr>
            <tr>
                <td><span>Пароль:</span></td>
                <td><input name="password" type="password" size="20" /></td>
            </tr>
            <tr style="text-align: right">
                <td colspan="2">
                    <button class="c-button" type="submit" onmouseover="this.className='n-button'" onmouseout="this.className='c-button'">&nbsp;Войти&nbsp;</button>
                </td>
            </tr>
            <tr style="text-align: center; color: red">
                <td colspan="2">
                    <?
                    if (@$error_login) { ?>
                        <span>Пользователь не авторизован.</span>
                    <?
                    } ?>
                </td>
            </tr>
        </table>
    </form>

</body>

</html>