<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();


$hoje = date("Y-m-d H:i:s");
$isAdmin = $_SESSION['s_nivel'] == 1;
$aliasAreasFilter = ($_SESSION['requester_areas'] ? "ua.AREA" : "o.sistema");

$filtered_areas = $_SESSION['dash_filter_areas'];
$filtered_clients = $_SESSION['dash_filter_clients'];
$qry_filter_areas = "";


/* Controle para limitar os resultados com base nos clientes selecionados */
$qry_filter_clients = "";
if (!empty($filtered_clients)) {
    $qry_filter_clients = " AND o.client IN ({$filtered_clients}) ";
}


/* Filtro de seleção a partir das áreas */
if (empty($filtered_areas)) {
    if ($isAdmin) {
        $qry_filter_areas = "";
    } else {
        $qry_filter_areas = " AND " . $aliasAreasFilter . " IN ({$_SESSION['s_uareas']})";
    }
} else {
    $qry_filter_areas = " AND (" . $aliasAreasFilter . " IN ({$filtered_areas}))";
}


$sql = "SELECT 
            s.sistema AS area, count({$aliasAreasFilter}) AS quantidade 
        FROM 
            sistemas s, ocorrencias o, usuarios ua, `status` st
        WHERE 
            s.sis_id = {$aliasAreasFilter} AND
            o.aberto_por = ua.user_id AND 
            o.status = st.stat_id AND
            o.data_abertura >= DATE_SUB('{$hoje}', INTERVAL 1 YEAR) AND
            st.stat_ignored <> 1 
            {$qry_filter_clients}
            {$qry_filter_areas}
        ";
$sql.= " GROUP BY s.sistema";
$sql = $conn->query($sql);

$data = array();

if ($sql->rowCount()) {
    foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data[] = $row;
    }
} else {
    $data[] = array(
        "area" => TRANS('NO_RECORDS_FOUND'),
        "quantidade" => 0
    );
}
$data[]['chart_title'] = ($_SESSION['requester_areas'] ? TRANS('TICKETS_BY_REQUESTER_AREAS', '', 1) : TRANS('TICKETS_BY_AREAS', '', 1));
// IMPORTANT, output to json
echo json_encode($data);

?>
