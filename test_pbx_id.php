<?php
include 'plog.php';

$conn = mysqli_connect("121.254.239.50", "nautes", "Nautes12@$", "LOGI", "3306");
if (!$conn) {
    echo "DB connection failed\n";
    exit;
}

function get_pbx_id_for_q_cc($conn, $q_num) {
    if (empty($q_num) || $q_num == "0") {
        return "0";
    }
    $sql = "SELECT c.pbx_id FROM T_QUEUE AS q INNER JOIN T_COMPANY AS c ON q.master_id = c.master_id AND c.company_level = 0 WHERE q.q_num = '$q_num' LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res))) {
        return $row[0] ?? "0";
    }
    return "0";
}

echo "Q700 pbx_id: " . get_pbx_id_for_q_cc($conn, "700") . "\n";
echo "Q800 pbx_id: " . get_pbx_id_for_q_cc($conn, "800") . "\n";
echo "Q900 pbx_id: " . get_pbx_id_for_q_cc($conn, "900") . "\n";

mysqli_close($conn);
?>
