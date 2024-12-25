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

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 2);
$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];

$exception = "";
$imgsPath = "../../includes/imgs/";

//Todas as áreas que o usuário percente
$uareas = $_SESSION['s_uareas'];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/util.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos_custom.css" />


    <title><?= APP_NAME; ?>&nbsp;<?= VERSAO; ?></title>

    <style>

        .line {
            line-height: 1.4em;
        }
        .icon-expand:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f0fe"; */
            content: "\f105";
            font-weight: 900;
            font-size: 16px;
        }

        .icon-collapse:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f146"; */
            content: "\f107";
            font-weight: 900;
            font-size: 16px;
        }


        .just-padding {
            padding: 5px;
        }

        .list-group.list-group-root {
            padding: 0;
            overflow: hidden;
        }

        .list-group>a {
            color: #111111 !important;
        }

        .list-group>a:hover {
            text-decoration: none !important;
            color: #111111 !important;
        }

        .list-group.list-group-root .list-group {
            margin-bottom: 0;
        }

        .list-group.list-group-root .list-group-item {
            border-radius: 0;
            border-width: 0 0 0 0;
        }

        .list-group.list-group-root>.list-group-item:first-child {
            border-top-width: 0;
        }

        .list-group.list-group-root>.list-group>.list-group-item {
            /* padding-left: 35px; */
            padding-left: 35px;
        }

        .list-group.list-group-root>.list-group>.list-group>.list-group-item {
            padding-left: 55px;
        }

        .list-group.list-group-root>.list-group>.list-group>.list-group>.list-group-item {
            padding-left: 75px;
        }

        .list-group.list-group-root>.list-group>.list-group>.list-group>.list-group>.list-group-item {
            padding-left: 95px;
        }

        .list-group-item .glyphicon,
        .list-group-item .icon-expand {
            margin-right: 5px;
        }

        .list-attributes {
            line-height: 1.5em;
        }



    </style>
</head>

<body>
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <?php
    if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
        echo $_SESSION['flash'];
        $_SESSION['flash'] = '';
    }


    /** Opções disponíveis para agrupamentos */
    $options = [
        TRANS('CLIENT'),
        TRANS('ASSET_CATEGORY'),
        TRANS('ASSET_TYPE'),
        TRANS('COL_UNIT'),
        TRANS('DEPARTMENT'),
        TRANS('COL_MODEL'),
        TRANS('STATE'),
        TRANS('COL_MANUFACTURER')
    ];
    sort($options, SORT_LOCALE_STRING);

    /** Chaves em separado pois no primeiro array seriam perdidas após a ordenação */
    $optionsKeys = [
        TRANS('CLIENT') => 'client',
        TRANS('ASSET_CATEGORY') => 'category',
        TRANS('ASSET_TYPE') => 'asset_type',
        TRANS('COL_UNIT') => 'unit',
        TRANS('DEPARTMENT') => 'department',
        TRANS('COL_MODEL') => 'model',
        TRANS('STATE') => 'state',
        TRANS('COL_MANUFACTURER') => 'manufacturer'
    ];

    $optionsForAvailability = [
        1 => TRANS('ANY_AVAILABILITY'),
        2 => TRANS('AVAILABLES'),
        3 => TRANS('IN_USE')
    ];
    
    $optionsForTypes = [
        1 => TRANS('ALL_TYPES'),
        2 => TRANS('ONLY_ASSETS'),
        3 => TRANS('ONLY_RESOURCES')
    ];

    ?>

    <div class="container-fluid">
        <h5 class="my-4"><i class="fas fa-stream text-secondary"></i>&nbsp;<?= TRANS('ASSETS_TREE'); ?></h5>
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails" style="position:relative">
                        <iframe id="assetInfo"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
            

            <div class="form-group row mb-0"> <!-- Abertura row nova -->

            <div class="form-group col-md-4">
                    <select class="form-control form-control-sm bs-select " id="group_00" name="group_00">
                        <?php
                            foreach ($optionsForAvailability as $key => $type) {
                                ?>
                                    <option value="<?= $key; ?>"
                                    <?= ($key == 1 ? ' selected' : ''); ?>
                                    ><?= $type; ?></option>
                                <?php
                            }
                        ?>
                    </select>
                    <small class="form-text text-muted"><?= TRANS('AVAILABILITY'); ?></small>
                </div>

                <div class="form-group col-md-4">
                    <select class="form-control form-control-sm bs-select " id="group_0" name="group_0">
                        <?php
                            foreach ($optionsForTypes as $key => $type) {
                                ?>
                                    <option value="<?= $key; ?>"
                                    <?= ($key == 2 ? ' selected' : ''); ?>
                                    ><?= $type; ?></option>
                                <?php
                            }
                        ?>
                    </select>
                    <small class="form-text text-muted"><?= TRANS('ALL_TYPES'); ?></small>
                </div>
                
            </div> <!-- fechamento row nova -->


            <div class="row mb-0">
                <div class="col-md-12 mb-0">
                    <?= TRANS('LEVELS_TO_AGROUP'); ?>
                </div>
            </div>
            
            <div class="row mt-0">
                <!-- <div class="col-md-12 mt-0"> -->
                <div class="col-md-8 mt-0">

                    <div class="form-group row my-4">
                        <div class="form-group col-md-3">
                            <select class="form-control form-control-sm bs-select sel-control" id="group_1" name="group_1">
                                <option value=""><?= TRANS('BT_CLEAR'); ?></option>
                                <?php
                                    foreach ($options as $value) {
                                        ?>
                                            <option value="<?= $optionsKeys[$value]; ?>"
                                            <?= ($optionsKeys[$value] == 'client' ? ' selected' : ''); ?>
                                            ><?= $value; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <select class="form-control form-control-sm bs-select sel-control" id="group_2" name="group_2" >
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($options as $value) {
                                        ?>
                                            <option value="<?= $optionsKeys[$value]; ?>"
                                            <?= ($optionsKeys[$value] == 'category' ? ' selected' : ''); ?>
                                            ><?= $value; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select class="form-control form-control-sm bs-select sel-control" id="group_3" name="group_3" >
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($options as $value) {
                                        ?>
                                            <option value="<?= $optionsKeys[$value]; ?>"><?= $value; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select class="form-control form-control-sm bs-select sel-control" id="group_4" name="group_4" >
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($options as $value) {
                                        ?>
                                            <option value="<?= $optionsKeys[$value]; ?>"><?= $value; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select class="form-control form-control-sm bs-select sel-control" id="group_5" name="group_5" >
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($options as $value) {
                                        ?>
                                            <option value="<?= $optionsKeys[$value]; ?>"><?= $value; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group row my-4">
                        <div class="form-group col-md-4 align-baseline">
                            <button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-sync-alt text-white"></i>&nbsp;<?= TRANS('BT_AGROUP'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
        
        
        <!-- Aqui serão carregadas as listagens agrupadas -->
        <div id="divResult" class="just-padding"></div>


        <script src="../../includes/components/jquery/jquery.js"></script>
        <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
        <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
        <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
        <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
        <script src="../../includes/javascript/funcoes-3.0.js"></script>
        <SCRIPT LANGUAGE="javaScript">
            $(function() {


                $('#idLoad').css('display', 'block');
                $( document ).ready(function() {
                    $('#idLoad').css('display', 'none');
                });

                var tableObj = {};
                /* Agrupamento padrão - carregado na inicialização do script */
                agroup();

                $('#group_0, #group_00').on('change', function() {
                    agroup();
                });

                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var obs = $.initialize("#assets_group", function() {
                    
                    $('.list-group-item').on('click', function() {
                        $('.glyphicon', this)
                            .toggleClass('icon-expand')
                            .toggleClass('icon-collapse');
                    });

                    $('a').on('click', function() {
                        loadTable($(this).attr('href'), $(this).attr('aria-expanded') ?? false);
                    });

                    $(function() {
                        $('[data-toggle="popover"]').popover({
                            html: true
                        })
                    });

                    $('.popover-dismiss').popover({
                        trigger: 'focus'
                    });

                }, {
                    target: document.getElementById('divResult')
                }); /* o target limita o scopo do mutate observer */



                var obs2 = $.initialize("#tables", function() {

                    $('table').each(function(){


                        var id = ($(this).attr('id'));
                        
                        /* Checando se o datatable já foi inicializado para esse objeto */
                        if ( !$.fn.DataTable.isDataTable($(this)) ) {
                        
                            tableObj[id] = $(this).DataTable({

                                paging: true,
                                deferRender: true,
                                // retrieve: true,
                                columnDefs: [{
                                    targets: ["direct-attributes", "aggregated-attributes"],
                                    // searchable: false,
                                    orderable: false
                                }],

                                "language": {
                                    "url": "../../includes/components/datatables/datatables.pt-br.json"
                                }
                            });
                        }
                    })


                    $(function() {
                        $('[data-toggle="popover"]').popover({
                            html: true
                        })
                    });

                    $('.popover-dismiss').popover({
                        trigger: 'focus'
                    });

                    /* Remoção dos popovers */
                    $('table').on('mouseout', 'td', function() {
                        $(this).popover('dispose');
                        $('.popover').remove();
                    });
                    /* Popovers para os indicadores de interação com o chamado (primeira coluna) */
                    $('table').on('mouseover', '.ticket-interaction', function() {

                        let content = $(this).attr('data-content');
                        
                        $(this).attr('data-content', content);
                        $(this).popover({
                            html:true
                        });
                        $(this).popover('update');
                        $(this).popover('show');
                    });

                }, {
                    target: document.getElementById('divResult')
                }); /* o target limita o scopo do mutate observer */


                // $('#modal').on('hidden.bs.modal', function (e) {
                //     // agroup();
                //     updateTables(tableObj);
                // })




                $('.bs-select').selectpicker({
                    /* placeholder */
                    title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
                    liveSearch: true,
                    liveSearchNormalize: true,
                    liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                    noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                    style: "",
                    styleBase: "form-control ",
                });


                $(function() {
                    $('[data-toggle="popover"]').popover({
                        html: true
                    })
                });

                $('.popover-dismiss').popover({
                    trigger: 'focus'
                });

                availablesGroupOptionsControl();

                $('#group_1, #group_2, #group_3, #group_4, #group_5').on('change', function(){
                    availablesGroupOptionsControl();
                });


                $('#idSubmit').on('click', function(e) {
                    e.preventDefault();
                    agroup();
			    });



            });


            function agroup() {
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $("#idSubmit").prop("disabled", true);
                
                $.ajax({
                    url: './assets_group.php',
                    method: 'POST',
                    data: $('#form').serialize(),
                }).done(function(response) {
                    $('#divResult').html('');
                    $('#divResult').html(response);

                    $("#idSubmit").prop("disabled", false);
                });
                return false;
            }


            function loadTable (params, expanded) {
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                // $('table').each(function() {
                //     $(this).DataTable().destroy();
                // });

                let href = 'params=' + params;
                href = href.replace('#', '');

                /* Não precisa rodar nenhuma checagem se a ação for de collapse (fechar) */
                if (!expanded) {
                    $.ajax({
                        url: './get_assets_tree_table.php',
                        method: 'POST',
                        data: $('#form').serialize()+"&"+href
                    }).done(function(response) {

                        if (response.length > 0) {
                            $(params).html(response);
                        }

                    });
                }
                
                return false;
            }




            function disableField(fieldID) {
                if ($('#'+fieldID).length > 0) {
                    $('#'+fieldID).prop('disabled', true);
                    $('#'+fieldID).selectpicker('refresh').selectpicker('val', '');
                }
            }

            function enableField(fieldID) {
                if ($('#'+fieldID).length > 0) {
                    $('#'+fieldID).prop('disabled', false);
                    $('#'+fieldID).selectpicker('refresh');
                }
            }

            function selectsControl() {
                let group_1 = $('#group_1');
                let group_2 = $('#group_2');
                let group_3 = $('#group_3');
                let group_4 = $('#group_4');
                let group_5 = $('#group_5');

                if (group_1.val() == "") {
                    disableField('group_2');
                    disableField('group_3');
                    disableField('group_4');
                    disableField('group_5');
                } else {
                    enableField('group_2');
                }

                if (group_2.val() == "") {
                    disableField('group_3');
                    disableField('group_4');
                    disableField('group_5');
                } else {
                    enableField('group_3');
                }

                if (group_3.val() == "") {
                    disableField('group_4');
                    disableField('group_5');
                } else {
                    enableField('group_4');
                }

                if (group_4.val() == "") {
                    disableField('group_5');
                } else {
                    enableField('group_5');
                }
            }


            /* Faz o controle das opções de tipos de características disponíveis para seleção */
            function availablesGroupOptionsControl() {
                let keys = [];
                let values = [];

                selectsControl();

                /* Primeiro habilito todos os options */
                $('.sel-control').each(function(){
                    $(this).find('option').each(function(){
                        $(this).prop('disabled', false);
                        
                        if ($(this).hasClass('bs-select')) {
                            $(this).selectpicker('refresh');
                        }
                    });
                });

                /* Pegando todos os IDs dos Selects e seus respectivos valores */
                $('select[name^="group_"]').each(function() {
                    
                    let id = $(this).attr('id');
                    let value = $(this).val();
                    
                    keys.push(id);
                    values.push(value);
                });

                for (var i = 0; i < keys.length; i++) {
                    /* Para cada option confiro em todos os Selects */
                    $('.sel-control').each(function(){

                        /* Controle de seleção - Desabilita todos os options que tiverem o valor já selecionado para o ID checado*/
                        if ($(this).attr('id') != keys[i]) {
                            
                            if (values[i] != '') {
                                $(this).find('[value="'+values[i]+'"]').prop('disabled', true);
                                if ($(this).hasClass('bs-select')) {
                                    $(this).selectpicker('refresh');
                                }
                            }
                            
                        } else {
                            $(this).find('[value="'+values[i]+'"]').prop('disabled', false);
                            if ($(this).hasClass('bs-select')) {
                                $(this).selectpicker('refresh');
                            }
                        }
                    });
                } 
            }



            // function updateTables(tableObj){
                
            //     const keys = Object.keys(tableObj);
                
            //     keys.forEach(key => {
            //         console.log (`${key}`)
            //         console.log(`${key} -> ${tableObj[key]}`)

            //         $('#'+`${key}`).ajax.reload(null, false);
            //     })
            // }

            function openAssetInfo(assetId) {
                let location = 'asset_show.php?asset_id=' + assetId;
                $("#assetInfo").attr('src',location)
                $('#modal').modal();
            }

            function closeIframeModal() {
                $('#modal').modal('hide');
            }
            
        </script>
    </div>
</body>

</html>