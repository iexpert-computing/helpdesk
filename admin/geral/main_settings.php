<?php
/* Copyright 2023 Flávio Ribeiro

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
 */ session_start();

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
	$_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
	exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

/* Configuraçoes gerais */
$config = getConfig($conn);
if (!$config['conf_updated_issues']) {
	redirect('update_issues_areas.php');
	exit;
}

if (!defined('ALLOWED_LANGUAGES')) {
    $langLabels = [
        'pt_BR.php' => TRANS('LANG_PT_BR'),
        'en.php' => TRANS('LANG_EN'),
        'es_ES.php' => TRANS('LANG_ES_ES')
    ];
} else {
    $langLabels = ALLOWED_LANGUAGES;
}
array_multisort($langLabels, SORT_LOCALE_STRING);


$categoriesLabels = [
	1 => $config['conf_prob_tipo_1'],
	2 => $config['conf_prob_tipo_2'],
	3 => $config['conf_prob_tipo_3'],
	4 => $config['conf_prob_tipo_4'],
	5 => $config['conf_prob_tipo_5'],
	6 => $config['conf_prob_tipo_6']
];

$categories[] = ["tag" => $config['conf_prob_tipo_1'], "value" => 1];
$categories[] = ["tag" => $config['conf_prob_tipo_2'], "value" => 2];
$categories[] = ["tag" => $config['conf_prob_tipo_3'], "value" => 3];
$categories[] = ["tag" => $config['conf_prob_tipo_4'], "value" => 4];
$categories[] = ["tag" => $config['conf_prob_tipo_5'], "value" => 5];
$categories[] = ["tag" => $config['conf_prob_tipo_6'], "value" => 6];
$categories = json_encode($categories);	

$categories_setted = $config['conf_cat_chain_at_opening'];
$textCategoriesSetted = TRANS('NO_PRE_FILTERS_DEFINED');
if (!empty($categories_setted)) {
	$textCategoriesSetted = "<ol class='categories_setted'>";
	$array_categories_setted = explode(',', $categories_setted);
	foreach ($array_categories_setted as $cat) {
		$textCategoriesSetted .= "<li>" . $categoriesLabels[$cat] . "</li>";
	}
	$textCategoriesSetted .= "</ol>";
}	



$response_at_routing = [
	'never' => TRANS('NEVER'),
	'always' => TRANS('ALWAYS'),
	'choice' => TRANS('OPERATOR_DECIDES')
];
array_multisort($response_at_routing, SORT_LOCALE_STRING);

/* Possíveis posições do campo descrição dos chamados */
$descriptionFieldPositions = [
	'default' => TRANS('COL_DEFAULT'),
	'top' => TRANS('TOP'),
	'bottom' => TRANS('BOTTOM'),
];
array_multisort($descriptionFieldPositions, SORT_LOCALE_STRING);

$descriptionCurrentPos = getConfigValue($conn, 'TICKET_DESCRIPTION_POS') ?? 'default';



$panels = [
	'1' => TRANS('PANEL_UPPER'),
	'2' => TRANS('PANEL_MAIN'),
	'3' => TRANS('HIDDEN_PANEL'),
];
$timeFreeze = [
	'0' => TRANS('HAS_NOT_TIME_FREEZE'),
	'1' => TRANS('HAS_TIME_FREEZE')
];

$ratingLabels = ratingLabels();


$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/jquery/jquery.amsify.suggestags-master/css/amsify.suggestags.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos_custom.css" />


	<style>
		hr.thick {
			border: 1px solid;
			color: #CCCCCC !important;
			/* border-radius: 5px; */
		}

		li {
			list-style: none;
			line-height: 1.5em;
		}
	</style>

	<title><?= APP_NAME; ?>&nbsp;<?= VERSAO; ?></title>
</head>

<body>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid bg-light">
		<h4 class="my-4"><i class="fas fa-cogs text-secondary"></i>&nbsp;<?= TRANS('MNL_CONF_BASIC'); ?></h4>
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

		<?php
		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
		}

		$registros = 1;
		
		/* Configuração para auto-cadastro */
		$screen = getScreenInfo($conn, 1);
		/* Listagem de status possíveis */
		$status = getStatus($conn, 0, '1,2');
		$status_done = getStatus($conn, 0, '3', '1', [12]);


		/* Status para monitorar quanto a inatividade */
		$statusToMonitorByInactivity = [];
		if ($config['stats_to_close_by_inactivity']) {
			$statusToMonitorByInactivity = explode(",", (string)$config['stats_to_close_by_inactivity']);
		}

		/* Status após retorno do solicitante */
		$statusOutInactivity = $config['stat_out_inactivity'];



		/* Base de referência de perfil de jornada - área origem do chamado ou a área de atendimento */
		$wt_areas = array();
		$wt_areas[1] = TRANS('ORIGIN_AREA');
		$wt_areas[2] = TRANS('SERVICE_AREA');


		/* Campos customizados do tipo texto */
		$textCustomFields = getCustomFields($conn, null, "ocorrencias", ["text"]);
		$textCustomFields = arraySortByColumn($textCustomFields, 'field_label');


		/* Classes para o grid */
		$colLabel = "col-sm-3 text-md-right font-weight-bold p-2 mb-4";
		$colsDefault = "small text-break border-bottom rounded p-2 bg-white"; /* border-secondary */
		$colContent = $colsDefault . " col-sm-9 col-md-9 ";
		$colContentLine = $colsDefault . " col-sm-9";
		/* Duas colunas */
		$colLabel2 = "col-sm-3 text-md-right font-weight-bold p-2 mb-4";
		$colContent2 = $colsDefault . " col-sm-3 col-md-3";


		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

			?>
			<button class="btn btn-sm btn-primary bt-edit" id="idBtEdit" name="edit"><?= TRANS("BT_EDIT"); ?></button><br /><br />
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {
			?>
				<h6 class="w-100 mt-5 "><i class="fas fa-sliders-h text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_CONFIGURATION')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MNL_LANG')); ?></div>
					<div class="<?= $colContent; ?>"><?= $langLabels[$config['conf_language']]; ?></div>


					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_DATE_FORMAT')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['conf_date_format']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_SITE')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['conf_ocomon_site']; ?></div>
				</div>

				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-bars text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('DEPRECATED_OPTIONS')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('SHOW_DEPRECATED_OPTIONS_IN_MENU')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (getConfigValue($conn, 'SHOW_DEPRECATED') ?? 1) == 1 ? " checked" : "" ;
						$noChecked = (getConfigValue($conn, 'SHOW_DEPRECATED') ?? 1) != 1 ? " checked" : "" ;
						?>
						<div class="switch-field">
							<input type="radio" id="show_deprecated" name="show_deprecated" value="yes" <?= $yesChecked; ?> disabled />
							<label for="show_deprecated"><?= TRANS('YES'); ?></label>
							<input type="radio" id="show_deprecated_no" name="show_deprecated" value="no" <?= $noChecked; ?> disabled />
							<label for="show_deprecated_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>
				
				<!-- Abertura de chamados -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-plus text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKETS_OPENING')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPENING_PRE_FILTERS_TO_ISSUES')); ?></div>
					<div class="<?= $colContentLine; ?>"><?= $textCategoriesSetted; ?></div>


					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('CONF_ONLY_REQUESTER_CAN_OPEN_AS_OTHERS')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$basic_users_can_open_as_others = getConfigValue($conn, 'ALLOW_BASIC_USERS_REQUEST_AS_OTHERS') ?? 0;

						$yesChecked = ($basic_users_can_open_as_others == 1 ? "checked" : "");
						$noChecked = ($basic_users_can_open_as_others == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="basic_users_can_request_as_others" name="basic_users_can_request_as_others" value="yes" <?= $yesChecked; ?> disabled />
							<label for="basic_users_can_request_as_others"><?= TRANS('YES'); ?></label>
							<input type="radio" id="basic_users_can_request_as_others_no" name="basic_users_can_request_as_others" value="no" <?= $noChecked; ?> disabled />
							<label for="basic_users_can_request_as_others_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

				</div>
				
				
				
				
				<!-- TICKET_CLOSING_MODE_BY_REQUESTER -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-check text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKET_CLOSING')); ?></h6>
				
				<?php
					if ($config['conf_time_to_close_after_done'] == 0) {
						echo message('info', '', TRANS('MSG_RATING_DISABLED'), '', '', true);
					}
				?>
				
				<div class="row my-2">

					<?php
						$textAboutWeekTime = ($config['conf_only_weekdays_to_count_after_done'] ? TRANS('TEXT_BUSINESS_DAYS') : TRANS('TEXT_RUNNING_DAYS'));
					?>
					
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('STATUS_DONE')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_status_done'])['status']; ?></div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('STATUS_DONE_REJECTED')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_status_done_rejected'])['status']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('TIME_TO_CLOSE_AFTER_DONE')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['conf_time_to_close_after_done']; ?>&nbsp;<?= $textAboutWeekTime; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('DEFAULT_AUTOMATIC_RATE')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $ratingLabels[$config['conf_rate_after_deadline']]; ?></div>
				</div>
				

				<!-- Encerramento automático para chamados inativos -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-check text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKETS_CLOSING_BY_INACTIVITY')); ?></h6>
				<div class="row my-2">
					<?php
						$textAboutWeekTime = ($config['only_weekdays_to_count_inactivity'] ? TRANS('TEXT_BUSINESS_DAYS') : TRANS('TEXT_RUNNING_DAYS'));
					
						$textStatusToCloseByInactivity = TRANS('WITHOUT_DEFINITION');

						if (!empty($statusToMonitorByInactivity)) {
							$textStatusToCloseByInactivity = "";
							foreach ($statusToMonitorByInactivity as $statInactive) {
								$textStatusToCloseByInactivity .= '<li>' . getStatusInfo($conn, $statInactive)['status'] . '</li>';
							}
						}
						
					?>
					
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('STATUS_TO_MONITOR_BY_INACTIVITY')); ?></div>
					<div class="<?= $colContent; ?>"><?= $textStatusToCloseByInactivity; ?></div>

					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('STATUS_REQUESTER_IS_ALIVE')); ?></div>
					<div class="<?= $colContent; ?>"><?= getStatusInfo($conn, $statusOutInactivity)['status']; ?></div>

					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('DAYS_TO_CLOSE_BY_INACTIVITY')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['days_to_close_by_inactivity']; ?>&nbsp;<?= $textAboutWeekTime; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('DEFAULT_AUTOMATIC_RATE')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $ratingLabels[$config['rate_after_close_by_inactivity']]; ?></div>
				</div>

				
				

				<!-- TICKETS COST OPTIONS-->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-file-invoice-dollar text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TTL_TICKETS_COST')); ?></h6>
				<div class="row my-2">

					<?php
						$fieldLabel = TRANS('MSG_NOT_DEFINED');
						if (!empty($config['tickets_cost_field'])) {
							$fieldLabel = getCustomFields($conn, $config['tickets_cost_field'])['field_label'];
						}
					?>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('TICKETS_COST_FIELD')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $fieldLabel; ?></div>
					
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_STATUS_TO_WAITING_COST_AUTHORIZATION')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['status_waiting_cost_auth'])['status']; ?></div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_STATUS_COST_AUTHORIZATED')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['status_cost_authorized'])['status']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_STATUS_COST_REFUSED')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['status_cost_refused'])['status']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_STATUS_COST_UPDATED')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['status_cost_updated'])['status']; ?></div>

				</div>
				

				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-user-plus text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('USER_SELF_REGISTER')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('ALLOW_SELF_REGISTER')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = ($screen['conf_user_opencall'] == 1 ? "checked" : "");
						$noChecked = ($screen['conf_user_opencall'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="allow_self_register" name="allow_self_register" value="yes" <?= $yesChecked; ?> disabled />
							<label for="allow_self_register"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_self_register_no" name="allow_self_register" value="no" <?= $noChecked; ?> disabled />
							<label for="allow_self_register_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('SELF_REGISTER_CLIENT')); ?></div>
					<div class="<?= $colContent2; ?>">
					<?php
						$autoClient = (!empty($screen['conf_scr_auto_client']) ? getClients($conn, $screen['conf_scr_auto_client'])['nickname'] : TRANS('FILL_EMPTY'));
					?>
						<?= $autoClient ?>
					</div>
					

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('SELF_REGISTER_AREA')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?= getAreaInfo($conn, $screen['conf_ownarea'])['area_name']; ?>
					</div>
					<div class="w-100"></div>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_FIELD_MSG')); ?></div>
					<div class="<?= $colContent; ?>">
						<?= $screen['conf_scr_msg']; ?>
					</div>
				</div>



				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-edit text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TREATING_OWN_TICKET')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('ALLOW_TREATING_OWN_TICKET')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['conf_allow_op_treat_own_ticket'] == 1 ? "checked" : "");
						$noChecked = ($config['conf_allow_op_treat_own_ticket'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="treat_own_ticket" name="treat_own_ticket" value="yes" <?= $yesChecked; ?> disabled />
							<label for="treat_own_ticket"><?= TRANS('YES'); ?></label>
							<input type="radio" id="treat_own_ticket_no" name="treat_own_ticket" value="no" <?= $noChecked; ?> disabled />
							<label for="treat_own_ticket_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>


				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-eye text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('VISIBILITY_BTW_AREAS')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('ISOLATE_AREAS_VISIBILITY')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['conf_isolate_areas'] == 1 ? "checked" : "");
						$noChecked = ($config['conf_isolate_areas'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="isolate_areas" name="isolate_areas" value="yes" <?= $yesChecked; ?> disabled />
							<label for="isolate_areas"><?= TRANS('YES'); ?></label>
							<input type="radio" id="isolate_areas_no" name="isolate_areas" value="no" <?= $noChecked; ?> disabled />
							<label for="isolate_areas_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>


				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-clock text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('WORKTIME_CALC')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_WT_PROFILE_AREAS')); ?></div>
					<div class="<?= $colContent; ?>"><?= $wt_areas[$config['conf_wt_areas']]; ?></div>
				</div>


				<!-- section -->
				<!-- Agendamento -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-calendar-alt text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CFG_TICKET_SCHEDULING')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_SCHEDULE_STATUS')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_schedule_status'])['status']; ?></div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_SCHEDULE_STATUS_2')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_schedule_status_2'])['status']; ?></div>
				</div>

				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_STATUS_SCHEDULED_TO_WORKER')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_status_scheduled_to_worker'])['status']; ?></div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_STATUS_IN_WORKER_QUEUE')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_status_in_worker_queue'])['status']; ?></div>
				</div>

				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('SEL_FOWARD_STATUS')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getStatusInfo($conn, $config['conf_foward_when_open'])['status']; ?></div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('RESPONSE_AT_ROUTING')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $response_at_routing[$config['set_response_at_routing']]; ?></div>
				</div>


				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-align-right text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKETS_CUSTOM_FIELDS')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('CUSTOM_FIELDS_EDIT_FOLLOWS_OPEN')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['conf_cfield_only_opened'] == 1 ? "checked" : "");
						$noChecked = ($config['conf_cfield_only_opened'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="cfield_only_opened" name="cfield_only_opened" value="yes" <?= $yesChecked; ?> disabled />
							<label for="cfield_only_opened"><?= TRANS('YES'); ?></label>
							<input type="radio" id="cfield_only_opened_no" name="cfield_only_opened" value="no" <?= $noChecked; ?> disabled />
							<label for="cfield_only_opened_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>


				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-handshake text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SLA_TOLERANCE')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('PERCENTAGE')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['conf_sla_tolerance']; ?>%</div>
				</div>



				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-exclamation-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_JUSTIFICATION_SLA_OUT')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_DESC_SLA_OUT')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['conf_desc_sla_out'] == 1 ? "checked" : "");
						$noChecked = ($config['conf_desc_sla_out'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="desc_sla_out" name="desc_sla_out" value="yes" <?= $yesChecked; ?> disabled />
							<label for="desc_sla_out"><?= TRANS('YES'); ?></label>
							<input type="radio" id="desc_sla_out_no" name="desc_sla_out" value="no" <?= $noChecked; ?> disabled />
							<label for="desc_sla_out_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>

				<!-- Reabertura -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-external-link-alt text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_REOPEN')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_ALLOW_REOPEN')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = ($config['conf_allow_reopen'] == 1 ? "checked" : "");
						$noChecked = ($config['conf_allow_reopen'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="allowReopen" name="allowReopen" value="yes" <?= $yesChecked; ?> disabled />
							<label for="allowReopen"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allowReopen_no" name="allowReopen" value="no" <?= $noChecked; ?> disabled />
							<label for="allowReopen_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('DEADLINE_DAYS_TO_REOPEN')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['conf_reopen_deadline']; ?></div>
				</div>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('STATUS_WHEN_REOPEN')); ?></div>
					<div class="<?= $colContent; ?>"><?= getStatusInfo($conn, $config['conf_status_reopen'])['status']; ?></div>
				</div>


				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-upload text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_UPLOAD')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_MAXSIZE')); ?></div>
					<?php
					$emMbytes = round($config['conf_upld_size'] / 1024 / 1024, 2);
					?>
					<div class="<?= $colContent2; ?>"><?= $emMbytes; ?> (MB)</div>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_QTD_MAX_ANEXOS')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['conf_qtd_max_anexos']; ?></div>
				</div>

				<!-- Arquivos permitidos para upload -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-file-import text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_IMG')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%IMG%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%IMG%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_img" name="upld_img" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_img"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_img_no" name="upld_img" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_img_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_TXT')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%TXT%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%TXT%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_txt" name="upld_txt" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_txt"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_txt_no" name="upld_txt" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_txt_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_PDF')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%PDF%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%PDF%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_pdf" name="upld_pdf" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_pdf"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_pdf_no" name="upld_pdf" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_pdf_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_ODF')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%ODF%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%ODF%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_odf" name="upld_odf" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_odf"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_odf_no" name="upld_odf" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_odf_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_OOO')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%OOO%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%OOO%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_ooo" name="upld_ooo" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_ooo"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_ooo_no" name="upld_ooo" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_ooo_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_MSO')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%MSO%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%MSO%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_mso" name="upld_mso" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_mso"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_mso_no" name="upld_mso" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_mso_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_NMSO')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%NMSO%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%NMSO%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_nmso" name="upld_nmso" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_nmso"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_nmso_no" name="upld_nmso" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_nmso_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_RTF')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%RTF%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%RTF%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_rtf" name="upld_rtf" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_rtf"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_rtf_no" name="upld_rtf" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_rtf_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_HTML')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%HTML%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%HTML%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_html" name="upld_html" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_html"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_html_no" name="upld_html" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_html_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_WAV')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%WAV%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%WAV%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_wav" name="upld_wav" value="yes" <?= $yesChecked; ?> disabled />
							<label for="upld_wav"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_wav_no" name="upld_wav" value="no" <?= $noChecked; ?> disabled />
							<label for="upld_wav_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>

				<!-- section -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-image text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_UPLOAD_IMG')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_MAXWIDTH')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['conf_upld_width']; ?> px</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_MAXHEIGHT')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['conf_upld_height']; ?> px</div>
				</div>


				<!-- section Barra de formatação de textos -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-align-right text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('FORMATTING_BAR')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_MURAL')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_formatBar'], '%mural%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_formatBar'], '%mural%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="formatMural" name="formatMural" value="yes" <?= $yesChecked; ?> disabled />
							<label for="formatMural"><?= TRANS('YES'); ?></label>
							<input type="radio" id="formatMural_no" name="formatMural" value="no" <?= $noChecked; ?> disabled />
							<label for="formatMural_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_OCORRENCIAS')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (strpos($config['conf_formatBar'], '%oco%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_formatBar'], '%oco%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="formatOco" name="formatOco" value="yes" <?= $yesChecked; ?> disabled />
							<label for="formatOco"><?= TRANS('YES'); ?></label>
							<input type="radio" id="formatOco_no" name="formatOco" value="no" <?= $noChecked; ?> disabled />
							<label for="formatOco_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>


				<!-- Configuração para posicionamento do campo de Descrição dos chamados -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-ellipsis-h text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('MISCELLENEOUS')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('DESCRIPTION_FIELD_POSITION')); ?></div>
					<div class="<?= $colContent; ?>"><?= $descriptionFieldPositions[$descriptionCurrentPos]; ?></div>
				</div>
				<div class="w-100"></div>


				<!-- section -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-bell text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_SEND_MAIL_WRTY')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_DAYS_BEFORE')); ?></div>
					<div class="<?= $colContent2; ?>"><?= $config['conf_days_bf']; ?></div>
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('OPT_SEL_AREA')); ?></div>
					<div class="<?= $colContent2; ?>"><?= getAreaInfo($conn, $config['conf_wrty_area'])['area_name']; ?></div>
				</div>

				<!-- Quantidade máxima de ativos que podem ser cadastradas em um lote -->
				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-boxes text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ASSETS_BATCH_REGISTER')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MAX_AMOUNT_EACH_ASSET_BATCH')); ?></div>
					<div class="<?= $colContent; ?>"><?= getConfigValue($conn, 'MAX_AMOUNT_BATCH_ASSETS_RECORD') ?? 1; ?></div>
				</div>

				<h6 class="w-100 mt-5 border-top p-4"><i class="fas fa-truck-loading text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ASSETS_ALLOCATION_TO_USERS')); ?></h6>
				<div class="row my-2">
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('ALLOW_ALLOCATION_BTW_CLIENTS')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (getConfigValue($conn, 'ALLOW_USER_GET_ASSETS_BTW_CLIENTS') ?? 0) == 1 ? " checked" : "" ;
						$noChecked = (getConfigValue($conn, 'ALLOW_USER_GET_ASSETS_BTW_CLIENTS') ?? 0) != 1 ? " checked" : "" ;
						?>
						<div class="switch-field">
							<input type="radio" id="allow_assets_btw_clients" name="allow_assets_btw_clients" value="yes" <?= $yesChecked; ?> disabled />
							<label for="allow_assets_btw_clients"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_assets_btw_clients_no" name="allow_assets_btw_clients" value="no" <?= $noChecked; ?> disabled />
							<label for="allow_assets_btw_clients_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				
					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('ALLOW_ALLOCATION_BTW_CLIENTS_ONLY_TO_OPS')); ?></div>
					<div class="<?= $colContent2; ?>">
						<?php
						$yesChecked = (getConfigValue($conn, 'ALLOW_ONLY_OPS_GET_ASSETS_BTW_CLIENTS') ?? 0) == 1 ? " checked" : "" ;
						$noChecked = (getConfigValue($conn, 'ALLOW_ONLY_OPS_GET_ASSETS_BTW_CLIENTS') ?? 0) != 1 ? " checked" : "" ;
						?>
						<div class="switch-field">
							<input type="radio" id="allow_only_ops_assets_btw_clients" name="allow_only_ops_assets_btw_clients" value="yes" <?= $yesChecked; ?> disabled />
							<label for="allow_only_ops_assets_btw_clients"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_only_ops_assets_btw_clients_no" name="allow_only_ops_assets_btw_clients" value="no" <?= $noChecked; ?> disabled />
							<label for="allow_only_ops_assets_btw_clients_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel2; ?>"><?= firstLetterUp(TRANS('DISABLED_USERS_ASSETS_DEPARTMENT')); ?></div>
					<?php
						$department = TRANS('MSG_NOT_DEFINED');
						$autoDepartmentId = getConfigValue($conn, 'ASSETS_AUTO_DEPARTMENT') ?? 0;
						if ($autoDepartmentId) {
							$autoDepartmentInfo = getDepartments($conn, null, $autoDepartmentId);

							$keys = ['local', 'nickname', 'unidade'];
							
							$departmentInfoArray = [];
							foreach ($keys as $key) {
								$departmentInfoArray[] = $autoDepartmentInfo[$key];
							}
							$department = implode(" - ", array_filter($departmentInfoArray));
						}


					?>
					<div class="<?= $colContent; ?>"><?= $department; ?></div>


				</div>


				<div class="row w-100">
					<div class="col-md-10 d-none d-md-block">
					</div>
					<div class="col-12 col-md-2 ">
						<button class="btn btn-primary bt-edit " name="edit"><?= TRANS("BT_EDIT"); ?></button>
					</div>
				</div>

			<?php
			}
		} else
		if ((isset($_GET['action'])  && ($_GET['action'] == "edit")) && !isset($_POST['submit'])) {

			?>
			<h6><?= TRANS('EDITION'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">

					<h6 class="w-100 mt-5 ml-5"><i class="fas fa-sliders-h text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_CONFIGURATION')); ?></h6>
					<?php
					$files = array();
					$files = getDirFileNames('../../includes/languages/');
					?>
					<label for="lang_file" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('MNL_LANG')); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control bs-select" name="lang_file" required id="lang_file">
							<?php
							foreach ($langLabels as $key => $label) {
                                if (in_array($key, $files)) {
                                    echo '<option value="' . $key . '"';
                                    echo ($key == $config['conf_language'] ? ' selected' : '') . '>' . $label;
                                    echo '</option>';
                                }
                            }
							?>
						</select>
					</div>
					<label for="date_format" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_DATA_FORMAT'); ?>"><?= firstLetterUp(TRANS('OPT_DATE_FORMAT')); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control" name="date_format" id="date_format" required value="<?= $config['conf_date_format']; ?>" placeholder="<?= TRANS('SUGGESTION'); ?>: d/m/Y H:i:s" />
					</div>
					<label for="site" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_SITE'); ?>"><?= firstLetterUp(TRANS('OPT_SITE')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="site" id="site" required value="<?= $config['conf_ocomon_site']; ?>" />
					</div>

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-bars text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('DEPRECATED_OPTIONS')); ?></h6>
					<label for="max_amount_batch_assets_record" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SHOW_DEPRECATED_OPTIONS_IN_MENU'); ?>"><?= firstLetterUp(TRANS('SHOW_DEPRECATED_OPTIONS_IN_MENU')); ?></label>

					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = (getConfigValue($conn, 'SHOW_DEPRECATED') ?? 1) == 1 ? " checked" : "" ;
							$noChecked = (getConfigValue($conn, 'SHOW_DEPRECATED') ?? 1) != 1 ? " checked" : "" ;
							?>
							<input type="radio" id="show_deprecated" name="show_deprecated" value="yes" <?= $yesChecked; ?> />
							<label for="show_deprecated"><?= TRANS('YES'); ?></label>
							<input type="radio" id="show_deprecated_no" name="show_deprecated" value="no" <?= $noChecked; ?> />
							<label for="show_deprecated_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-plus text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKETS_OPENING')); ?></h6>
					<label for="pre_filters" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_OPENING_PRE_FILTERS_TO_ISSUES'); ?>"><?= firstLetterUp(TRANS('OPENING_PRE_FILTERS_TO_ISSUES')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="pre_filters" id="pre_filters" value="<?= $config['conf_cat_chain_at_opening']; ?>" placeholder="<?= TRANS('ADD_OR_REMOVE'); ?>" />
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CONF_ONLY_REQUESTER_CAN_OPEN_AS_OTHERS'); ?>"><?= firstLetterUp(TRANS('CONF_ONLY_REQUESTER_CAN_OPEN_AS_OTHERS')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php

							$basic_users_can_open_as_others = getConfigValue($conn, 'ALLOW_BASIC_USERS_REQUEST_AS_OTHERS') ?? 0;

							$yesChecked = ($basic_users_can_open_as_others == 1 ? "checked" : "");
							$noChecked = ($basic_users_can_open_as_others == 0 ? "checked" : "");
							?>
							<input type="radio" id="basic_users_can_request_as_others" name="basic_users_can_request_as_others" value="yes" <?= $yesChecked; ?> />
							<label for="basic_users_can_request_as_others"><?= TRANS('YES'); ?></label>
							<input type="radio" id="basic_users_can_request_as_others_no" name="basic_users_can_request_as_others" value="no" <?= $noChecked; ?> />
							<label for="basic_users_can_request_as_others_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-check text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKET_CLOSING')); ?></h6>
					
					<label for="status_done" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_STATUS_DONE'); ?>"><?= firstLetterUp(TRANS('STATUS_DONE')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_done" required id="status_done">
							<?php
							$subtext = "";
							foreach ($status_done as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_status_done']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<label for="status_done_rejected" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_STATUS_DONE_REJECTED'); ?>"><?= firstLetterUp(TRANS('STATUS_DONE_REJECTED')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_done_rejected" required id="status_done_rejected">
							<?php
							// $status = getStatus($conn, 0, '1,2');
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_status_done_rejected']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<label for="time_to_close_after_done" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_TIME_TO_CLOSE_AFTER_DONE'); ?>"><?= firstLetterUp(TRANS('TIME_TO_CLOSE_AFTER_DONE')); ?></label>
					<div class="form-group col-md-4">
					<div class="input-group">	
						<input type="number" class="form-control" name="time_to_close_after_done" id="time_to_close_after_done" min="0" value="<?= $config['conf_time_to_close_after_done']; ?>" />
						<div class="input-group-append">

							<?php
								$checked = ($config['conf_only_weekdays_to_count_after_done'] ? " checked" : "");
							?>
							
                            <div class="input-group-text" title="<?= TRANS('COUNTS_ONLY_WEEKDAYS'); ?>" data-placeholder="<?= TRANS('COUNTS_ONLY_WEEKDAYS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-business-time"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="only_weekdays_to_count" id="only_weekdays_to_count" value="1" <?= $checked; ?>>
                            </div>
                        </div>
					</div>
					</div>

					<label for="default_automatic_rate" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DEFAULT_AUTOMATIC_RATE'); ?>"><?= firstLetterUp(TRANS('DEFAULT_AUTOMATIC_RATE')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="default_automatic_rate" required id="default_automatic_rate">
							<?php
							foreach ($ratingLabels as $key => $label) {
							?>
								<option value="<?= $key; ?>" <?= ($key == $config['conf_rate_after_deadline']) ? 'selected' : ''; ?>><?= $label; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<!-- Encerramento automático por inatividade -->
					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-check text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKETS_CLOSING_BY_INACTIVITY')); ?></h6>

					<label for="status_to_monitor" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_TICKETS_CLOSING_BY_INACTIVITY'); ?>"><?= firstLetterUp(TRANS('STATUS_TO_MONITOR_BY_INACTIVITY')); ?></label>
					<div class="form-group col-md-10 ">
						<select class="form-control bs-select" name="status_to_monitor[]" required id="status_to_monitor" multiple="multiple">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= (in_array($stat['stat_id'], $statusToMonitorByInactivity)) ? ' selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="status_to_monitor" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('STATUS_REQUESTER_IS_ALIVE'); ?>"><?= firstLetterUp(TRANS('STATUS_REQUESTER_IS_ALIVE')); ?></label>
					<div class="form-group col-md-10 ">
						<select class="form-control bs-select" name="status_out_inactivity" required id="status_out_inactivity">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $statusOutInactivity) ? ' selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="days_to_close_by_inactivity" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DAYS_TO_CLOSE_BY_INACTIVITY'); ?>"><?= firstLetterUp(TRANS('DAYS_TO_CLOSE_BY_INACTIVITY')); ?></label>
					<div class="form-group col-md-4">
					<div class="input-group">	
						<input type="number" class="form-control" name="days_to_close_by_inactivity" id="days_to_close_by_inactivity" min="1" value="<?= $config['days_to_close_by_inactivity']; ?>" />
						<div class="input-group-append">

							<?php
								$checked = ($config['only_weekdays_to_count_inactivity'] ? " checked" : "");
							?>
							
                            <div class="input-group-text" title="<?= TRANS('COUNTS_ONLY_WEEKDAYS'); ?>" data-placeholder="<?= TRANS('COUNTS_ONLY_WEEKDAYS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-business-time"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="only_weekdays_to_count_inactivity" id="only_weekdays_to_count_inactivity" value="1" <?= $checked; ?>>
                            </div>
                        </div>
					</div>
					</div>

					<label for="default_inactivity_automatic_rate" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('DEFAULT_AUTOMATIC_RATE'); ?>"><?= firstLetterUp(TRANS('DEFAULT_AUTOMATIC_RATE')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="default_inactivity_automatic_rate" required id="default_inactivity_automatic_rate">
							<?php
							foreach ($ratingLabels as $key => $label) {
							?>
								<option value="<?= $key; ?>" <?= ($key == $config['rate_after_close_by_inactivity']) ? 'selected' : ''; ?>><?= $label; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<!-- Final da seção sobre o encerramento automático por inatividade -->




					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-file-invoice-dollar text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TTL_TICKETS_COST_AND_AUTHORIZATION_FLOW')); ?></h6>
					
					<label for="tickets_cost_field" class="col-md-2 col-form-label col-form-label-sm text-md-right" title="<?= TRANS('TICKETS_COST_FIELD'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_TICKETS_COST_FIELD'); ?>"><?= firstLetterUp(TRANS('TICKETS_COST_FIELD')); ?></label>
					<div class="form-group col-md-4 ">
						
						<?php
							if (empty($textCustomFields)) {
								echo message('danger', 'Ooops!', TRANS('MSG_NO_CUSTOM_FIELDS_TICKETS_COST'), '', '', 1 );
							} else {
								?>
								<select class="form-control bs-select" name="tickets_cost_field" required id="tickets_cost_field">
									<option value=""><?= TRANS('SEL_NONE'); ?></option>
								<?php
								foreach ($textCustomFields as $customField) {
								?>
									<option value="<?= $customField['id']; ?>" <?= ($customField['id'] == $config['tickets_cost_field']) ? 'selected' : ''; ?>><?= $customField['field_label']; ?></option>
								<?php
								}
								?>
								</select>
								<?php
							}
						?>
					</div>
					
					<label for="status_waiting_cost_auth" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_STATUS_TO_WAITING_COST_AUTHORIZATION'); ?>"><?= firstLetterUp(TRANS('OPT_STATUS_TO_WAITING_COST_AUTHORIZATION')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_waiting_cost_auth" required id="status_waiting_cost_auth">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['status_waiting_cost_auth']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<label for="status_cost_authorized" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_STATUS_COST_AUTHORIZATED'); ?>"><?= firstLetterUp(TRANS('OPT_STATUS_COST_AUTHORIZATED')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_cost_authorized" required id="status_cost_authorized">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['status_cost_authorized']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<label for="status_cost_refused" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_STATUS_COST_REFUSED'); ?>"><?= firstLetterUp(TRANS('OPT_STATUS_COST_REFUSED')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_cost_refused" required id="status_cost_refused">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['status_cost_refused']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="status_cost_updated" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_OPT_STATUS_COST_UPDATED'); ?>"><?= firstLetterUp(TRANS('OPT_STATUS_COST_UPDATED')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_cost_updated" required id="status_cost_updated">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['status_cost_updated']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>



					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-user-plus text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('USER_SELF_REGISTER')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_SELF_REGISTER'); ?>"><?= firstLetterUp(TRANS('ALLOW_SELF_REGISTER')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($screen['conf_user_opencall'] == 1 ? "checked" : "");
							$noChecked = ($screen['conf_user_opencall'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="allow_self_register" name="allow_self_register" value="yes" <?= $yesChecked; ?> />
							<label for="allow_self_register"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_self_register_no" name="allow_self_register" value="no" <?= $noChecked; ?> />
							<label for="allow_self_register_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label for="self_register_client" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_SELF_REGISTER_CLIENT'); ?>"><?= firstLetterUp(TRANS('SELF_REGISTER_CLIENT')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="self_register_client" required id="self_register_client">
							<option value=""><?= TRANS('FILL_EMPTY'); ?></option>
							<?php
							$clients = getClients($conn, null, 2);
							foreach ($clients as $client) {
							?>
								<option value="<?= $client['id']; ?>" <?= ($client['id'] == $screen['conf_scr_auto_client']) ? 'selected' : ''; ?>><?= $client['nickname']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="self_register_area" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_SELF_REGISTER_AREA'); ?>"><?= firstLetterUp(TRANS('SELF_REGISTER_AREA')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="self_register_area" required id="self_register_area">
							<?php
							$areas = getAreas($conn, 0, 1, 0);
							foreach ($areas as $area) {
							?>
								<option value="<?= $area['sis_id']; ?>" <?= ($area['sis_id'] == $screen['conf_ownarea']) ? 'selected' : ''; ?>><?= $area['sistema']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<div class="w-100"></div>

					<label for="msg" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_OPENING_MESSAGE'); ?>"><?= firstLetterUp(TRANS('OPT_FIELD_MSG')); ?></label>
					<div class="form-group col-md-10 ">
						<textarea class="form-control" name="msg" id="msg"><?= $screen['conf_scr_msg']; ?></textarea>
					</div>


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-edit text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TREATING_OWN_TICKET')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_TREATING_OWN_TICKET'); ?>"><?= firstLetterUp(TRANS('ALLOW_TREATING_OWN_TICKET')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['conf_allow_op_treat_own_ticket'] == 1 ? "checked" : "");
							$noChecked = ($config['conf_allow_op_treat_own_ticket'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="treat_own_ticket" name="treat_own_ticket" value="yes" <?= $yesChecked; ?> />
							<label for="treat_own_ticket"><?= TRANS('YES'); ?></label>
							<input type="radio" id="treat_own_ticket_no" name="treat_own_ticket" value="no" <?= $noChecked; ?> />
							<label for="treat_own_ticket_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-eye text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('VISIBILITY_BTW_AREAS')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_VISIBILITY_BTW_AREAS'); ?>"><?= firstLetterUp(TRANS('ISOLATE_AREAS_VISIBILITY')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['conf_isolate_areas'] == 1 ? "checked" : "");
							$noChecked = ($config['conf_isolate_areas'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="isolate_areas" name="isolate_areas" value="yes" <?= $yesChecked; ?> />
							<label for="isolate_areas"><?= TRANS('YES'); ?></label>
							<input type="radio" id="isolate_areas_no" name="isolate_areas" value="no" <?= $noChecked; ?> />
							<label for="isolate_areas_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-clock text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('WORKTIME_CALC')); ?></h6>
					<label for="worktime_area_reference" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_WT_PROFILE_AREAS'); ?>"><?= firstLetterUp(TRANS('OPT_WT_PROFILE_AREAS')); ?></label>
					<div class="form-group col-md-10 ">
						<select class="form-control bs-select" name="worktime_area_reference" required id="worktime_area_reference">
							<?php
							foreach ($wt_areas as $key => $value) {
							?>
								<option value="<?= $key; ?>" <?= ($key == $config['conf_wt_areas'] ? 'selected' : ''); ?>><?= $value; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-calendar-alt text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CFG_TICKET_SCHEDULING')); ?></h6>
					<label for="open_scheduling_status" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_OPENING_SCHEDULE_STATUS'); ?>"><?= firstLetterUp(TRANS('OPT_SCHEDULE_STATUS')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="open_scheduling_status" required id="open_scheduling_status">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_schedule_status']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<label for="edit_scheduling_status" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_EDITING_SCHEDULE_STATUS'); ?>"><?= firstLetterUp(TRANS('OPT_SCHEDULE_STATUS_2')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="edit_scheduling_status" required id="edit_scheduling_status">
							<?php
							// $status = getStatus($conn, 0, '1,2');
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_schedule_status_2']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="scheduled_to_worker_status" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_STATUS_SCHEDULED_TO_WORKER'); ?>"><?= firstLetterUp(TRANS('OPT_STATUS_SCHEDULED_TO_WORKER')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="scheduled_to_worker_status" required id="scheduled_to_worker_status">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_status_scheduled_to_worker']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<label for="status_in_worker_queue" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_STATUS_IN_WORKER_QUEUE'); ?>"><?= firstLetterUp(TRANS('OPT_STATUS_IN_WORKER_QUEUE')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="status_in_worker_queue" required id="status_in_worker_queue">
							<?php
							// $status = getStatus($conn, 0, '1,2');
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_status_in_worker_queue']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="forward_status" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_OPENING_FORWARD_STATUS'); ?>"><?= firstLetterUp(TRANS('SEL_FOWARD_STATUS')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="forward_status" required id="forward_status">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_foward_when_open']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label for="response_at_routing" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_RESPONSE_AT_ROUTING'); ?>"><?= firstLetterUp(TRANS('RESPONSE_AT_ROUTING')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="response_at_routing" required id="response_at_routing">
							<?php
							foreach ($response_at_routing as $key => $value) {
							?>
								<option value="<?= $key; ?>" <?= ($key == $config['set_response_at_routing']) ? 'selected' : ''; ?>><?= $value; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<!-- Campos personalizados -->
					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-align-right text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TICKETS_CUSTOM_FIELDS')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" title="<?= TRANS('CUSTOM_FIELDS_EDIT_FOLLOWS_OPEN'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CUSTOM_FIELDS_EDIT_FOLLOWS_OPEN'); ?>"><?= firstLetterUp(TRANS('CUSTOM_FIELDS_EDIT_FOLLOWS_OPEN')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['conf_cfield_only_opened'] == 1 ? "checked" : "");
							$noChecked = ($config['conf_cfield_only_opened'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="cfield_only_opened" name="cfield_only_opened" value="yes" <?= $yesChecked; ?> />
							<label for="cfield_only_opened"><?= TRANS('YES'); ?></label>
							<input type="radio" id="cfield_only_opened_no" name="cfield_only_opened" value="no" <?= $noChecked; ?> />
							<label for="cfield_only_opened_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-handshake text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SLA_TOLERANCE')); ?></h6>
					<label for="sla_tolerance" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SLA_TOLERANCE'); ?>"><?= firstLetterUp(TRANS('PERCENTAGE')); ?></label>
					<div class="form-group col-md-10">
						<input type="number" class="form-control" name="sla_tolerance" id="sla_tolerance" required value="<?= $config['conf_sla_tolerance']; ?>" />
					</div>


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-exclamation-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_JUSTIFICATION_SLA_OUT')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_JUSTIFICATION_SLA_OUT'); ?>"><?= firstLetterUp(TRANS('OPT_DESC_SLA_OUT')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['conf_desc_sla_out'] == 1 ? "checked" : "");
							$noChecked = ($config['conf_desc_sla_out'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="justificativa" name="justificativa" value="yes" <?= $yesChecked; ?> />
							<label for="justificativa"><?= TRANS('YES'); ?></label>
							<input type="radio" id="justificativa_no" name="justificativa" value="no" <?= $noChecked; ?> />
							<label for="justificativa_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<!-- Reabertura -->
					<h6 class="w-100 mt-5 ml-5 border-top p-4" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_REOPEN_AFTER_VERSION_5'); ?>"><i class="fas fa-external-link-alt text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_REOPEN')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_ALLOW_REOPEN'); ?>"><?= firstLetterUp(TRANS('OPT_ALLOW_REOPEN')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['conf_allow_reopen'] == 1 ? "checked" : "");
							$noChecked = ($config['conf_allow_reopen'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="allow_reopen" name="allow_reopen" value="yes" <?= $yesChecked; ?> />
							<label for="allow_reopen"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_reopen_no" name="allow_reopen" value="no" <?= $noChecked; ?> />
							<label for="allow_reopen_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
					<label for="reopen_deadline" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DEADLINE_DAYS_TO_REOPEN'); ?>"><?= firstLetterUp(TRANS('DEADLINE_DAYS_TO_REOPEN')); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control" name="reopen_deadline" id="reopen_deadline" min="0" value="<?= $config['conf_reopen_deadline']; ?>" />
					</div>
					
					<label for="reopening_status" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('STATUS_WHEN_REOPEN'); ?>"><?= firstLetterUp(TRANS('STATUS_WHEN_REOPEN')); ?></label>
					<div class="form-group col-md-10 ">
						<select class="form-control bs-select" name="reopening_status" required id="reopening_status">
							<?php
							$subtext = "";
							foreach ($status as $stat) {
								$subtext = $panels[$stat['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$stat['stat_time_freeze']];
							?>
								<option data-subtext="<?= $subtext; ?>" value="<?= $stat['stat_id']; ?>" <?= ($stat['stat_id'] == $config['conf_status_reopen']) ? 'selected' : ''; ?>><?= $stat['status']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-upload text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_UPLOAD')); ?></h6>
					<label for="img_max_size" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_MAX_FILE_SIZE'); ?>"><?= firstLetterUp(TRANS('OPT_MAXSIZE')); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control" name="img_max_size" id="img_max_size" required value="<?= round($config['conf_upld_size'] / 1024 / 1024, 2); ?>" />
					</div>
					<label for="max_number_attachs" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_QTD_MAX_ANEXOS')); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control" name="max_number_attachs" id="max_number_attachs" required value="<?= $config['conf_qtd_max_anexos']; ?>" />
					</div>




					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-file-import text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_IMG')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%IMG%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%IMG%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_img" name="upld_img" value="yes" <?= $yesChecked; ?> />
							<label for="upld_img"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_img_no" name="upld_img" value="no" <?= $noChecked; ?> />
							<label for="upld_img_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_TXT')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%TXT%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%TXT%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_txt" name="upld_txt" value="yes" <?= $yesChecked; ?> />
							<label for="upld_txt"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_txt_no" name="upld_txt" value="no" <?= $noChecked; ?> />
							<label for="upld_txt_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_PDF')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%PDF%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%PDF%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_pdf" name="upld_pdf" value="yes" <?= $yesChecked; ?> />
							<label for="upld_pdf"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_pdf_no" name="upld_pdf" value="no" <?= $noChecked; ?> />
							<label for="upld_pdf_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_ODF')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%ODF%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%ODF%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_odf" name="upld_odf" value="yes" <?= $yesChecked; ?> />
							<label for="upld_odf"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_odf_no" name="upld_odf" value="no" <?= $noChecked; ?> />
							<label for="upld_odf_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_OOO')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%OOO%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%OOO%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_ooo" name="upld_ooo" value="yes" <?= $yesChecked; ?> />
							<label for="upld_ooo"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_ooo_no" name="upld_ooo" value="no" <?= $noChecked; ?> />
							<label for="upld_ooo_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_MSO')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%MSO%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%MSO%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_mso" name="upld_mso" value="yes" <?= $yesChecked; ?> />
							<label for="upld_mso"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_mso_no" name="upld_mso" value="no" <?= $noChecked; ?> />
							<label for="upld_mso_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_NMSO')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%NMSO%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%NMSO%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_nmso" name="upld_nmso" value="yes" <?= $yesChecked; ?> />
							<label for="upld_nmso"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_nmso_no" name="upld_nmso" value="no" <?= $noChecked; ?> />
							<label for="upld_nmso_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_RTF')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%RTF%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%RTF%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_rtf" name="upld_rtf" value="yes" <?= $yesChecked; ?> />
							<label for="upld_rtf"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_rtf_no" name="upld_rtf" value="no" <?= $noChecked; ?> />
							<label for="upld_rtf_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_HTML')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%HTML%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%HTML%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_html" name="upld_html" value="yes" <?= $yesChecked; ?> />
							<label for="upld_html"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_html_no" name="upld_html" value="no" <?= $noChecked; ?> />
							<label for="upld_html_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_UPLOAD_TYPE_WAV')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_upld_file_types'], '%WAV%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_upld_file_types'], '%WAV%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="upld_wav" name="upld_wav" value="yes" <?= $yesChecked; ?> />
							<label for="upld_wav"><?= TRANS('YES'); ?></label>
							<input type="radio" id="upld_wav_no" name="upld_wav" value="no" <?= $noChecked; ?> />
							<label for="upld_wav_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-image text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_UPLOAD_IMG')); ?></h6>
					
					<label for="img_max_width" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_MAXWIDTH')); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control" name="img_max_width" id="img_max_width" required value="<?= $config['conf_upld_width']; ?>" />
					</div>
					<label for="img_max_height" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_MAXHEIGHT')); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control" name="img_max_height" id="img_max_height" required value="<?= $config['conf_upld_height']; ?>" />
					</div>
					

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-align-right text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('FORMATTING_BAR')); ?></h6>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_FORMATBAR_MURAL'); ?>"><?= firstLetterUp(TRANS('OPT_MURAL')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_formatBar'], '%mural%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_formatBar'], '%mural%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="formatMural" name="formatMural" value="yes" <?= $yesChecked; ?> />
							<label for="formatMural"><?= TRANS('YES'); ?></label>
							<input type="radio" id="formatMural_no" name="formatMural" value="no" <?= $noChecked; ?> />
							<label for="formatMural_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELP_FORMATBAR_TICKETS'); ?>"><?= firstLetterUp(TRANS('OPT_OCORRENCIAS')); ?></label>
					<div class="form-group col-md-4 ">
						<?php
						$yesChecked = (strpos($config['conf_formatBar'], '%oco%')) ? " checked" : "";
						$noChecked = (!strpos($config['conf_formatBar'], '%oco%')) ? " checked" : "";
						?>
						<div class="switch-field">
							<input type="radio" id="formatOco" name="formatOco" value="yes" <?= $yesChecked; ?> />
							<label for="formatOco"><?= TRANS('YES'); ?></label>
							<input type="radio" id="formatOco_no" name="formatOco" value="no" <?= $noChecked; ?> />
							<label for="formatOco_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<!-- Configuração para posicionamento do campo de Descrição dos chamados -->
					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-ellipsis-h text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('MISCELLANEOUS')); ?></h6>
					<label for="description_position" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DESCRIPTION_FIELD_POSITION'); ?>"><?= firstLetterUp(TRANS('DESCRIPTION_FIELD_POSITION')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="description_position" required id="description_position">
							<?php
							foreach ($descriptionFieldPositions as $key => $value) {
							?>
								<option value="<?= $key; ?>" <?= ($key == $descriptionCurrentPos) ? 'selected' : ''; ?>><?= $value; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					


					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-bell text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('OPT_SEND_MAIL_WRTY')); ?></h6>
					<label for="days_before_expire" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_DAYS_BEFORE')); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control" name="days_before_expire" id="days_before_expire" required value="<?= $config['conf_days_bf']; ?>" />
					</div>
					<label for="area_to_alert" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= firstLetterUp(TRANS('OPT_SEL_AREA')); ?></label>
					<div class="form-group col-md-4 ">
						<select class="form-control bs-select" name="area_to_alert" required id="area_to_alert">
							<?php
							$areas = getAreas($conn, 0, 1, 1);
							foreach ($areas as $area) {
							?>
								<option value="<?= $area['sis_id']; ?>" <?= ($area['sis_id'] == $config['conf_wrty_area']) ? 'selected' : ''; ?>><?= $area['sistema']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-boxes text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ASSETS_BATCH_REGISTER')); ?></h6>
					<label for="max_amount_batch_assets_record" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MAX_AMOUNT_EACH_ASSET_BATCH'); ?>"><?= firstLetterUp(TRANS('MAX_AMOUNT_EACH_ASSET_BATCH')); ?></label>
					<div class="form-group col-md-10">
						<input type="number" class="form-control" name="max_amount_batch_assets_record" id="max_amount_batch_assets_record" min="1" required value="<?= getConfigValue($conn, 'MAX_AMOUNT_BATCH_ASSETS_RECORD') ?? 1; ?>" />
					</div>



					<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-truck-loading text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ASSETS_ALLOCATION_TO_USERS')); ?></h6>
					
					<label for="allow_assets_btw_clients" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_ALLOW_ALLOCATION_BTW_CLIENTS'); ?>"><?= firstLetterUp(TRANS('ALLOW_ALLOCATION_BTW_CLIENTS')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = (getConfigValue($conn, 'ALLOW_USER_GET_ASSETS_BTW_CLIENTS') ?? 0) == 1 ? " checked" : "" ;
							$noChecked = (getConfigValue($conn, 'ALLOW_USER_GET_ASSETS_BTW_CLIENTS') ?? 0) != 1 ? " checked" : "" ;
							?>
							<input type="radio" id="allow_assets_btw_clients" name="allow_assets_btw_clients" value="yes" <?= $yesChecked; ?> />
							<label for="allow_assets_btw_clients"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_assets_btw_clients_no" name="allow_assets_btw_clients" value="no" <?= $noChecked; ?> />
							<label for="allow_assets_btw_clients_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label for="allow_only_ops_assets_btw_clients" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_ALLOW_ALLOCATION_BTW_CLIENTS_ONLY_TO_OPS'); ?>"><?= firstLetterUp(TRANS('ALLOW_ALLOCATION_BTW_CLIENTS_ONLY_TO_OPS')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = (getConfigValue($conn, 'ALLOW_ONLY_OPS_GET_ASSETS_BTW_CLIENTS') ?? 0) == 1 ? " checked" : "" ;
							$noChecked = (getConfigValue($conn, 'ALLOW_ONLY_OPS_GET_ASSETS_BTW_CLIENTS') ?? 0) != 1 ? " checked" : "" ;
							?>
							<input type="radio" id="allow_only_ops_assets_btw_clients" name="allow_only_ops_assets_btw_clients" value="yes" <?= $yesChecked; ?> />
							<label for="allow_only_ops_assets_btw_clients"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allow_only_ops_assets_btw_clients_no" name="allow_only_ops_assets_btw_clients" value="no" <?= $noChecked; ?> />
							<label for="allow_only_ops_assets_btw_clients_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<label for="assets_auto_department" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DISABLED_USERS_ASSETS_DEPARTMENT'); ?>"><?= firstLetterUp(TRANS('DISABLED_USERS_ASSETS_DEPARTMENT')); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select" name="assets_auto_department" id="assets_auto_department">
							<option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
							$departments = getDepartments($conn, 1, null, null, null);
							$subtextKeys = ['nickname', 'unidade'];
							foreach ($departments as $department) {
								$departmentInfoArray = [];
								foreach ($subtextKeys as $key) {
									$departmentInfoArray[] = $department[$key];
								}
								$subtext = implode(" - ", array_filter($departmentInfoArray));
								?>
									<option data-subtext="<?= $subtext; ?>" value="<?= $department['loc_id']; ?>" 
									<?= ($department['loc_id'] == getConfigValue($conn, 'ASSETS_AUTO_DEPARTMENT')) ? ' selected' : ''; ?>
									><?= $department['local']; ?></option>
								<?php
							}
							?>
						</select>
					</div>


					<!-- ---------------------------------------- -->
					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="edit">
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>


				</div>
			</form>
		<?php
		}
		?>
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<!-- <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script> -->
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script src="../../includes/components/jquery/jquery.amsify.suggestags-master/js/jquery.amsify.suggestags.js"></script>

	<script type="text/javascript">
		$(function() {

			$(function() {
				$('[data-toggle="popover"]').popover({
					html:true
				})
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.bs-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				showSubtext: true,
				// actionsBox: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});

			if ($('#pre_filters').length > 0) {
				$('input[name="pre_filters"]').amsifySuggestags({
					type : 'bootstrap',
					defaultTagClass: 'badge bg-secondary text-white p-2 m-1',
					tagLimit: 6,
					printValues: false,
					showPlusAfter: 6,
					showAllSuggestions: true,
					keepLastOnHoverTag: false,
					
					suggestions: <?= $categories; ?>,
					whiteList: true
				});
			}

			deadlineReopenControl();
			$('[name="allow_reopen"]').on('change', function(){
				deadlineReopenControl();
			});

			ratingOptionsControl ();
			$('#time_to_close_after_done').on('change', function(){
				ratingOptionsControl ();
			});

			optionUserAssetsControl();
			$('[name="allow_assets_btw_clients"]').on('change', function(){
				optionUserAssetsControl();
			});

			// ticketClosingOptionsControl();
			// $('[name="closing_mode_requester"]').on('change', function(){
			// 	ticketClosingOptionsControl();
			// });

			$('#idSubmit').on('click', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$("#idSubmit").prop("disabled", true);
				enableRatingOptions();
				$.ajax({
					url: './config_geral_process.php',
					method: 'POST',
					data: $('#form').serialize(),
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');

						$('.bs-select').selectpicker('setStyle', 'is-invalid', 'remove');
						$('.bs-select').selectpicker('refresh');

						if (response.field_id != "") {

							if ($('#' + response.field_id).hasClass('bs-select')) {
								$('#' + response.field_id).selectpicker('setStyle', 'is-invalid');
								$('#' + response.field_id).focus().selectpicker('refresh');
							} else {
								$('#' + response.field_id).focus().addClass('is-invalid');
							}

						}
						$("#idSubmit").prop("disabled", false);
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#idSubmit").prop("disabled", false);
						var url = '<?= $_SERVER['PHP_SELF'] ?>';
						$(location).prop('href', url);
						return false;
					}
				});
				return false;
			});


			$('.bt-edit').on("click", function() {
				$('#idLoad').css('display', 'block');
				var url = '<?= $_SERVER['PHP_SELF'] ?>?action=edit';
				$(location).prop('href', url);
			});

			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});


		function deadlineReopenControl () {

			if ($('#allow_reopen').is(':checked')) {
				$('#reopen_deadline').prop('disabled', false);
			} else {
				$('#reopen_deadline').prop('disabled', true);
			}
		}


		function enableRatingOptions() {
			$('#status_done').prop('disabled', false).selectpicker('refresh');
			$('#status_done_rejected').prop('disabled', false).selectpicker('refresh');
			$('#only_weekdays_to_count').prop('disabled', false).selectpicker('refresh');
			$('#default_automatic_rate').prop('disabled', false).selectpicker('refresh');
		}

		function disableRatingOptions() {
			$('#status_done').prop('disabled', true).selectpicker('refresh');
			$('#status_done_rejected').prop('disabled', true).selectpicker('refresh');
			$('#only_weekdays_to_count').prop('disabled', true).selectpicker('refresh');
			$('#default_automatic_rate').prop('disabled', true).selectpicker('refresh');
		}

		function ratingOptionsControl () {
			if ($('#time_to_close_after_done').val() > 0) {
				enableRatingOptions();
			} else {
				disableRatingOptions();
			}
		}

		function optionUserAssetsControl () {
			if (!$('#allow_assets_btw_clients').is(':checked')) {
				$('#allow_only_ops_assets_btw_clients').prop('disabled', true).prop('checked', false);
				$('#allow_only_ops_assets_btw_clients_no').prop('disabled', true).prop('checked', true);
			} else if (!$('#allow_assets_btw_clients').prop('disabled')) {
				$('#allow_only_ops_assets_btw_clients').prop('disabled', false);
				$('#allow_only_ops_assets_btw_clients_no').prop('disabled', false);
			}
		}
	</script>
</body>

</html>