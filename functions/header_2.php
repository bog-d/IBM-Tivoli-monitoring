<?php
            // top header informational message output
            if (!empty($output)) {
                echo "<b><font color='red'>".$output."</font></b>";
                if (isset($log_file))
                    file_put_contents($log_file, date('d.m.Y H:i:s')."\t".$acs_user."\t".$output."\t\n", FILE_APPEND | LOCK_EX);
            }
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td align='center'>";
        echo "</td>";
    echo "</tr>";
echo "</table>";
