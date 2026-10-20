
<?php
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'] ?? '', ['professor', 'administrador', 'coordenador', 'gestao'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../conexao.php";

$usuario_id = (int) $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];
$_SESSION['token_cancelamento'] ??= bin2hex(random_bytes(32));
$mensagem = '';

/*CANCELAMENTO DE AGENDAMENTO*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_agendamento'])) {
    $id = filter_input(INPUT_POST, 'id_agendamentos', FILTER_VALIDATE_INT);
    $token = $_POST['token_cancelamento'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['token_cancelamento'], $token)) {
        http_response_code(403);
        exit('Solicitação inválida. Atualize a página.');
    }

    if ($id && $id > 0) {
$sql = "UPDATE agendamentos SET status='Cancelada' WHERE id_agendamentos=? AND solicitante_id=? AND solicitante_tipo=? AND status IN ('Pendente', 'Aprovada')";        $stmt = mysqli_prepare($conexao, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $id, $usuario_id, $usuario_tipo);
            mysqli_stmt_execute($stmt);
            $cancelou = mysqli_stmt_affected_rows($stmt) === 1;
            mysqli_stmt_close($stmt);

            if ($cancelou) {
                header("Location: meus-agendamentos.php?cancelado=1");
                exit();
            }
        }

        $mensagem = "Não foi possível cancelar. A solicitação pode já ter sido processada.";
    } else {
        $mensagem = "Agendamento inválido.";
    }
}

if (($_GET['cancelado'] ?? '') === '1') {
    $mensagem = "Agendamento cancelado com sucesso!";
}

/*BUSCA OS AGENDAMENTOS DO USUÁRIO*/

$agendamentos = [];
$sql = "SELECT a.id_agendamentos, a.descr, a.data_agendamento, a.horario, a.status, amb.nome AS nome_ambiente, amb.tipo AS tipo_ambiente
        FROM agendamentos a
        INNER JOIN ambientes amb ON a.id_ambientes=amb.id_ambientes
        WHERE a.solicitante_id=? AND a.solicitante_tipo=?
        ORDER BY a.data_agendamento DESC, a.id_agendamentos DESC";

$stmt = mysqli_prepare($conexao, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $usuario_id, $usuario_tipo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    while ($linha = mysqli_fetch_assoc($resultado)) $agendamentos[] = $linha;
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meus Agendamentos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/meus_agendamentos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<header>
    <div class="logo"><img src="../logo.png" alt="Logo"></div>
    <nav>
        <a href="">Home</a>
        <a href="#" class="has-submenu">Cursos</a>
        <a href="#" class="has-submenu">A Etec</a>
        <a href="#" class="has-submenu">Equipe Etec</a>
        <li><a href="agendamento.php" class="has-submenu">Agendamento</a></li>
        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href="">TCC</a>
    </nav>
</header>

<div class="titulo"><h1>Meus Agendamentos</h1></div>
<br><br>

<main class="container">

    <?php if ($mensagem): ?>
<div style="background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin:15px 0;" role="status">
    <i class="fa-solid fa-circle-check"></i>
    <?= htmlspecialchars($mensagem) ?>
</div>    <?php endif; ?>

    <?php if (empty($agendamentos)): ?>
        <div class="sem-agendamentos">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h2>Nenhum agendamento encontrado</h2>
            <p>Você ainda não possui solicitações de agendamento.</p>
            <a href="agendamento.php">Fazer um agendamento</a>
        </div>
    <?php else: ?>
        <div class="lista-agendamentos">

        <?php foreach ($agendamentos as $a):
            /* Formata a data */
            $data = date('d/m/Y', strtotime($a['data_agendamento']));

            /* Monta o nome do ambiente */
            $ambiente = $a['tipo_ambiente'] === 'Auditório'
                ? $a['nome_ambiente']
                : $a['nome_ambiente'].' - '.$a['tipo_ambiente'];

            /* Define o status */
            $status = $a['status'];
            $classeStatus = match ($status) {
                'Aprovada' => 'aprovada',
                'Recusada' => 'recusada',
                'Cancelada' => 'cancelada',
                default => 'pendente'
            };
        ?>

            <div class="card-agendamento">
                <div class="informacoes">
                    <h2><?= htmlspecialchars($ambiente) ?> - Solicitação <?= htmlspecialchars($status) ?></h2>
                    <p class="data-horario"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($a['horario']) ?> - <?= $data ?></p>
                    <p class="descricao"><strong>Descrição:</strong> <?= htmlspecialchars($a['descr']) ?></p>
                </div>

                <div class="acoes-agendamento">
                    <div class="status <?= $classeStatus ?>">
                        <i class="fa-solid <?= match ($status) {
                            'Aprovada' => 'fa-circle-check',
                            'Recusada' => 'fa-circle-xmark',
                            'Cancelada' => 'fa-ban',
                            default => 'fa-clock'
                        } ?>"></i>
                        <?= htmlspecialchars($status) ?>
                    </div>

                    <?php if (in_array($status, ['Pendente', 'Aprovada'])): ?>
                        <button type="button" class="btn-cancelar" onclick="abrirConfirmacao(<?= (int)$a['id_agendamentos'] ?>)">
                            <i class="fa-solid fa-xmark"></i> Cancelar
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<br><br>
<!-- Botão voltar -->
<section>
    <div class="container">
        <div class="row">
            <div class="col-sm-2 offset-sm-5 text-center">
                <button class="btn btn-warning text-white btn-block" onclick="location.href='agendamento.php'">Voltar</button>
            </div>
        </div>
    </div>
</section>

<!-- Confirmação de cancelamento -->
<div id="modalCancelar" class="modal-cancelamento">
    <div class="caixa-cancelamento">
        <button type="button" class="fechar-cancelamento" onclick="fecharConfirmacao()" aria-label="Fechar">&times;</button>
        <i class="fa-solid fa-circle-exclamation"></i>
        <h2>Cancelar agendamento?</h2>
        <p>Deseja realmente cancelar esta solicitação?</p>
        <form method="POST" action="meus-agendamentos.php">
            <input type="hidden" name="id_agendamentos" id="idCancelar">
            <input type="hidden" name="token_cancelamento" value="<?= htmlspecialchars($_SESSION['token_cancelamento']) ?>">
            <div class="botoes-cancelamento">
                <button type="button" class="btn-voltar-cancelamento" onclick="fecharConfirmacao()">Voltar</button>
                <button type="submit" name="cancelar_agendamento" class="btn-confirmar-cancelamento">Sim, cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirConfirmacao(id) {
    document.getElementById('idCancelar').value = id;
    document.getElementById('modalCancelar').style.display = 'flex';
}
function fecharConfirmacao() {
    document.getElementById('modalCancelar').style.display = 'none';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
