<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "MODELO_TCC";


$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);


if ($conn->connect_error) {

    die(
        "Erro na conexão com o banco de dados: "
        . $conn->connect_error
    );

}


$conn->set_charset("utf8");


// =====================================================
// VERIFICAR DADOS
// =====================================================

if (
    !isset($_GET["acao"]) ||
    !isset($_GET["tipo"]) ||
    !isset($_GET["id"])
) {

    header("Location: gerenciar_usuarios.php");

    exit;

}


$acao = $_GET["acao"];

$tipo = $_GET["tipo"];

$id = intval($_GET["id"]);


// =====================================================
// TABELAS
// =====================================================

$tabelas = [

    "administrador" => [
        "tabela" => "administrador",
        "id" => "id_administrador"
    ],

    "coordenador" => [
        "tabela" => "coordenador",
        "id" => "id_coordenador"
    ],

    "professor" => [
        "tabela" => "professor",
        "id" => "id_professor"
    ],

    "representante" => [
        "tabela" => "representante",
        "id" => "id_representante"
    ],

    "gestao" => [
        "tabela" => "gestao",
        "id" => "id_gestao"
    ]

];


// =====================================================
// VERIFICAR TIPO
// =====================================================

if (!isset($tabelas[$tipo])) {

    die("Tipo de usuário inválido.");

}


$tabela = $tabelas[$tipo]["tabela"];

$campoId = $tabelas[$tipo]["id"];


// =====================================================
// BLOQUEAR
// =====================================================

if ($acao == "bloquear") {


    $sql = "UPDATE $tabela

            SET status = 'Bloqueado'

            WHERE $campoId = ?";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Erro ao preparar a ação: "
            . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $id
    );


    if (!$stmt->execute()) {

        die(
            "Erro ao bloquear o usuário: "
            . $stmt->error
        );

    }

}


// =====================================================
// ATIVAR
// =====================================================

elseif ($acao == "ativar") {


    $sql = "UPDATE $tabela

            SET status = 'Ativo'

            WHERE $campoId = ?";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Erro ao preparar a ação: "
            . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $id
    );


    if (!$stmt->execute()) {

        die(
            "Erro ao ativar o usuário: "
            . $stmt->error
        );

    }

}


// =====================================================
// AÇÃO INVÁLIDA
// =====================================================

else {

    die("Ação inválida.");

}


// =====================================================
// VOLTAR PARA GERENCIAMENTO
// =====================================================

header(
    "Location: gerenciar_usuarios.php"
);

exit;

?>