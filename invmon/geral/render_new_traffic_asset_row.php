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

$html = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";

$data['random'] = (isset($post['random']) ? noHtml($post['random']) : "");

$afterDomClass = "after-dom-ready";
$randomClass = $data['random'];

?>
<div class="form-group col-md-2 <?= $randomClass; ?>">
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">
                <a href="javascript:void(0);" class="remove_button_assets" data-random="<?= $randomClass; ?>" title="<?= TRANS('REMOVE_SPEC'); ?>"><i class="fa fa-minus"></i></a>
            </div>
        </div>
        <input type="text" name="assetTag[]" id="<?= $randomClass; ?>" class="form-control <?= $afterDomClass; ?> asset_tag_class" placeholder="<?= TRANS('ASSET_TAG'); ?>"/>
    </div>
    <small class="form-text text-muted"><?= TRANS('ASSET_TAG'); ?></small>
</div>

<div class="form-group col-md-4 <?= $randomClass; ?>">
    <select class="form-control asset_unit_class bs-select" name="asset_unit[]" id="<?= $randomClass .'_asset_unit'; ?>" data-base-random="<?= $randomClass;?>">
        <option value=""><?= TRANS('SEL_SELECT'); ?></option>
    </select>
    <small class="form-text text-muted"><?= TRANS('COL_UNIT'); ?></small>
</div>

<div class="form-group col-md-2 <?= $randomClass; ?>">
    <input type="text" name="asset_department[]" id="<?= $randomClass . '_asset_department'; ?>" class="form-control <?= $afterDomClass; ?>" disabled/>
    <small class="form-text text-muted"><?= TRANS('DEPARTMENT'); ?></small>
</div>


<div class="form-group col-md-4 <?= $randomClass; ?>">
    <input type="text" name="asset_desc[]" id="<?= $randomClass . '_asset_desc'; ?>" class="form-control <?= $afterDomClass; ?>" disabled/>
    <small class="form-text text-muted"><?= TRANS('ASSET_TYPE'); ?></small>
</div>

<!-- <div class="form-group col-md-2 <?= $randomClass; ?>">
    <input type="text" name="asset_sn[]" id="<?= $randomClass . '_asset_sn'; ?>" class="form-control <?= $afterDomClass; ?>" disabled/>
    <small class="form-text text-muted"><?= TRANS('SERIAL_NUMBER'); ?></small>
</div> -->

<?php
