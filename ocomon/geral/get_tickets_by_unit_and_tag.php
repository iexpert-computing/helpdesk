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

$imgsPath = "../../includes/imgs/";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

$actionUrl = "../../ocomon/geral/get_full_tickets_table.php";

/* Campos customizados */
$custom_fields_full = getCustomFields($conn, null, 'ocorrencias');
$custom_fields_classes = [];
foreach ($custom_fields_full as $cfield) {
    $custom_fields_classes[] = $cfield['field_name'];
}
$custom_fields_classes_text = implode(",", $custom_fields_classes);


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos_custom.css" />

    <title><?= APP_NAME; ?>&nbsp;<?= VERSAO; ?></title>

</head>

<body>
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div class="container-fluid">

        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>


        <?php

            $tag = ((isset($_GET['tag'])) ? nohtml($_GET['tag']) : "");
            $unit = ((isset($_GET['unit'])) ? nohtml($_GET['unit']) : "");

            if (empty($tag) || empty($unit)) {
                echo message ('warning', 'Ooops!', TRANS('FILL_UNIT_TAG'), '', '', 1);
                return;
            }

            if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
                echo $_SESSION['flash'];
                $_SESSION['flash'] = '';
            }
        ?>
    </div>


    <div id="print-info" class="d-none">&nbsp;</div>
    <input type="hidden" name="custom_fields_classes_text" id="custom_fields_classes_text" value="<?= $custom_fields_classes_text; ?>">



    <div class="container-fluid">
        <div id="divTicketsList">
        </div>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <!-- <script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script> -->
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./js/smart_search_columns.js"></script>

    <script>
        $(function() {

            let hiddenColunsCookie = getCookie('oc_cf_hidden_columns');
            let hiddenColunsCookieArray = hiddenColunsCookie.split(',');
            let customFieldsClassesText = $('#custom_fields_classes_text').val();
            let customFieldsClassesArray = customFieldsClassesText.split(',');

            var allColumns = reportAllColumns
            .concat(customFieldsClassesArray);
            
            var defaultHiddenColumns = hiddenColunsCookieArray

            if (defaultHiddenColumns == null || defaultHiddenColumns.length == 0 || defaultHiddenColumns == '') {
                defaultHiddenColumns = reportDefaultHiddenColumns.concat(customFieldsClassesArray);
            }

            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            $.ajax({
                // url: 'get_full_tickets_table.php',
                url: '<?= $actionUrl;?>',
                method: 'POST',
                data: {unidade: '<?= $unit;?>', etiqueta: '<?= $tag;?>'},
            }).done(function(response) {
                $('#divTicketsList').html(response);
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
                target: document.getElementById('divTicketsList')
            }); /* o target limita o scopo do mutate observer */



            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs = $.initialize("#table_tickets_queue", function() {
                
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

                    searching: false,
                    info: false,
                    paging: true,
                    // pageLength: 10,
                    deferRender: true,
                    // fixedHeader: true,
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
                        iFixedColumns: 1
                    },

                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    },

                });

                // new $.fn.dataTable.ColReorder(table);

                new $.fn.dataTable.Buttons(table,{
                    
                    buttons: [{
                            extend: 'print',
                            text: '<?= TRANS('SMART_BUTTON_PRINT', '', 1)?>',
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1)?>',
                            // message: 'Relatório de Ocorrências',
                            message: $('#print-info').html(),
                            autoPrint: true,

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
                            text: '<?= TRANS('SMART_BUTTON_COPY', '', 1)?>',
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
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1);?>-<?= date('d-m-Y-H:i:s');?>',
                        },
                        {
                            extend: 'csvHtml5',
                            text: "CVS",
                            exportOptions: {
                                columns: ':visible'
                            },

                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1);?>-<?= date('d-m-Y-H:i:s');?>',
                        },
                        {
                            extend: 'pdfHtml5',
                            text: "PDF",

                            exportOptions: {
                                columns: ':visible',
                            },
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1);?>',
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1);?>-<?= date('d-m-Y-H:i:s');?>',
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
                                        table: {
                                            widths: ['100%'],
                                            headerRows: 0,
                                            body: [
                                                [{
                                                    text: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1);?>',
                                                    alignment: 'center',
                                                    fontSize: 14,
                                                    bold: true,
                                                    margin: [0, 10, 0, 0]
                                                }],
                                            ]
                                        },
                                        layout: 'noBorders',
                                        margin: 10
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
                            text: '<?= TRANS('SMART_BUTTON_MANAGE_COLLUMNS', '', 1)?>',
                            // className: 'btn btn-primary',
                            // columns: ':gt(0)'
                            collectionLayout: 'dropdown four-column',
                        },
                    ]
                });

                table.buttons().container()
                    .appendTo($('.display-buttons:eq(0)', table.table().container()));

                table.on( 'buttons-action', function ( e, buttonApi, dataTable, node, config ) {
                    setTitles();
                });


            }, {
                target: document.getElementById('divTicketsList')
            }); /* o target limita o scopo do mutate observer */

        });


        function openTicketInfo(ticket) {

            let location = 'ticket_show.php?numero=' + ticket;
            $("#divDetails").load(location);
            $('#modal').modal();

        }
    </script>
</body>

</html>
