<?php session_start();
/*      Copyright 2023 Flávio Ribeiro

        This file is part of OCOMON.

        OCOMON is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 3 of the License, or
        (at your option) any later version.
        OCOMON is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with Foobar; if not, write to the Free Software
        Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$post = $_POST;

$erro = false;
$screenNotification = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['format_bar'] = $_SESSION['s_formatBarOco'];
$data['problema'] = (isset($post['problema']) ? noHtml($post['problema']) : "");

$data['area'] = (isset($post['area']) ? $post['area'] : []);
$data['area_default'] = (isset($post['area_default']) ? noHtml($post['area_default']) : "");
if (!empty($data['area']) && count($data['area']) == 1) {
    $data['area_default'] = $data['area'][0];
}


$data['sla'] = (isset($post['sla']) ? noHtml($post['sla']) : "");
$data['tipo_1'] = (isset($post['tipo_1']) ? noHtml($post['tipo_1']) : "");
$data['tipo_2'] = (isset($post['tipo_2']) ? noHtml($post['tipo_2']) : "");
$data['tipo_3'] = (isset($post['tipo_3']) ? noHtml($post['tipo_3']) : "");
$data['tipo_4'] = (isset($post['tipo_4']) ? noHtml($post['tipo_4']) : "");
$data['tipo_5'] = (isset($post['tipo_5']) ? noHtml($post['tipo_5']) : "");
$data['tipo_6'] = (isset($post['tipo_6']) ? noHtml($post['tipo_6']) : "");
$data['profile_form'] = (isset($post['profile_form']) ? noHtml($post['profile_form']) : "");


$data['descricao'] = (isset($post['descricao']) ? $post['descricao'] : "");
$data['descricao'] = ($data['format_bar'] ? $data['descricao'] : noHtml($data['descricao']));

$data['prob_active'] = (isset($post['prob_active']) ? ($post['prob_active'] == "yes" ? 1 : 0) : 0);
$data['need_authorization'] = (isset($post['need_authorization']) ? ($post['need_authorization'] == "yes" ? 1 : 0) : 0);
$data['card_in_costdash'] = (isset($post['card_in_costdash']) ? ($post['card_in_costdash'] == "yes" ? 1 : 0) : 0);


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['problema'])) {
        $data['success'] = false; 
        $data['field_id'] = "problema";
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }

    if (count($data['area']) > 1 && empty($data['area_default'])) {
        $data['success'] = false; 
        $data['field_id'] = "area_default";
        $data['message'] = message('warning', 'Ooops!', TRANS('NEED_DEFINE_ONE_AREA_AS_DEFAULT'),'');
        echo json_encode($data);
        return false;
    }
}



if ($data['action'] == 'new') {


    $sql = "SELECT prob_id FROM problemas WHERE problema = '" . $data['problema'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "problema";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }
    
    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }


    $sql = "INSERT INTO problemas 
        (
            problema, 
            prob_area, 
            prob_sla, 
            prob_tipo_1, 
            prob_tipo_2, 
            prob_tipo_3, 
            prob_tipo_4, 
            prob_tipo_5, 
            prob_tipo_6, 
            prob_profile_form, prob_descricao, prob_area_default,
            need_authorization,
            card_in_costdash
        ) 
        VALUES 
        (
            '" . $data['problema'] . "', 
            NULL, 
            " . dbField($data['sla']) . ", 
            " . dbField($data['tipo_1']) . ", 
            " . dbField($data['tipo_2']) . ", 
            " . dbField($data['tipo_3']) . ", 
            " . dbField($data['tipo_4']) . ", 
            " . dbField($data['tipo_5']) . ", 
            " . dbField($data['tipo_6']) . ", 
            " . dbField($data['profile_form']) . ", 
            '" . $data['descricao'] . "', 
            " . dbField($data['area_default']) . ", 
            " . $data['need_authorization'] . ", 
            " . $data['card_in_costdash'] . " 
        )";

    try {
        $conn->exec($sql);
        $issueId = $conn->lastInsertId();
        $data['success'] = true; 


        if (!empty($data['area'])) {
            foreach ($data['area'] as $area) {
                $sql = "INSERT INTO areas_x_issues
                        (
                            area_id, 
                            prob_id, 
                            old_prob_id
                        )
                        VALUES 
                        (
                            '" . noHtml($area) . "', 
                            {$issueId},
                            null
                        )
                ";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        } else {
            $sql = "INSERT INTO areas_x_issues 
                    (
                        area_id, 
                        prob_id, 
                        old_prob_id
                    )
                    VALUES 
                    (
                        NULL, 
                        {$issueId},
                        NULL
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }


        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    $sql = "SELECT prob_id FROM problemas WHERE problema = '" . $data['problema'] . "' AND prob_id <> " . $data['cod'] . " ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "problema";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }
    
    
    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }


    $sql = "UPDATE problemas SET 
				problema = '" . $data['problema'] . "', 
                prob_area = NULL, 
                prob_sla = " . dbField($data['sla']) . ",  
                prob_tipo_1 = " . dbField($data['tipo_1']) . ", 
                prob_tipo_2 = " . dbField($data['tipo_2']) . ", 
                prob_tipo_3 = " . dbField($data['tipo_3']) . ",  
                prob_tipo_4 = " . dbField($data['tipo_4']) . ",  
                prob_tipo_5 = " . dbField($data['tipo_5']) . ",  
                prob_tipo_6 = " . dbField($data['tipo_6']) . ",  
                prob_profile_form = " . dbField($data['profile_form']) . ",  
				prob_descricao = '" . $data['descricao'] . "', 
				prob_active = '" . $data['prob_active'] . "', 
				prob_area_default = " . dbField($data['area_default']) . ",
				need_authorization = '" . $data['need_authorization'] . "', 
				card_in_costdash = '" . $data['card_in_costdash'] . "' 
            WHERE prob_id='" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true;


        $sql = "DELETE FROM areas_x_issues WHERE prob_id = '" . $data['cod'] . "' ";
        try {
            $conn->exec($sql);

            if (!empty($data['area'])) {
                foreach ($data['area'] as $area) {
                    $sql = "INSERT INTO areas_x_issues
                            (
                                area_id, 
                                prob_id, 
                                old_prob_id
                            )
                            VALUES 
                            (
                                '" . noHtml($area) . "', 
                                '" . $data['cod'] . "',
                                null
                            )
                    ";
                    try {
                        $conn->exec($sql);
                    } catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            } else {
                $sql = "INSERT INTO areas_x_issues 
                        (
                            area_id, 
                            prob_id, 
                            old_prob_id
                        )
                        VALUES 
                        (
                            NULL, 
                            '" . $data['cod'] . "',
                            NULL
                        )
                ";
                try {
                    $conn->exec($sql);
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        



        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {


    $sqlFindPrevention = "SELECT numero FROM ocorrencias WHERE problema = '" . $data['cod'] . "'";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }


    $sql = "DELETE FROM problemas WHERE prob_id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);