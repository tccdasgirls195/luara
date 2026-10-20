<?php
// ==========================================================
// INICIA A SESSÃO E VERIFICA PERMISSÃO DE ADMINISTRADOR
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: ../login/login.php");
    exit();
}

require_once dirname(__DIR__) . "/conexao.php";

// ==========================================================
// CAPTURA OS PARÂMETROS DA AÇÃO (AÇÃO, TIPO E ID)
// ==========================================================
$acao = $_GET['acao'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$id   = intval($_GET['id'] ?? 0);

if (empty($acao) || empty($tipo) || $id <= 0) {
    header("Location: gerenciar_usuarios.php");
    exit();
}

// Define o novo status com base na ação solicitada
$novoStatus = ($acao === 'ativar') ? 'Ativo' : 'Bloqueado';

// Mapeia a tabela correta e a respectiva coluna de ID de acordo com o tipo de usuário
$tabelas = [
    'administrador' => ['tabela' => 'administrador', 'id' => 'id_administrador'],
    'coordenador'   => ['tabela' => 'coordenador',   'id' => 'id_coordenador'],
    'professor'     => ['tabela' => 'professor',     'id' => 'id_professor'],
    'representante' => ['tabela' => 'representante', 'id' => 'id_representante'],
    'gestao'        => ['tabela' => 'gestao',        'id' => 'id_gestao']
];

if (!array_key_exists($tipo, $tabelas)) {
    header("Location: gerenciar_usuarios.php");
    exit();
}

$nomeTabela = $tabelas[$tipo]['tabela'];
$colunaId   = $tabelas[$tipo]['id'];

// ==========================================================
// EXECUTA A ATUALIZAÇÃO NO BANCO DE DADOS
// ==========================================================
$sql = "UPDATE $nomeTabela SET status = ? WHERE $colunaId = ?";
$stmt = mysqli_prepare($conexao, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $novoStatus, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($conexao);

// ==========================================================
// REDIRECIONA DE VOLTA COM A MENSAGEM CORRETA
// ==========================================================
$_SESSION['mensagem_sucesso'] = ($novoStatus === 'Bloqueado')
    ? 'Usuário bloqueado com sucesso!'
    : 'Usuário ativado com sucesso!';
header("Location: gerenciar_usuarios.php");
exit();
?>