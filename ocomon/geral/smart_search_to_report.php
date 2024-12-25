<?php session_start();
/*                        Copyright 2023 Flávio Ribeiro

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

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

// use Html2Text\Html2Text;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$imgsPath = "../../includes/imgs/";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

$_SESSION['s_page_ocomon'] = $_SERVER['PHP_SELF'];
// $_SESSION['s_app'] = "smart_search";

/* Campos customizados */
$custom_fields_full = getCustomFields($conn, null, 'ocorrencias');
$custom_fields_classes = [];
foreach ($custom_fields_full as $cfield) {
    $custom_fields_classes[] = $cfield['field_name'];
}
$custom_fields_classes_text = implode(",", $custom_fields_classes);

// Para solucionar o bug que ocorre no Datatables na tentativa de memorizar as colunas quando não existem campos customizados
if (empty($custom_fields_classes_text)) {
    $custom_fields_classes_text = "custom_field";
}

$filter_areas = "";
$areas_names = "";
if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
    /* Visibilidade isolada entre áreas para usuários não admin */
    $u_areas = $_SESSION['s_uareas'];
    $filter_areas = " AND sis_id IN ({$u_areas}) ";

    $array_areas_names = getUserAreasNames($conn, $u_areas);

    foreach ($array_areas_names as $area_name) {
        if (strlen((string)$areas_names))
            $areas_names .= ", ";
        $areas_names .= $area_name;
    }
}



?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos_custom.css" />

    <title><?= APP_NAME; ?>&nbsp;<?= VERSAO; ?></title>

    <style>
        .input-group>.input-group-prepend {
            /* flex: 0 0 60px; */
            max-width: 60px;
            min-width: 60px;
        }

        .input-group .input-group-text {
            width: 100%;
        }

        .input-group>.input-group-append {
            /* flex: 0 0 60px; */
            max-width: 60px;
            min-width: 60px;
        }
        

        .list-resources {
            line-height: 1.5em;
        }

        .input-group>.double-append {
            max-width: 55px;
            min-width: 55px;
        }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
            font-size: 11px;
            font-family: FreeSans, sans-serif;
            line-height: 1.5em;
        }

    </style>

</head>

<body>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div class="container-fluid">

        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails" style="position:relative">
                        <iframe id="iframeTicketInfo"  frameborder="1" style="position:absolute;top:0px;width:100%;height:100vh;"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="report-mainlogo" class="report-mainlogo" id="report-mainlogo"/>
        <input type="hidden" name="logo-base64" id="logo-base64"/>

        <h5 class="my-4"><i class="fas fa-filter text-secondary"></i>&nbsp;<?= TRANS('TTL_SMART_SEARCH_TO_REPORT'); ?></h5>
        <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" onSubmit="return false;">
            <div class="form-group row my-4">
                <!-- form-row -->
                <label for="data_abertura_from" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_MIN_DATE_OPENING'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_MIN_DATE_OPENING'); ?>" data-placeholder="<?= TRANS('SMART_MIN_DATE_OPENING'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;<i class="fas fa-plus-square"></i>&nbsp;
                            </div>
                        </div>
                        <input type="text" class="form-control " id="data_abertura_from" name="data_abertura_from" placeholder="<?= TRANS('FIELD_CURRENT_MONTH'); ?>" autocomplete="off" disabled />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('FIELD_CURRENT_MONTH'); ?>" data-placeholder="<?= TRANS('FIELD_CURRENT_MONTH'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-check"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="current_month" id="current_month" value="1" checked="checked">
                            </div>
                        </div>
                    </div>
                </div>

                <label for="data_abertura_to" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_MAX_DATE_OPENING'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_MAX_DATE_OPENING'); ?>" data-placeholder="<?= TRANS('SMART_MAX_DATE_OPENING'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;<i class="fas fa-plus-square"></i>&nbsp;
                            </div>
                        </div>
                        <input type="text" class="form-control " id="data_abertura_to" name="data_abertura_to" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" />
                    </div>
                </div>

                <label for="data_atendimento_from" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_MIN_DATE_FIRST_RESPONSE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_HAS_FIRST_RESPONSE'); ?>" data-placeholder="<?= TRANS('SMART_HAS_FIRST_RESPONSE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-comment"></i>&nbsp;
                                <input type="checkbox" name="no_empty_response" id="no_empty_response" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="data_atendimento_from" name="data_atendimento_from" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_HASNT_FIRST_RESPONSE'); ?>" data-placeholder="<?= TRANS('SMART_HASNT_FIRST_RESPONSE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-comment-slash"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="empty_response" id="empty_response" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <label for="data_atendimento_to" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_MAX_DATE_FIRST_RESPONSE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_MAX_DATE_FIRST_RESPONSE'); ?>" data-placeholder="<?= TRANS('SMART_MAX_DATE_FIRST_RESPONSE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;<i class="fas fa-comment"></i>
                            </div>
                        </div>
                        <input type="text" class="form-control " id="data_atendimento_to" name="data_atendimento_to" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" />
                    </div>
                </div>


                <label for="data_fechamento_from" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_MIN_DATE_CLOSURE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_CLOSED'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_CLOSED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-check"></i>&nbsp;
                                <input type="checkbox" name="closed" id="closed" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="data_fechamento_from" name="data_fechamento_from" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('CARDS_NOT_CLOSED'); ?>" data-placeholder="<?= TRANS('CARDS_NOT_CLOSED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="not_closed" id="not_closed" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <label for="data_fechamento_to" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_MAX_DATE_CLOSURE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_MAX_DATE_CLOSURE'); ?>" data-placeholder="<?= TRANS('SMART_MAX_DATE_CLOSURE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-alt"></i>&nbsp;&nbsp;<i class="fas fa-check"></i>
                            </div>
                        </div>
                        <input type="text" class="form-control " id="data_fechamento_to" name="data_fechamento_to" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('ONLY_CLOSED_DUE_INACTIVITY'); ?>" data-placeholder="<?= TRANS('ONLY_CLOSED_DUE_INACTIVITY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-hourglass-end"></i>&nbsp;
                                <input type="checkbox" class="last-check-text-due" name="auto_close_due_inacticity" id="auto_close_due_inacticity" value="1">
                    </div>
                </div>
                    </div>
                </div>


                <!-- Cliente -->
                <label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-tie"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_client" id="no_empty_client" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="client" name="client[]" multiple="multiple">
                            <?php
                                $clients = getClients($conn);
                                foreach ($clients as $client) {
                                    ?>
                                    <option value="<?= $client['id']; ?>"><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_client" id="no_client" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Unidade -->
                <label for="unidade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-city"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_unidade" id="no_empty_unidade" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="unidade" name="unidade[]" multiple="multiple">
                            <?php
                            $units = getUnits($conn);
                            foreach ($units as $unit) {
                                ?>
                                <option data-subtext="<?= $unit['nickname']; ?>" value="<?= $unit['inst_cod']; ?>"><?= $unit['inst_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_unidade" id="no_unidade" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                


                <!-- Aberto por -->
                <label for="aberto_por" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('REQUESTER'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_BY_ENDUSER'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_BY_ENDUSER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="end_user_only" id="end_user_only" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="aberto_por" name="aberto_por[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM usuarios ORDER BY nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['user_id'] . "'";
                                print ">" . $row['nome'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_BY_TECHNITIANS'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_BY_TECHNITIANS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-md"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_end_user" id="no_end_user" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Email de contato -->
                <label for="contact_email" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('CONTACT_EMAIL'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-at"></i>&nbsp;
                                <input type="checkbox" name="no_empty_contact_email" id="no_empty_contact_email" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="contact_email" name="contact_email" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_contact_email" id="no_contact_email" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Departamento -->
                <label for="departamento" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-door-closed"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_departamento" id="no_empty_departamento" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="departamento" name="departamento[]" multiple="multiple">
                            <?php
                                $departments = getDepartments($conn);
                                foreach ($departments as $department) {
                                    $client = (!empty($department['nickname']) ? " (" . $department['nickname'] .")" : "");
                                    ?>
                                    <option data-subtext="<?= $client; ?>" value="<?= $department['loc_id']; ?>"><?= $department['local']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_departamento" id="no_departamento" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                

                <!-- Último editor -->
                <label for="operator" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SMART_OPERATOR'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_OPERATOR'); ?>" data-placeholder="<?= TRANS('SMART_OPERATOR'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-md"></i>&nbsp;&nbsp;<i class="fas fa-edit"></i>
                            </div>
                        </div>
                        <select class="form-control sel2" id="operator" name="operator[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM usuarios WHERE nivel in (1,2) ORDER BY nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['user_id'] . "'";
                                print ">" . $row['nome'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>


            <!-- Área solicitante -->
            <label for="requester_area" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('REQUESTER_AREA'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="fas fa-user-friends"></i>&nbsp;
                                <i class="fas fa-headset"></i>&nbsp;
                            </div>
                        </div>
                        <select class="form-control sel2 " id="requester_area" name="requester_area[]" multiple="multiple">
                            <?php
                            
                            $areas = getAreas($conn, 0, 1, null);
                            
                            foreach ($areas as $area) {
                                ?>
                                    <option value="<?= $area['sis_id'] ?>"><?= $area['sistema']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>


                <!-- Área de atendimento -->
                <label for="area" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSIBLE_AREA'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-headset"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_area" id="no_empty_area" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="area" name="area[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM sistemas WHERE sis_status = 1 {$filter_areas} AND sis_atende = 1 ORDER BY sistema";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $rowArea) {
                                print "<option value='" . $rowArea['sis_id'] . "'";
                                print ">" . $rowArea['sistema'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_area" id="no_area" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipo de problema -->
                <label for="problema" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ISSUE_TYPE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-exclamation-circle"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_problema" id="no_empty_problema" value="1">
                            </div>
                        </div>
                        <select class="form-control  sel2" id="problema" name="problema[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM problemas ORDER BY problema";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['prob_id'] . "'";
                                print ">" . $row['problema'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_problema" id="no_problema" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                
                <label for="etiqueta" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('FIELD_TAG_EQUIP'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-barcode"></i>&nbsp;
                                <input type="checkbox" name="no_empty_etiqueta" id="no_empty_etiqueta" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="etiqueta" name="etiqueta" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_etiqueta" id="no_etiqueta" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- <label for="contato" class="col-md-2 col-form-label col-form-label-sm text-md-right">Contato</label>
                <div class="form-group col-md-4">
                    <input type="text" class="form-control " id="contato" name="contato" placeholder="Informe o nome do contato" />
                </div> -->



                <label for="status" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_STATUS'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_CLOSED_PAUSED_STATUS'); ?>" data-placeholder="<?= TRANS('SMART_NOT_CLOSED_PAUSED_STATUS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-pause"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="time_freeze_status_only" id="time_freeze_status_only" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="status" name="status[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM status  ORDER BY status";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['stat_id'] . "'";
                                print ">" . $row['status'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_CLOSED_RUNNING_STATUS'); ?>" data-placeholder="<?= TRANS('SMART_NOT_CLOSED_RUNNING_STATUS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-clock"></i>&nbsp;
                                <!-- <i class="fas fa-hourglass-half"></i>&nbsp; -->
                                <input type="checkbox" class="last-check" name="no_time_freeze_status" id="no_time_freeze_status" value="1">
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Status de autorização -->
                <?php
                    $authorizationTypes = [
                        1 => TRANS('STATUS_WAITING_AUTHORIZATION'),
                        2 => TRANS('STATUS_AUTHORIZED'),
                        3 => TRANS('STATUS_REFUSED')
                    ];
                ?>
                <label for="authorization_status" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('AUTHORIZATION_STATUS'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-check"></i>&nbsp;
                                <input type="checkbox" name="no_empty_authorization_status" id="no_empty_authorization_status" class="first-check" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="authorization_status" name="authorization_status[]" multiple="multiple">
                            <?php
                            foreach ($authorizationTypes as $key => $value) {
                                ?>
                                <option value="<?= $key; ?>"><?= $value; ?></option>
                                <?php
                            }
                            ?>
                        </select>

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_authorization_status" id="no_authorization_status" class="last-check" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Avaliação de atendimento -->
                <label for="rate" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERVICE_RATE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-star-half-alt"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_rate" id="no_empty_rate" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="rate" name="rate[]" multiple="multiple">
                            <?php
                            $rateLabels = ratingLabels();
                            foreach ($rateLabels as $key => $label) {
                                ?>
                                    <option value="<?= $key; ?>"><?= $label; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_rate" id="no_rate" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejeição -->
                <label for="rejection" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TICKET_REJECTION'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('NEVER_REJECTED'); ?>" data-placeholder="<?= TRANS('NEVER_REJECTED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-check"></i>&nbsp;
                                <input type="checkbox" class="first-check-text" name="never_rejected" id="never_rejected" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="rejection" name="rejection" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" readonly />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('HAS_BEEN_REJECTED'); ?>" data-placeholder="<?= TRANS('HAS_BEEN_REJECTED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="has_been_rejected" id="has_been_rejected" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Com chamados relacionados - subchamados ou chamado pai -->
                <label for="relacionados" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TICKETS_REFERENCED'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_WITH_TICKETS_REFERENCED'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_WITH_TICKETS_REFERENCED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-stream"></i>&nbsp;
                                <input type="checkbox" class="first-check-text" name="only_relatives" id="only_relatives" value="1">
                            </div>
                        </div>

                        <input type="text" class="form-control " id="relacionados" name="relacionados" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" readonly />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_WITHOUT_TICKETS_REFERENCED'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_WITHOUT_TICKETS_REFERENCED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-clock"></i>&nbsp; -->
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="no_relatives" id="no_relatives" value="1">
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Recursos alocados -->
                <label for="resources" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ALLOCABLE_RESOURCES'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-plus-square"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_resources" id="no_empty_resources" value="1">
                            </div>
                        </div>
                        <select class="form-control  sel2" id="resources" name="resources[]" multiple="multiple">
                            <?php
                            $resources = getAssetsModels($conn, null, null, null, 1, ['t.tipo_nome']);
                            foreach ($resources as $resource) {
                                $fullType = $resource['tipo'] . ' - ' . $resource['fabricante'] . ' - ' . $resource['modelo'];
                                ?>
                                <option data-subtext="<?= $resource['cat_name']; ?>" data-model="<?= $resource['codigo']; ?>" value="<?= $resource['codigo']; ?>"><?= $fullType; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_resources" id="no_resources" value="1">
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Anexos -->
                <label for="attachments" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACHMENTS'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('ONLY_TICKETS_WITH_ATTACHMENTS'); ?>" data-placeholder="<?= TRANS('ONLY_TICKETS_WITH_ATTACHMENTS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-paperclip"></i>&nbsp;
                                <input type="checkbox" class="first-check-text" name="only_attachments" id="only_attachments" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="attachments" name="attachments" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" readonly />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('ONLY_TICKETS_WITHOUT_ATTACHMENTS'); ?>" data-placeholder="<?= TRANS('ONLY_TICKETS_WITHOUT_ATTACHMENTS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="no_attachments" id="no_attachments" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <div class="w-100"></div>
                <!-- <i class="fas fa-handshake"></i> -->
                <label for="response_sla" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSE_SLA'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('RESPONSE_SLA'); ?>" data-placeholder="<?= TRANS('RESPONSE_SLA'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-handshake"></i>&nbsp;<i class="fas fa-comment"></i>
                            </div>
                        </div>
                        <select class="form-control sel2 " id="response_sla" name="response_sla[]" multiple="multiple">
                            <option value="1"><?= TRANS('SMART_NOT_IDENTIFIED'); ?></option>
                            <option value="2"><?= TRANS('SMART_IN_SLA'); ?></option>
                            <option value="3"><?= TRANS('SMART_IN_SLA_TOLERANCE'); ?></option>
                            <option value="4"><?= TRANS('SMART_OUT_SLA'); ?></option>

                        </select>

                    </div>
                </div>

                <label for="solution_sla" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SOLUTION_SLA'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SOLUTION_SLA'); ?>" data-placeholder="<?= TRANS('SOLUTION_SLA'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-handshake"></i>&nbsp;<i class="fas fa-check"></i>
                            </div>
                        </div>

                        <select class="form-control sel2 " id="solution_sla" name="solution_sla[]" multiple="multiple">
                            <option value="1"><?= TRANS('SMART_NOT_IDENTIFIED'); ?></option>
                            <option value="2"><?= TRANS('SMART_IN_SLA'); ?></option>
                            <option value="3"><?= TRANS('SMART_IN_SLA_TOLERANCE'); ?></option>
                            <option value="4"><?= TRANS('SMART_OUT_SLA'); ?></option>

                        </select>

                    </div>
                </div>

                <!-- Canal -->
                <label for="channel" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPENING_CHANNEL'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_SYSTEM_CHANNELS'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_SYSTEM_CHANNELS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-magic"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="system_channels_only" id="system_channels_only" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="channel" name="channel[]" multiple="multiple">
                            <?php
                            $channels = getChannels($conn);
                            foreach ($channels as $channel) {
                            ?>
                                <option value="<?= $channel['id']; ?>"><?= $channel['name']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_OPEN_CHANNELS'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_OPEN_CHANNELS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-clock"></i>&nbsp; -->
                                <i class="fas fa-random"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="open_channels_only" id="open_channels_only" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prioridade -->
                <label for="prioridade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PRIORITY'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('COL_PRIORITY'); ?>" data-placeholder="<?= TRANS('COL_PRIORITY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-angle-double-down"></i>
                            </div>
                        </div>
                        <select class="form-control sel2 " id="prioridade" name="prioridade[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM prior_atend ORDER BY pr_nivel";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['pr_cod'] . "'";
                                print ">" . $row['pr_desc'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>



              

                <?php
                $tagsList = getTagsList($conn);
                ?>
                <div class="w-100"></div>
                <!-- Tags/Labels obrigatórias -->
                <label for="has_tags" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('INPUT_TAGS'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('MUST_HAVE_TAGS'); ?>" data-placeholder="<?= TRANS('MUST_HAVE_TAGS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-hashtag"></i>&nbsp;
                                <input type="checkbox" class="first-check-tmp" name="must_have_tags" id="must_have_tags" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="has_tags" name="has_tags[]" multiple="multiple">
                            <?php
                            foreach ($tagsList as $row) {
                                print "<option value='" . $row['tag_name'] . "'";
                                print ">" . $row['tag_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Tags de exclusão -->
                <label for="exclude_tags" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('INPUT_TAGS_EXCLUDED'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('INPUT_TAGS_EXCLUDED'); ?>" data-placeholder="<?= TRANS('INPUT_TAGS_EXCLUDED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-hashtag"></i>&nbsp;&nbsp;<i class="fas fa-minus-square"></i>&nbsp;
                            </div>
                        </div>
                        <select class="form-control sel2 " id="exclude_tags" name="exclude_tags[]" multiple="multiple" disabled>
                            <?php
                            foreach ($tagsList as $row) {
                                print "<option value='" . $row['tag_name'] . "'";
                                print ">" . $row['tag_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>


            <!-- Aqui será o bloco referente aos campos personalizados -->
            <div id="div_custom_fields"></div>
            <!-- Fim do bloco referente aos campos personalizados -->


            <div class="form-group row my-4">
                <div class="form-group col-md-6 d-none d-md-block"></div>


                <!-- <div class="form-group col-md-6 col-sm-6 d-flex justify-content-center">
                    <button type="submit" id="idSearch" class="btn btn-primary">Confirmar Solicitação</button>
                </div>
                <div class="form-group col-md-6 col-sm-6 d-flex justify-content-center">
                    <button type="reset" class="btn btn-secondary">Cancelar</button>
                </div> -->


                <div class="row w-100"></div>
                <div class="form-group col-md-8 d-none d-md-block">
                </div>
                <div class="form-group col-12 col-md-2 ">
                    <input type="hidden" name="custom_fields_classes_text" id="custom_fields_classes_text" value="<?= $custom_fields_classes_text; ?>">
                    <button type="submit" id="idSearch" class="btn btn-primary btn-block"><?= TRANS('BT_SEARCH'); ?></button>
                </div>
                <div class="form-group col-12 col-md-2">
                    <button type="reset" id="idReset" class="btn btn-secondary btn-block text-nowrap"><?= TRANS('BT_CLEAR'); ?></button>
                </div>

            </div>
        </form>
    </div>


    <div id="print-info" class="d-none">&nbsp;</div>
    <div class="container-fluid">
        <div id="divResult"></div>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>

    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.min.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/inputmask.min.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>
    <script src="./js/smart_search_columns.js"></script>

    <script>
        $(function() {

            let hiddenColunsCookie = getCookie('oc_sf_hidden_columns');
            let hiddenColunsCookieArray = hiddenColunsCookie.split(',');
            let customFieldsClassesText = $('#custom_fields_classes_text').val();
            let customFieldsClassesArray = customFieldsClassesText.split(',');


            var allColumns = reportAllColumns
            .concat(customFieldsClassesArray);

            var defaultHiddenColumns = hiddenColunsCookieArray
            if (defaultHiddenColumns == null || defaultHiddenColumns.length == 0 || defaultHiddenColumns == '') {
                defaultHiddenColumns = reportDefaultHiddenColumns.concat(customFieldsClassesArray);
            }

            // let columnsOrderCookie = (getCookie('oc_sf_columns_order') == '' ? reportDefaultColumnsOrder.join(',') : getCookie('oc_sf_columns_order'));
            let columnsOrderCookie = getCookie('oc_sf_columns_order');
            let colunsOrderCookieArray = columnsOrderCookie.split(',');
            var defaultColumnsOrder = colunsOrderCookieArray;

            /* Completa o form com os campos personalizados e ativos */
            $.ajax({
                url: 'smart_search_custom_fields.php',
                type: 'POST',
                data: {
                    table: 'ocorrencias'
                },

                success: function(data) {
                    $('#div_custom_fields').html(data);
                }
            });


            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            $('#modal').on('hidden.bs.modal', function(){
                $("#iframeTicketInfo").attr('src','');
            });


            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');

            /* Calendários de início e fim do período */
            $('#data_abertura_from').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        maxDate: $('#data_abertura_to').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });
            $('#data_abertura_to').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        minDate: $('#data_abertura_from').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });

            $('#data_atendimento_from').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        maxDate: $('#data_atendimento_to').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });
            $('#data_atendimento_to').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        minDate: $('#data_atendimento_from').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });

            $('#data_fechamento_from').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        maxDate: $('#data_fechamento_to').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });
            $('#data_fechamento_to').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        minDate: $('#data_fechamento_from').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });


            /* Para campos personalizados (criados após o carregamento do DOM) - bind pelas classes*/
            var obsCustomFields = $.initialize("#accordionCustomFields", function() {


                $('#toggle_check_norender_checkboxes').change(function() {
                    if ($(this).is(':checked')) {
                        selectAllNoRenderCheckboxes();
                    } else {
                        unselectAllNoRenderCheckboxes();
                    }
                });

                

                $('.custom_field_select_multi, .custom_field_select').selectpicker({
                    container: "body",
                    /* placeholder */
                    title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
                    liveSearch: true,
                    liveSearchNormalize: true,
                    liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                    noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                    maxOptions: 5,
                    maxOptionsText: "<?= TRANS('TEXT_MAX_OPTIONS', '', 1); ?>",
                    style: "",
                    styleBase: "form-control input-select-multi",
                });

                $('.custom_field_datetime').datetimepicker({
                    timepicker: true,
                    format: 'd/m/Y H:i',
                    step: 30,
                    lazyInit: true
                });

                $('.custom_field_date').datetimepicker({
                    timepicker: false,
                    format: 'd/m/Y',
                    lazyInit: true
                });

                $('.custom_field_time').datetimepicker({
                    datepicker: false,
                    format: 'H:i',
                    step: 30,
                    lazyInit: true
                });

                customDateFillControl();

                customNumberFillControl();

                /* Controle dos checkboxes para os campos do tipo data */
                $('.first-check-date').on('click', function() {

                    customDateFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find(':text').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + last_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);

                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });

                $('.last-check-date').on('click', function() {

                    customDateFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var first_checkbox_id = group_parent.find('input:first').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find(':text').attr('id');

                    if ($(this).is(':checked')) {
                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + first_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);
                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });



                $('.first-check-text').on('click', function() {

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + last_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                    }
                });

                $('.last-check-text').on('click', function() {

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var first_checkbox_id = group_parent.find('input:first').attr('id');

                    if ($(this).is(':checked')) {
                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + first_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));
                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                    }
                });


                $('.first-check').on('click', function() {

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find('select').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true)
                            .selectpicker({title: $(this).parent().attr('data-placeholder')})
                            .selectpicker('refresh');
                        $('#' + last_checkbox_id).prop('checked', false);
                        $('#' + select_input_id).val(null).trigger('change');
                        
                    } else {
                        $('#' + select_input_id).prop('disabled', false)
                            .selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"})
                            .selectpicker('refresh');
                    }
                });


                $('.last-check').on('click', function() {

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find('select').attr('id');
                    var first_checkbox_id = group_parent.find('input:first').attr('id');

                    if ($(this).is(':checked')) {
                        $('#' + select_input_id).prop('disabled', true)
                            .selectpicker({title: $(this).parent().attr('data-placeholder')})
                            .selectpicker('refresh');
                        $('#' + first_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val(null).trigger('change');
                    } else {
                        $('#' + select_input_id).prop('disabled', false)
                            .selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"})
                            .selectpicker('refresh');
                    }
                });


                $('.first-check-number').on('click', function() {

                    customNumberFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find('.custom_field_number').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find('.custom_field_number').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + last_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);

                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });

                $('.last-check-number').on('click', function() {

                    customNumberFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find('.custom_field_number').attr('id');
                    var first_checkbox_id = group_parent.find('input:first').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find('.custom_field_number').attr('id');

                    if ($(this).is(':checked')) {
                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + first_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);
                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });



            }, {
                target: document.getElementById('div_custom_fields')
            }); /* o target limita o scopo do observer */


            // if ($("#accordionCustomFields").length > 0) {
            //     obsCustomFields.disconnect();
            // }


            $('.first-check-text').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find(':text').attr('id');
                var last_checkbox_id = group_parent.find('input:last').attr('id');

                if ($(this).is(':checked')) {

                    $('#' + select_input_id).prop('disabled', true);
                    $('#' + last_checkbox_id).prop('checked', false);

                    $('#' + select_input_id).val('');
                    // $('#' + select_input_id).attr('placeholder', $(this).parent().attr('title'));
                    $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                } else {
                    $('#' + select_input_id).prop('disabled', false);
                    $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                }
            });

            $('.last-check-text').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find(':text').attr('id');
                var first_checkbox_id = group_parent.find('input:first').attr('id');

                if ($(this).is(':checked')) {
                    $('#' + select_input_id).prop('disabled', true);
                    $('#' + first_checkbox_id).prop('checked', false);

                    $('#' + select_input_id).val('');
                    // $('#' + select_input_id).attr('placeholder', $(this).parent().attr('title'));
                    $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));
                } else {
                    $('#' + select_input_id).prop('disabled', false);
                    $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                }
            });


            $('.first-check').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find('select').attr('id');
                var last_checkbox_id = group_parent.find('input:last').attr('id');

                if ($(this).is(':checked')) {

                    $('#' + select_input_id).prop('disabled', true)
                        .selectpicker({title: $(this).parent().attr('data-placeholder')})
                        .selectpicker('refresh');
                    $('#' + last_checkbox_id).prop('checked', false);
                    $('#' + select_input_id).val(null).trigger('change');
                    
                } else {
                    $('#' + select_input_id).prop('disabled', false)
                        .selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"})
                        .selectpicker('refresh');
                }
            });

            $('.last-check').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find('select').attr('id');
                var first_checkbox_id = group_parent.find('input:first').attr('id');

                if ($(this).is(':checked')) {
                    $('#' + select_input_id).prop('disabled', true)
                        .selectpicker({title: $(this).parent().attr('data-placeholder')})
                        .selectpicker('refresh');
                    $('#' + first_checkbox_id).prop('checked', false);

                    $('#' + select_input_id).val(null).trigger('change');
                } else {
                    $('#' + select_input_id).prop('disabled', false)
                        .selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"})
                        .selectpicker('refresh');
                }
            });


            $("#has_tags").on('change', function() {
                if ($(this).val() != '') {
                    $("#exclude_tags").attr('disabled', false)
                        .selectpicker('refresh');
                } else {
                    $("#exclude_tags").val('').change().prop('disabled', true)
                        .selectpicker('refresh');
                }
            });


            $('#idSearch').on('click', function(e) {
                e.preventDefault();
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });

                $(document).ajaxStop(function() {
                    loading.hide();
                });

                setLogoSrc();

                $.ajax({
                    url: 'get_full_tickets_table.php',
                    method: 'POST',
                    data: $('#form').serialize(),
                }).done(function(response) {
                    $('#divResult').html(response);
                });
                return false;
            });

            $("#idReset").click(function(e) {

                e.preventDefault();
                $("#form").trigger('reset');

                $(this).closest('form').find("input[type=text]").prop('disabled', false);
                $(this).closest('form').find("input[type=text]").attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                $(this).closest('form').find("select").prop('disabled', false);

                $('.sel2').selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"}).selectpicker('render');
                $('.custom_field_select_multi, .custom_field_select').selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"}).selectpicker('render');

                $('#data_abertura_from').prop('disabled', true);
                $('#data_abertura_from').attr('placeholder', '<?= TRANS('FIELD_CURRENT_MONTH', '', 1); ?>');
                $("#exclude_tags").val('').change().prop('disabled', true);

            });

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.sel2').selectpicker({
                /* placeholder */
                title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
                showSubtext: true,
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                maxOptions: 5,
                maxOptionsText: "<?= TRANS('TEXT_MAX_OPTIONS', '', 1); ?>",
                style: "",
                styleBase: "form-control input-select-multi",
            });


            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs2 = $.initialize("#table_info", function() {
                $('#table_info').html($('#table_info_hidden').html());
                $('#print-info').html($('#table_info').html());

                /* Collumn resize */
                var pressed = false;
                var start = undefined;
                var startX, startWidth;

                $("table td").mousedown(function(e) {
                    start = $(this);
                    pressed = true;
                    startX = e.pageX;
                    startWidth = $(this).width();
                    $(start).addClass("resizing");
                });

                $(document).mousemove(function(e) {
                    if (pressed) {
                        $(start).width(startWidth + (e.pageX - startX));
                    }
                });

                $(document).mouseup(function() {
                    if (pressed) {
                        $(start).removeClass("resizing");
                        pressed = false;
                    }
                });
                /* end Collumn resize */

            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */



            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs = $.initialize("#table_tickets_queue", function() {

                
                $(function() {
                    $('[data-toggle="popover"]').popover()
                });

                $('.popover-dismiss').popover({
                    trigger: 'focus'
                });
                
                
                var criterios = $('#divCriterios').text();

                function setTitles() {
                    var buttons = $( 'a.buttons-columnVisibility' );

                    buttons.each(function( index ) {
                        // console.log( index + ": " + $( this ).text() );
                        var tooltip =  $( this ).text() ;
                        $( this ).attr( 'title', tooltip );
                    });
                }

                var table = $('#table_tickets_queue').DataTable({

                    paging: true,
                    pageLength: 25,
                    deferRender: true,
                    fixedHeader: true,
                    // scrollX: 300, /* para funcionar a coluna fixa */
                    // fixedColumns: true,
                    columnDefs: [{
                            targets: defaultHiddenColumns,
                            visible: false,
                        },
                        {
                            targets: reportNotOrderable,
                            orderable: false,
                            searchable: false,
                        },
                        {
                            targets: reportNotSearchable,
                            searchable: false,
                        },
                    ],

                    colReorder: {
                        iFixedColumns: 1,
                        order : defaultColumnsOrder
                    },



                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    },

                });

                // new $.fn.dataTable.ColReorder(table);

                new $.fn.dataTable.Buttons(table, {

                    buttons: [{
                            extend: 'print',
                            text: '<?= TRANS('SMART_BUTTON_PRINT', '', 1) ?>',
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1) ?>',
                            message: $('#print-info').html(),
                            autoPrint: true,

                            // className: 'btn btn-primary',

                            /* customize: function(win) {
                                $(win.document.body)
                                    .css('font-size', '8pt')

                                $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                            }, */

                            customize: function(win) {
                                $(win.document.body).find('table').addClass('display').css('font-size', '10px');
                                $(win.document.body).find('tr:nth-child(odd) td').each(function(index) {
                                    $(this).css('background-color', '#f9f9f9');
                                });
                                $(win.document.body).find('h1').css('text-align', 'center');
                            },
                            exportOptions: {
                                columns: ':visible'
                            },
                        },
                        {
                            extend: 'copyHtml5',
                            text: '<?= TRANS('SMART_BUTTON_COPY', '', 1) ?>',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excel',
                            text: "Excel",
                            exportOptions: {
                                columns: ':visible'
                            },
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                        },
                        {
                            extend: 'csvHtml5',
                            text: "CVS",
                            exportOptions: {
                                columns: ':visible'
                            },

                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                        },
                        {
                            extend: 'pdfHtml5',
                            text: "PDF",

                            exportOptions: {
                                columns: ':visible',
                            },
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                            orientation: 'landscape',
                            pageSize: 'A4',

                            customize: function(doc) {
                                var criterios = $('#divCriterios').text()
                                var rdoc = doc;
                                var rcout = doc.content[doc.content.length - 1].table.body.length - 1;
                                doc.content.splice(0, 1);
                                var now = new Date();
                                var jsDate = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
                                doc.pageMargins = [30, 70, 30, 30];
                                doc.defaultStyle.fontSize = 8;
                                doc.styles.tableHeader.fontSize = 9;

                                doc['header'] = (function(page, pages) {
                                    return {
                                        columns: [
                                            {
                                                margin: [20, 10, 0, 0],
                                                image: getLogoSrc(),
                                                width: getLogoWidth()
                                            } ,
                                            {
                                                table: {
                                                    widths: ['100%'],
                                                    headerRows: 0,
                                                    body: [
                                                        [{
                                                            text: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                                                            alignment: 'center',
                                                            
                                                            fontSize: 14,
                                                            bold: true,
                                                            margin: [0, 20, 0, 0]
                                                            
                                                        }],
                                                        // [{
                                                        //     text: [{
                                                        //             text: criterios,
                                                        //             bold: true
                                                        //         }, 'Sub title details...1\n',
                                                        //         {
                                                        //             text: 'SubTitle2: ',
                                                        //             bold: true
                                                        //         }, 'Sub title details...2',
                                                        //     ]
                                                        // }]
                                                    ]
                                                },
                                                layout: 'noBorders',
                                                margin: 10,
                                            }
                                        ],
                                        
                                    }
                                });

                                doc['footer'] = (function(page, pages) {
                                    return {
                                        columns: [{
                                                alignment: 'left',
                                                text: ['Criado em: ', {
                                                    text: jsDate.toString()
                                                }]
                                            },
                                            {
                                                alignment: 'center',
                                                text: 'Total ' + rcout.toString() + ' linhas'
                                            },
                                            {
                                                alignment: 'right',
                                                text: ['página ', {
                                                    text: page.toString()
                                                }, ' de ', {
                                                    text: pages.toString()
                                                }]
                                            }
                                        ],
                                        margin: 10
                                    }
                                });

                                var objLayout = {};
                                objLayout['hLineWidth'] = function(i) {
                                    return .8;
                                };
                                objLayout['vLineWidth'] = function(i) {
                                    return .5;
                                };
                                objLayout['hLineColor'] = function(i) {
                                    return '#aaa';
                                };
                                objLayout['vLineColor'] = function(i) {
                                    return '#aaa';
                                };
                                objLayout['paddingLeft'] = function(i) {
                                    return 5;
                                };
                                objLayout['paddingRight'] = function(i) {
                                    return 35;
                                };
                                doc.content[doc.content.length - 1].layout = objLayout;

                            }

                        },
                        {
                            extend: 'colvis',
                            text: '<?= TRANS('SMART_BUTTON_MANAGE_COLLUMNS', '', 1) ?>',
                            // className: 'btn btn-primary',
                            // columns: ':gt(0)', 
                            collectionLayout: 'dropdown four-column',
                            attr: {
                                id: 'colvisID'
                            },
                        },
                        {
                            text: '<?= TRANS('REMEMBER_VISIBLE_COLUMNS', '', 1) ?>',
                            attr: {
                                title: '<?= TRANS('REMEMBER_VISIBLE_COLUMNS', '', 1) ?>',
                                id: 'customButton'
                            },
                            // action: function(e, dt, node, config) {
                            //     alert('Button activated');
                            // }
                        }
                    ]
                });

                table.buttons().container()
                    .appendTo($('.display-buttons:eq(0)', table.table().container()));



                table.on( 'buttons-action', function ( e, buttonApi, dataTable, node, config ) {
                    setTitles();
                    // console.log( 'Button '+buttonApi.text()+' was activated' );
                });
                

            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */

            // var obs2 = $.initialize("#table_info", function() {
            //     $('#table_info').html($('#table_info_hidden').html());
            //     $('#print-info').html($('#table_info').html());
            // }, {
            //     target: document.getElementById('divResult')
            // }); /* o target limita o scopo do mutate observer */



            /* Observando o gerenciamento de colunas*/
            var obsColvis = $.initialize("#table_tickets_queue", function() {

                var table2 = $('#table_tickets_queue').DataTable();

                $('#customButton').on('click', function(){
                    defaultHiddenColumns = getHiddenColumns(table2, allColumns);

                    defaultColumnsOrder = getColumnsOrder(table2);

                });

            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */


        });



        function customDateFillControl() {
            $('.custom_field_date_min').on('change focus', function() {
                var next_group_parent = $($(this).parents().eq(1).next()).next(); //object
                var next_select_input_id = next_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y',
                    onShow: function(ct) {

                        if ($('#' + next_select_input_id).val() != '') {
                            this.setOptions({
                                maxDate: $('#' + next_select_input_id).datetimepicker('getValue')
                            })
                        }

                    },
                    timepicker: false
                });

                // if ($(this).val() == '') {
                //     $('#' + next_select_input_id).val('').prop('disabled', true);
                // } else {
                //     $('#' + next_select_input_id).prop('disabled', false);
                // }
            });

            $('.custom_field_date_max').on('change focus', function() {
                var prev_group_parent = $(this).parents().prev().prev(); //object
                var prev_select_input_id = prev_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y',
                    onShow: function(ct) {
                        if ($('#' + prev_select_input_id).val() != '') {
                            this.setOptions({
                                minDate: $('#' + prev_select_input_id).datetimepicker('getValue')
                            })
                        }
                    },
                    timepicker: false
                });
            });

            $('.custom_field_datetime_min').on('change focus', function() {
                var next_group_parent = $($(this).parents().eq(1).next()).next(); //object
                var next_select_input_id = next_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y H:i',
                    onShow: function(ct) {
                        if ($('#' + next_select_input_id).val() != '') {
                            this.setOptions({
                                maxDate: $('#' + next_select_input_id).datetimepicker('getValue')
                            })
                        }
                    },
                    timepicker: true
                });

                // if ($(this).val() == '') {
                //     $('#' + next_select_input_id).val('').prop('disabled', true);
                // } else {
                //     $('#' + next_select_input_id).prop('disabled', false);
                // }
            });

            $('.custom_field_datetime_max').on('change focus', function() {
                var prev_group_parent = $(this).parents().prev().prev(); //object
                var prev_select_input_id = prev_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y H:i',
                    onShow: function(ct) {
                        if ($('#' + prev_select_input_id).val() != '') {
                            this.setOptions({
                                minDate: $('#' + prev_select_input_id).datetimepicker('getValue')
                            })
                        }
                    },
                    timepicker: true
                });
            });
        }


        function customNumberFillControl() {
            $('.custom_field_number_min').on('change focus blur', function() {
                var next_group_parent = $($(this).parents().eq(1).next()).next(); //object
                var next_select_input_id = next_group_parent.find('.custom_field_number_max').attr('id');

                if ($(this).val() != '') {
                    $('#' + next_select_input_id).attr("min", $(this).val());
                } else {
                    $('#' + next_select_input_id).removeAttr("min");
                }
            });

            $('.custom_field_number_max').on('change focus blur', function() {
                var prev_group_parent = $(this).parents().prev().prev(); //object
                var prev_select_input_id = prev_group_parent.find('.custom_field_number_min').attr('id');

                if ($(this).val() != '') {
                    $('#' + prev_select_input_id).attr("max", $(this).val());
                } else {
                    $('#' + prev_select_input_id).removeAttr("max");
                }
            });
        }


        function getHiddenColumns(table, columnsClasses) {
            // console.log(table.column('.aberto_por').visible() === true ? 'visible' : 'not visible');
            let hiddenColumns = []

            for (let i in columnsClasses) {
                // console.log(columnsClasses[i]);
                // console.log(table.column('.' + columnsClasses[i]).visible() === true ? columnsClasses[i] + ' visible' : columnsClasses[i] + ' not visible');
                if (table.column('.' + columnsClasses[i]).visible() !== true) {
                    hiddenColumns.push(columnsClasses[i]) 
                }
            }

            /* Fazer um ajax para gravar cookies com o array de colunas ocultas - Esse array deve ser consultado 
            toda a vez que o datatables for carregado */
            $.ajax({
                url: 'set_cookie_recent_columns.php',
                type: 'POST',
                data: {
                    columnsClasses: hiddenColumns,
                    app: 'smartSearch'
                },
                success: function(data) {
                    // console.log(data);
                }
            });

            defaultHiddenColumns = hiddenColumns;
            return hiddenColumns;
        }


        function getColumnsOrder(table) {
            let columnsOrder = []

            columnsOrder = table.colReorder.order();

            $.ajax({
                url: 'set_cookie_columns_order.php',
                type: 'POST',
                data: {
                    columnsOrder: columnsOrder,
                    app: 'smartSearch'
                },
                success: function(data) {
                    // console.log(data);
                }
            });

            defaultColumnsOrder = columnsOrder;
            return columnsOrder;
        }

        function selectAllNoRenderCheckboxes() {
            /* Selecionar todos os checkboxes que tenham a classe no-render */
            $('.no-render').each(function() {
                $(this).prop('checked', true);
            });
        }

        function unselectAllNoRenderCheckboxes() {
            /* Selecionar todos os checkboxes que tenham a classe no-render */
            $('.no-render').each(function() {
                $(this).prop('checked', false);
            });
        }

        function openTicketInfo(ticket) {

            // let location = 'ticket_show.php?numero=' + ticket;
            // popup_alerta_wide(location);

            $("#iframeTicketInfo").attr('src','');

            let location = './ticket_show.php?numero=' + ticket;
            $("#iframeTicketInfo").attr('src',location);
            $('#modal').modal();

        }

        function getLogoSrc() {
            return $('#logo-base64').val() ?? '';
        }

        function setLogoSrc() {

            let logoName = $('#report-mainlogo').css('background-image');

            if (logoName == 'none') {
                return;
            }
            logoName = logoName.replace(/.*\s?url\([\'\"]?/, '').replace(/[\'\"]?\).*/, '')
            logoName = logoName.split('/').pop();

            $.ajax({
                url: './get_reports_logo.php',
                method: 'POST',
                data: {
                    'logo_name': logoName
                },
                dataType: 'json',
            }).done(function(data) {

                if (!data.success) {
                    return;
                }
                $('#logo-base64').val(data.logo);
            });
        }

        function getLogoWidth() {
            let logoWidth = $('#report-mainlogo').width() ?? 150;
            return logoWidth;
        }
    </script>
</body>

</html>
