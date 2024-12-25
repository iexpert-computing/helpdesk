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

$clients = getClients($conn);


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
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos_custom.css" />

	<title><?= APP_NAME; ?>&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>

	<input type="hidden" name="label_close" id="label_close" value="<?= TRANS('BT_CLOSE'); ?>">
	<input type="hidden" name="label_return" id="label_return" value="<?= TRANS('TXT_RETURN'); ?>">


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-city text-secondary"></i>&nbsp;<?= TRANS('UNITS'); ?></h4>
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
		
		
		$units = (isset($_GET['cod']) ? getUnits($conn, null, (int)$_GET['cod']) : getUnits($conn));
		$registros = count($units);

		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

		?>
			<!-- Modal -->
			<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-light">
							<h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<?= TRANS('CONFIRM_REMOVE'); ?> <span class="j_param_id"></span>?
						</div>
						<div class="modal-footer bg-light">
							<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
							<button type="button" id="deleteButton" class="btn"><?= TRANS('BT_OK'); ?></button>
						</div>
					</div>
				</div>
			</div>

			<button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br />
			
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
                            <td class="line client"><?= TRANS('CLIENT'); ?></td>
                            <td class="line issue_type"><?= TRANS('COL_UNIT'); ?></td>
                            <td class="line issue_type"><?= TRANS('ADDRESS'); ?></td>
                            <td class="line issue_type"><?= TRANS('CITY'); ?></td>
                            <td class="line issue_type"><?= TRANS('ADDRESS_UF'); ?></td>
                            <td class="line status"><?= TRANS('ACTIVE_O'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php
						$addressKeys = ['addr_street', 'addr_number', 'addr_complement', 'addr_neighborhood'];
						foreach ($units as $row) {
							$location = "";
							$locationArray = [];
							foreach ($addressKeys as $key) {
								$locationArray[] = $row[$key];
							}
							
							$location = implode(" - ", array_filter($locationArray));

							$lstatus = ($row['inst_status'] == 1 ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

							?>
							<tr>
                                <td class="line"><?= $row['nickname']; ?></td>
                                <td class="line"><?= $row['inst_nome']; ?></td>
                                <td class="line"><?= $location; ?></td>
                                <td class="line"><?= $row['addr_city']; ?></td>
                                <td class="line"><?= $row['addr_uf']; ?></td>
                                <td class="line"><?= $lstatus; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['inst_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['inst_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
							</tr>
						<?php
						}
						?>
					</tbody>
				</table>
			<?php
			}
		} else
		if ((isset($_GET['action'])  && ($_GET['action'] == "new")) && !isset($_POST['submit'])) {

			?>
			<h6><?= TRANS('NEW_RECORD'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input('units'); ?>
				<div class="form-group row my-4">
					
					<label for="unit_client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select" id="unit_client" name="unit_client">
							<option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								foreach ($clients as $client) {
									?>
										<option value="<?= $client['id']; ?>"><?= $client['nickname']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>
				
					<label for="unit_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="unit_name" name="unit_name" required />
                    </div>
					

					<div class="form-group col-md-2">
					</div>
					<div class="form-group col-md-10">
						<hr />
                    </div>
								

					<label for="unit_cep" class="col-md-2 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('CEP'); ?></label>
					<div class="form-group col-md-3">
						<div class="input-group">
							<input type="text" class="form-control " id="unit_cep" name="unit_cep"  />
							<div class="input-group-append">
								<div class="input-group-text load-address" title="<?= TRANS('GET_ADDRESS_INFO_BY_CEP'); ?>" data-placeholder="<?= TRANS('GET_ADDRESS_INFO_BY_CEP'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-search-location"></i>&nbsp;
								</div>
                        	</div>
						</div>
                    </div>
					<label for="unit_street" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('STREET'); ?></label>
					<div class="form-group col-md-6">
						<input type="text" class="form-control " id="unit_street" name="unit_street"  />
                    </div>
					<label for="unit_neighborhood" class="col-md-2 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('NEIGHBORHOOD'); ?></label>
					<div class="form-group col-md-3">
						<input type="text" class="form-control " id="unit_neighborhood" name="unit_neighborhood"  />
                    </div>
					<label for="unit_city" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('CITY'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="unit_city" name="unit_city"  />
                    </div>
					<label for="unit_state" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('ADDRESS_STATE'); ?></label>
					<div class="form-group col-md-1">
						<input type="text" class="form-control " id="unit_state" name="unit_state"  />
                    </div>

					<label for="unit_address_number" class="col-md-2 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('ADDRESS_NUMBER'); ?></label>
					<div class="form-group col-md-3">
						<input type="text" class="form-control " id="unit_address_number" name="unit_address_number"  />
                    </div>
					<label for="unit_address_complement" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('ADDRESS_COMPLEMENT'); ?></label>
					<div class="form-group col-md-6">
						<input type="text" class="form-control " id="unit_address_complement" name="unit_address_complement"  />
                    </div>

					<div class="form-group col-md-2">
					</div>
					<div class="form-group col-md-10">
						<hr />
                    </div>

					<label for="unit_obs" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('TXT_INFO_COMPLEM'); ?>"><?= firstLetterUp(TRANS('TXT_INFO_COMPLEM')); ?></label>
					<div class="form-group col-md-10 ">
						<textarea class="form-control" name="unit_obs" id="unit_obs"></textarea>
					</div>
                    
					

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" id="close_details" class="btn btn-secondary btn-block"><?= TRANS('BT_CANCEL'); ?></button>
					</div>


				</div>
			</form>
		<?php
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

			$row = $units;
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input('units'); ?>
				<div class="form-group row my-4">


					<label for="unit_client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select" id="unit_client" name="unit_client">
							<option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								foreach ($clients as $client) {
									?>
										<option value="<?= $client['id']; ?>"
										<?= ($client['id'] == $row['inst_client'] ? " selected" : ""); ?>
										><?= $client['nickname']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>

					<label for="unit_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="unit_name" name="unit_name" value="<?= $row['inst_nome']; ?>" required />
                    </div>


                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ACTIVE_O'); ?>"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['inst_status'] == 1 ? "checked" : "");
							$noChecked = (!($row['inst_status'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="unit_status" name="unit_status" value="yes" <?= $yesChecked; ?> />
							<label for="unit_status"><?= TRANS('YES'); ?></label>
							<input type="radio" id="unit_status_no" name="unit_status" value="no" <?= $noChecked; ?> />
							<label for="unit_status_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label for="unit_cep" class="col-md-2 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('CEP'); ?></label>
					<div class="form-group col-md-3">
						<div class="input-group">
							<input type="text" class="form-control " id="unit_cep" name="unit_cep" value="<?= $row['addr_cep']; ?>" />
							<div class="input-group-append">
								<div class="input-group-text load-address" title="<?= TRANS('GET_ADDRESS_INFO_BY_CEP'); ?>" data-placeholder="<?= TRANS('GET_ADDRESS_INFO_BY_CEP'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-search-location"></i>&nbsp;
								</div>
                        	</div>
						</div>
                    </div>
					<label for="unit_street" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('STREET'); ?></label>
					<div class="form-group col-md-6">
						<input type="text" class="form-control " id="unit_street" name="unit_street"  value = "<?= $row['addr_street']; ?>"/>
                    </div>
					<label for="unit_neighborhood" class="col-md-2 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('NEIGHBORHOOD'); ?></label>
					<div class="form-group col-md-3">
						<input type="text" class="form-control " id="unit_neighborhood" name="unit_neighborhood"    value = "<?= $row['addr_neighborhood']; ?>"/>
                    </div>
					<label for="unit_city" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('CITY'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="unit_city" name="unit_city" value = "<?= $row['addr_city']; ?>" />
                    </div>
					<label for="unit_state" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('ADDRESS_STATE'); ?></label>
					<div class="form-group col-md-1">
						<input type="text" class="form-control " id="unit_state" name="unit_state"  value = "<?= $row['addr_uf']; ?>" />
                    </div>

					<label for="unit_address_number" class="col-md-2 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('ADDRESS_NUMBER'); ?></label>
					<div class="form-group col-md-3">
						<input type="text" class="form-control " id="unit_address_number" name="unit_address_number"  value = "<?= $row['addr_number']; ?>"  />
                    </div>
					<label for="unit_address_complement" class="col-md-1 col-form-label col-form-label-sm text-md-right address-info"><?= TRANS('ADDRESS_COMPLEMENT'); ?></label>
					<div class="form-group col-md-6">
						<input type="text" class="form-control " id="unit_address_complement" name="unit_address_complement"  value = "<?= $row['addr_complement']; ?>"  />
                    </div>

					<div class="form-group col-md-2">
					</div>
					<div class="form-group col-md-10">
						<hr />
                    </div>

					<label for="unit_obs" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('TXT_INFO_COMPLEM'); ?>"><?= firstLetterUp(TRANS('TXT_INFO_COMPLEM')); ?></label>
					<div class="form-group col-md-10 ">
						<textarea class="form-control" name="unit_obs" id="unit_obs"><?= $row['observation']; ?></textarea>
					</div>
                    

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
                        <input type="hidden" name="cod" value="<?= (int)$_GET['cod']; ?>">
                        <input type="hidden" name="action" id="action" value="edit">
						<button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" id="close_details" class="btn btn-secondary btn-block"><?= TRANS('BT_CANCEL'); ?></button>
					</div>

				</div>
			</form>
		<?php
		}
		?>
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>

	<script type="text/javascript">
		$(function() {

			$('#table_lists').DataTable({
				paging: true,
				deferRender: true,
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
			});

			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.bs-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				actionsBox: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

			$('#idBtReturn').on('click', function() {
				let url = '<?= $_SERVER['PHP_SELF']; ?>';
				$(location).prop('href', url);
			});


			/* Identificar se a janela está sendo carregada em uma popup (iframe dentro de uma modal) */
			if (isInIframe()) {
				if (!isMainIframe()) {
					$('#close_details').text($('#label_close').val()).on("click", function() {
						window.parent.closeIframe();
					});
				} else {
					$('#close_details').text($('#label_return').val()).on("click", function() {
						window.history.back();
					});
				}
			} else {
				$('#close_details').text($('#label_return').val()).on("click", function() {
					window.history.back();
				});
			}



			$('#unit_cep').inputmask({ mask: '99999-999' }).on('change', function(e){
				e.preventDefault();
				loadAddressData();
			});

			$('.load-address').css('cursor', 'pointer').on('click', function(e) {
				e.preventDefault();
				loadAddressData();
			});


            $('input, select, textarea').on('change', function() {
				$(this).removeClass('is-invalid');
			});
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
				$.ajax({
					url: './units_process.php',
					method: 'POST',
					data: $('#form').serialize(),
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#idSubmit").prop("disabled", false);
					} else {

						if (isInIframe() && !isMainIframe()) {
							window.parent.loadPossibleUnits();
							window.parent.closeIframe();
						} else {
							$('#divResult').html('');
							$('input, select, textarea').removeClass('is-invalid');
							$("#idSubmit").prop("disabled", false);
							var url = '<?= $_SERVER['PHP_SELF'] ?>';
							$(location).prop('href', url);
						}
						return false;
					}
				});
				return false;
			});

			$('#idBtIncluir').on("click", function() {
				$('#idLoad').css('display', 'block');
				var url = '<?= $_SERVER['PHP_SELF'] ?>?action=new';
				$(location).prop('href', url);
			});

			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});





		function loadAddressData() {
			// e.preventDefault();
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$(".address-info").each(function() {
				$(this).prop("disabled", true);
			});
			$.ajax({
				url: './get_address_by_cep.php',
				method: 'POST',
				data: {
					cep: $('#unit_cep').val(),
				},
				dataType: 'json',
			}).done(function(response) {

				if (!response.success) {
					$('#divResult').html(response.message);
					$('input, select, textarea').removeClass('is-invalid');
					if (response.field_id != "") {
						$('#' + response.field_id).focus().addClass('is-invalid');
					}
					$(".address-info").each(function() {
						$(this).prop("disabled", false);
					});
				} else {

					$('#divResult').html('');
					$(".address-info").each(function() {
						$(this).prop("disabled", false);
					});
					$('input, select, textarea').removeClass('is-invalid');
					
					$('#unit_street').val(response[0].street);
					$('#unit_neighborhood').val(response[0].neighborhood);
					$('#unit_city').val(response[0].city);
					$('#unit_state').val(response[0].state);

					return false;
				}
			});
			return false;
		};









		function confirmDeleteModal(id) {
			$('#deleteModal').modal();
			$('#deleteButton').html('<a class="btn btn-danger" onclick="deleteData(' + id + ')"><?= TRANS('REMOVE'); ?></a>');
		}

		function deleteData(id) {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './units_process.php',
				method: 'POST',
				data: {
					cod: id,
					action: 'delete'
				},
				dataType: 'json',
			}).done(function(response) {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
				return false;
			});
			return false;
			// $('#deleteModal').modal('hide'); // now close modal
		}

		function isInIframe() {
			return (window.location !== window.parent.location) ? true : false;
		}

		function isMainIframe() {
			var iframeParent = window.parent.document.getElementById('iframeMain');
			return (iframeParent) ? true : false;
		}
	</script>
</body>

</html>