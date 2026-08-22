<?php

include("conexao.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}

if (empty($_POST['token']) || empty($_POST['senha'])) {
    die("Token ou senha não informado.");
}

$token = trim($_POST['token']);
$senha = $_POST['senha'];



$sql = "SELECT usuario_id, usuario_tipo
        FROM recuperacao_senha
        WHERE token = ?
        AND expiracao > NOW()";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die("Erro ao consultar o token: " . $conexao->error);
}

$stmt->bind_param("s", $token);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Este link de recuperação é inválido ou já expirou.");
}

$recuperacao = $resultado->fetch_assoc();

$usuario_id = $recuperacao['usuario_id'];
$usuario_tipo = $recuperacao['usuario_tipo'];

$stmt->close();

/*Cria o hash da nova senha*/

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);



$tabelas = [
    'administrador' => 'id_administrador',
    'coordenador'   => 'id_coordenador',
    'professor'     => 'id_professor',
    'representante' => 'id_representante',
    'gestao'        => 'id_gestao'
];


if (!isset($tabelas[$usuario_tipo])) {
    die("Tipo de usuário inválido.");
}

$id_coluna = $tabelas[$usuario_tipo];



$sql_update = "UPDATE $usuario_tipo
               SET senha = ?
               WHERE $id_coluna = ?";

$stmt_update = $conexao->prepare($sql_update);

if (!$stmt_update) {
    die("Erro ao preparar atualização: " . $conexao->error);
}

$stmt_update->bind_param("si", $senha_hash, $usuario_id);

if (!$stmt_update->execute()) {
    die("Erro ao atualizar a senha: " . $stmt_update->error);
}

$stmt_update->close();



$sql_delete = "DELETE FROM recuperacao_senha
               WHERE token = ?";

$stmt_delete = $conexao->prepare($sql_delete);

if (!$stmt_delete) {
    die("Erro ao remover o token: " . $conexao->error);
}

$stmt_delete->bind_param("s", $token);
$stmt_delete->execute();

$stmt_delete->close();


header("Location: login.php?senha_alterada=1");
exit;

?>