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


$textJson =  (isset($post['textJson']) ? $post['textJson'] : "");
$key = (isset($post['key']) ? $post['key'] : "");
$value = (isset($post['value']) ? $post['value'] : "");


if (empty($textJson) || empty($key)) {
    echo "Null values";
    exit;
}



// var_dump([
//     'textJson' => $textJson,
//     'key' => $key,
//     'value' => $value,
//     'json atualizado' => updateJson($textJson, $key, $value),
//     'array atualizado' => json_decode(updateJson($textJson, $key, $value), true),
// ]); exit;


$data = [];


$data['json'] = updateJson($textJson, $key, $value);

echo json_encode($data);