<?php
// top header
$parameters = isset($parameters) ? $parameters : '';
echo "<table width='100%' border='0' cellspacing='0' cellpadding='10' class='page_title'>";
    echo "<tr>";
        echo "<td width='20%' align='left' rowspan='0' class='page_title_dark'>";
            if (empty($acs_user)) {
                if (isset($_POST['txtpass'])) {
                    $output = 'КОД ДОСТУПА НЕ РАСПОЗНАН';
                    $log_flag = false;
                }
                echo "Для активации кнопок введите код доступа:<br><br>";
                echo "<form action='{$_SERVER['PHP_SELF']}{$parameters}' method='post' id='cookieId'>
                        <input type='password' name='txtpass' size='30' maxlength='32' required autofocus='true'>
                        <input type='submit' class='btn' name='cookie_clear' value='OK' title='Перейти к авторизованному доступу'>
                      </form>";
            }
            else {
                echo "{$acs_user} ({$roles_arr[$acs_role]})<br><br>";
                echo "<form action='{$_SERVER['PHP_SELF']}{$parameters}' method='post' id='cookieId'>
                        <input type='submit' class='btn' name='cookie_clear' onclick='clearCookie()' value='Сбросить код доступа' title='Вернуться к анонимному доступу'>
                      </form>";
                if (isset($_POST['txtpass'])) {
                    $output = 'Код доступа подтверждён.';
                    $log_flag = false;
                }
            }
        echo "</td>";
        echo "<td align='center'>";
            echo "<h3>{$title}</h3>";
        echo "</td>";
        echo "<td width='25%' align='right' rowspan='0'>";
            foreach ($links as $link)
                echo "$link<br>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td align='center'>";
            echo "<p id='to_remove'><b><font color='red'>Пожалуйста, подождите...   </font></b><img src='images/inprogress.gif' hspace=10></p>";
            // output buffer flush
            ob_flush();
            flush();
            ob_end_flush();
