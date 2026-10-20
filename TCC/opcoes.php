<?php
// INICIA A SESSÃO
session_start();

//VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
// VERIFICA O TIPO DE USUÁRIO
$tiposPermitidos = [
    'professor',
    'administrador',
    'coordenador',
    'gestao'
];

if (!in_array($_SESSION['usuario_tipo'], $tiposPermitidos)) {
    header("Location: index.php");
    exit();
}

// LOGOUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {

    session_unset();
    session_destroy();

    header("Location:../index.php");
    exit();
}

// CABEÇALHOS ANTI-CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");


// CONEXÃO COM O BANCO
require_once "conexao.php";

// QUEM ESTÁ LOGADO
$usuario_id = $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];

// ==========================================================
// NOTIFICAÇÕES DO MENU LATERAL
// Gestão vê solicitações pendentes; professor/coordenador
// vê o resultado das próprias solicitações.
// ==========================================================
$notificacoes = [];
$quantidadePendentes = 0;

if ($usuario_tipo === 'gestao') {
    $sqlNotificacoes = "
        SELECT
            a.id_agendamentos,
            a.nome_prof,
            a.data_agendamento,
            a.horario,
            a.status,
            amb.nome AS nome_ambiente,
            amb.tipo AS tipo_ambiente
        FROM agendamentos a
        INNER JOIN ambientes amb
            ON amb.id_ambientes = a.id_ambientes
        WHERE a.status = 'Pendente'
        ORDER BY a.data_agendamento ASC, a.horario ASC, a.id_agendamentos DESC
        LIMIT 30
    ";

    $stmtNotificacoes = mysqli_prepare($conexao, $sqlNotificacoes);
    if ($stmtNotificacoes) {
        mysqli_stmt_execute($stmtNotificacoes);
        $resultadoNotificacoes = mysqli_stmt_get_result($stmtNotificacoes);
        while ($linhaNotificacao = mysqli_fetch_assoc($resultadoNotificacoes)) {
            $notificacoes[] = $linhaNotificacao;
        }
        mysqli_stmt_close($stmtNotificacoes);
    }
    $quantidadePendentes = count($notificacoes);

} elseif (in_array($usuario_tipo, ['professor', 'coordenador'], true)) {
    $sqlNotificacoes = "
        SELECT
            a.id_agendamentos,
            a.data_agendamento,
            a.horario,
            a.status,
            amb.nome AS nome_ambiente,
            amb.tipo AS tipo_ambiente
        FROM agendamentos a
        INNER JOIN ambientes amb
            ON amb.id_ambientes = a.id_ambientes
        WHERE a.solicitante_id = ?
          AND a.solicitante_tipo = ?
          AND a.status IN ('Aprovada', 'Recusada')
        ORDER BY a.id_agendamentos DESC
        LIMIT 30
    ";

    $stmtNotificacoes = mysqli_prepare($conexao, $sqlNotificacoes);
    if ($stmtNotificacoes) {
        mysqli_stmt_bind_param(
            $stmtNotificacoes,
            "is",
            $usuario_id,
            $usuario_tipo
        );
        mysqli_stmt_execute($stmtNotificacoes);
        $resultadoNotificacoes = mysqli_stmt_get_result($stmtNotificacoes);
        while ($linhaNotificacao = mysqli_fetch_assoc($resultadoNotificacoes)) {
            $notificacoes[] = $linhaNotificacao;
        }
        mysqli_stmt_close($stmtNotificacoes);
    }
}


?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Opções</title>

    <link rel="stylesheet" href="css/opcoes.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<header>

    <div class="logo">
        <img src="logo.png">
    </div>

    <nav>

        <a href="">Home</a>

        <a href="#" class="has-submenu">Cursos</a>

        <a href="#" class="has-submenu">A Etec</a>

        <a href="#" class="has-submenu">Equipe Etec</a>
            </ul>
        </li>

        <a href="#" class="has-submenu">Notícias</a>

        <a href="">Empregos & Estágios</a>

        <a href="">Parceiros</a>

        <a href="">TCC</a>

    </nav>

    <!-- BOTÕES DA BARRA LATERAL -->
    <div class="acoes-header">

        <button
            type="button"
            class="botao-header botao-menu"
            aria-label="Abrir menu lateral"
            onclick="abrirBarraLateral()">
            <i class="fa-solid fa-bars"></i>
        </button>

    </div>

</header>


<section class="titulo">

    <h1>Opções</h1>

</section>

<!-- =====================================================
     BARRA LATERAL
====================================================== -->

<div
    id="fundoBarraLateral"
    class="fundo-barra"
    onclick="fecharBarraLateral()">
</div>

<aside id="barraLateral" class="barra-lateral">

    <div class="barra-topo"></div>

    <!-- OPÇÃO FIXA DE CIMA -->
    <div class="barra-opcao-fixa">

        <?php if ($usuario_tipo === 'gestao'): ?>

            <a href="solicitacoes_gestao.php" class="opcao-lateral">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Solicitações</span>
                <?php if ($quantidadePendentes > 0): ?>
                    <span class="contador-notificacoes"><?= $quantidadePendentes ?></span>
                <?php endif; ?>
            </a>

        <?php elseif ($usuario_tipo === 'coordenador'): ?>

            <a href="#" class="opcao-lateral">
                <i class="fa-solid fa-user-plus"></i>
                <span>Cadastro representante</span>
            </a>

        <?php endif; ?>

    </div>


    <!-- NOTIFICAÇÕES DINÂMICAS -->
    <div class="area-notificacoes">
        <h2>Notificações</h2>

        <div class="lista-notificacoes">

            <?php if ($usuario_tipo === 'gestao'): ?>

                <?php if (empty($notificacoes)): ?>
                    <div class="notificacao">
                        <i class="fa-regular fa-bell"></i>
                        <span>Não há solicitações pendentes.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificacoes as $notificacao): ?>
                        <?php
                            $nomeAmbiente = $notificacao['nome_ambiente'];
                            if ($notificacao['tipo_ambiente'] !== 'Auditório') {
                                $nomeAmbiente .= ' - ' . $notificacao['tipo_ambiente'];
                            }
                        ?>
                        <a class="notificacao notificacao-link" href="solicitacoes_gestao.php">
                            <i class="fa-solid fa-bell notificacao-pendente"></i>
                            <span>
                                Nova solicitação: <?= htmlspecialchars($notificacao['nome_prof']) ?>
                                — <?= htmlspecialchars($nomeAmbiente) ?>
                                <small>
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($notificacao['data_agendamento']))) ?>
                                    às <?= htmlspecialchars($notificacao['horario']) ?>
                                </small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php elseif (in_array($usuario_tipo, ['professor', 'coordenador'], true)): ?>

                <?php if (empty($notificacoes)): ?>
                    <div class="notificacao">
                        <i class="fa-regular fa-bell"></i>
                        <span>Você não tem atualizações de solicitações.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificacoes as $notificacao): ?>
                        <?php
                            $nomeAmbiente = $notificacao['nome_ambiente'];
                            if ($notificacao['tipo_ambiente'] !== 'Auditório') {
                                $nomeAmbiente .= ' - ' . $notificacao['tipo_ambiente'];
                            }
                            $aprovada = $notificacao['status'] === 'Aprovada';
                        ?>
                        <a class="notificacao notificacao-link" href="meus-agendamentos.php">
                            <?php if ($aprovada): ?>
                                <i class="fa-solid fa-circle-check notificacao-ok"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle-xmark notificacao-erro"></i>
                            <?php endif; ?>
                            <span>
                                Sua solicitação para <?= htmlspecialchars($nomeAmbiente) ?>
                                foi <?= $aprovada ? 'aprovada' : 'recusada' ?>.
                                <small>
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($notificacao['data_agendamento']))) ?>
                                    às <?= htmlspecialchars($notificacao['horario']) ?>
                                </small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="notificacao">
                    <i class="fa-regular fa-bell"></i>
                    <span>Não há notificações para exibir.</span>
                </div>
            <?php endif; ?>

        </div>
    </div>


    <!-- LOGOUT: FICA SEMPRE EMBAIXO -->
    <div class="barra-logout">

        <form action="index.php" method="POST">

            <input type="hidden" name="logout" value="1">

            <button type="submit">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sair</span>
            </button>

        </form>

    </div>

</aside>

<div class="botoes">

    <!-- AGENDAMENTOS -->
    <button
        type="button"
        class="btn"
        onclick="window.location.href='agendamento/agendamento.php';">

        Agendamentos
        <i class="fa-solid fa-right-to-bracket"></i>

    </button>


    <!-- CALENDÁRIO -->
    <button
        type="button"
        class="btn"
        onclick="window.location.href='calendario/calendario.php';">

        Calendário
        <i class="fa-solid fa-right-to-bracket"></i>

    </button>


    <!-- USUÁRIOS -->
    <button
        type="button"
        class="btn"
        onclick="window.location.href='gerenciamento_usuarios/gerenciar_usuarios.php';">
        Usuários
        <i class="fa-solid fa-right-to-bracket"></i>

    </button>

    <!-- AMBIENTES -->
    <button
        type="button"
        class="btn"
        onclick="window.location.href='gerenciamento_ambientes/gerenciar_ambientes.php';">

        Ambientes
        <i class="fa-solid fa-right-to-bracket"></i>

    </button>

</div>

<script>

// ==========================================================
// BARRA LATERAL
// ==========================================================

function abrirBarraLateral() {

document.getElementById("barraLateral").classList.add("aberta");
document.getElementById("fundoBarraLateral").classList.add("ativo");

document.body.classList.add("barra-aberta");
}


function fecharBarraLateral() {

document.getElementById("barraLateral").classList.remove("aberta");
document.getElementById("fundoBarraLateral").classList.remove("ativo");

document.body.classList.remove("barra-aberta");
}


document.addEventListener("keydown", function(event) {

if (event.key === "Escape") {
    fecharBarraLateral();
}

});




</script>
