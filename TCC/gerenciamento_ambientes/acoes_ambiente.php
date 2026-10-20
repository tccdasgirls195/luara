<?php
// ==========================================================
// INICIA A SESSÃO
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// VERIFICA SE O USUÁRIO ESTÁ LOGADO
// ==========================================================
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// ==========================================================
// VERIFICA O TIPO DE USUÁRIO
// ==========================================================
$tipoPermitido = 'administrador';

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== $tipoPermitido) {
    header("Location: ../login/login.php");
    exit();
}

// ==========================================================
// LOGOUT
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../login/login.php");
    exit();
}

// ==========================================================
// CABEÇALHOS ANTI-CACHE
// ==========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================================
// CONEXÃO COM O BANCO
// ==========================================================
require_once dirname(__DIR__) . "/conexao.php";

//=====================================================
// VERIFICAR DADOS
// =====================================================

if (!isset($_GET["acao"]) || !isset($_GET["id"])) {
    header("Location: gerenciar_ambientes.php");
    exit();
}

$acao = $_GET["acao"];
$id = intval($_GET["id"]);

if ($acao == "bloquear") {
    $sql = "UPDATE ambientes
            SET status = 'Bloqueado'
            WHERE id_ambientes = ?";
} elseif ($acao == "ativar") {
    $sql = "UPDATE ambientes
            SET status = 'Ativo'
            WHERE id_ambientes = ?";
} else {
    mysqli_close($conexao);
    die("Ação inválida.");
}

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    mysqli_close($conexao);
    die("Erro ao preparar a consulta.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
$sucesso = mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);
mysqli_close($conexao);

if (!$sucesso) {
    die("Erro ao atualizar o status do ambiente.");
}

$_SESSION['mensagem_sucesso'] = ($acao === "bloquear")
    ? "Ambiente bloqueado com sucesso!"
    : "Ambiente ativado com sucesso!";
header("Location: gerenciar_ambientes.php");
exit();
?>
