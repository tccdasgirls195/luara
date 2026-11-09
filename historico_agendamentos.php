
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'] ?? '', ['professor', 'administrador', 'coordenador', 'gestao'], true)) {
    header("Location: ../index.php");
    exit();
}

require_once "../conexao.php";

$usuario_id = (int) $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];

/* REGISTRA OS AGENDAMENTOS APROVADOS QUE JÁ FORAM REALIZADOS */
$sqlRegistrar = "
    INSERT IGNORE INTO historico_utilizacao
        (id_agendamentos, id_ambientes, nome_ambiente, tipo_ambiente,
         usuario_nome, usuario_tipo, data_utilizacao, horario, descr)
    SELECT
        a.id_agendamentos,
        a.id_ambientes,
        amb.nome,
        amb.tipo,
        COALESCE(NULLIF(a.nome_prof, ''), CONCAT('Usuário #', a.solicitante_id)),
        a.solicitante_tipo,
        a.data_agendamento,
        a.horario,
        a.descr
    FROM agendamentos a
    INNER JOIN ambientes amb ON amb.id_ambientes = a.id_ambientes
    WHERE a.status = 'Aprovada'
      AND a.data_agendamento < CURDATE()
";

mysqli_query($conexao, $sqlRegistrar);

/* BUSCA TODOS OS REGISTROS DO HISTÓRICO */
$historico = [];

$sqlHistorico = "SELECT
    id_historico,
    id_agendamentos,
    id_ambientes,
    nome_ambiente,
    tipo_ambiente,
    usuario_nome,
    usuario_tipo,
    data_utilizacao,
    horario,
    descr
FROM historico_utilizacao
ORDER BY data_utilizacao DESC, horario DESC";

$resultadoHistorico = mysqli_query($conexao, $sqlHistorico);

while ($linha = mysqli_fetch_assoc($resultadoHistorico)) {
    $historico[] = $linha;
}

/* NOTIFICAÇÕES */
$notificacoes = [];
$quantidadePendentes = 0;

if ($usuario_tipo === 'gestao') {

    $sqlNotificacoes = "
        SELECT a.id_agendamentos, a.nome_prof, a.data_agendamento,
               a.horario, a.status,
               amb.nome AS nome_ambiente,
               amb.tipo AS tipo_ambiente
        FROM agendamentos a
        INNER JOIN ambientes amb ON amb.id_ambientes = a.id_ambientes
        WHERE a.status = 'Pendente'
        ORDER BY a.data_agendamento ASC, a.horario ASC
        LIMIT 30
    ";

    $stmtNotificacoes = mysqli_prepare($conexao, $sqlNotificacoes);
    mysqli_stmt_execute($stmtNotificacoes);
    $resultadoNotificacoes = mysqli_stmt_get_result($stmtNotificacoes);

    while ($linha = mysqli_fetch_assoc($resultadoNotificacoes)) {
        $notificacoes[] = $linha;
    }

    mysqli_stmt_close($stmtNotificacoes);
    $quantidadePendentes = count($notificacoes);

} elseif (in_array($usuario_tipo, ['professor', 'coordenador', 'administrador'], true)) {

    $sqlNotificacoes = "
        SELECT a.id_agendamentos, a.data_agendamento,
               a.horario, a.status,
               amb.nome AS nome_ambiente,
               amb.tipo AS tipo_ambiente
        FROM agendamentos a
        INNER JOIN ambientes amb ON amb.id_ambientes = a.id_ambientes
        WHERE a.solicitante_id = ?
          AND a.solicitante_tipo = ?
          AND a.status IN ('Aprovada', 'Recusada')
        ORDER BY a.id_agendamentos DESC
        LIMIT 30
    ";

    $stmtNotificacoes = mysqli_prepare($conexao, $sqlNotificacoes);
    mysqli_stmt_bind_param($stmtNotificacoes, "is", $usuario_id, $usuario_tipo);
    mysqli_stmt_execute($stmtNotificacoes);
    $resultadoNotificacoes = mysqli_stmt_get_result($stmtNotificacoes);

    while ($linha = mysqli_fetch_assoc($resultadoNotificacoes)) {
        $notificacoes[] = $linha;
    }

    mysqli_stmt_close($stmtNotificacoes);
}

/* FUNÇÕES */
function h($valor) {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function nomeAmbiente($nome, $tipo) {
    return $tipo === 'Auditório' ? $nome : $nome . ' - ' . $tipo;
}

function tipoUsuario($tipo) {
    return match ($tipo) {
        'professor' => 'Professor(a)',
        'coordenador' => 'Coordenador(a)',
        'administrador' => 'Administrador(a)',
        'gestao' => 'Gestão',
        default => ucfirst((string) $tipo)
    };
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/historico_agendamentos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/responsivo.css">

    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#72c67c">
    <link rel="apple-touch-icon" href="../icons/apple-touch-icon.png">
</head>

<body>

<header>
    <div class="logo">
        <img src="../logo.png" alt="Logo">
    </div>

    <nav>
        <a href="">Home</a>
        <a href="#" class="has-submenu">Cursos</a>

        <li>
            <a href="#" class="has-submenu">A Etec</a>
            <ul class="submenu">
                <li>
                    <a href="../calendario/calendario.php">Eventos</a>
                </li>
            </ul>
        </li>

        <a href="#" class="has-submenu">Equipe Etec</a>

        <li>
            <a href="agendamento.php" class="has-submenu">Agendamento</a>
            <ul class="submenu">
                <li>
                    <a href="meus-agendamentos.php">Meus agendamentos</a>
                </li>
            </ul>
        </li>

        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href="">TCC</a>
    </nav>

    <div class="acoes-header">
        <button type="button" class="botao-header botao-menu"
                aria-label="Abrir menu lateral"
                onclick="abrirBarraLateral()">
            <i class="fa-solid fa-bars"></i>
            <span id="indicadorNotificacoes"></span>
        </button>
    </div>
</header>

<div class="titulo">
    <h1>Histórico de agendamentos</h1>
</div>

<div id="fundoBarraLateral" class="fundo-barra"
     onclick="fecharBarraLateral()"></div>

<aside id="barraLateral" class="barra-lateral">

    <div class="barra-topo"></div>

    <div class="barra-opcao-fixa">

        <?php if ($usuario_tipo === 'gestao'): ?>

            <a href="solicitacoes_gestao.php" class="opcao-lateral">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Solicitações</span>

                <?php if ($quantidadePendentes > 0): ?>
                    <span class="contador-notificacoes"><?= $quantidadePendentes ?></span>
                <?php endif; ?>
            </a>

            <br>

            <a href="historico_agendamentos.php" class="opcao-lateral">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Histórico</span>
            </a>

        <?php elseif ($usuario_tipo === 'coordenador'): ?>

            <a href="../gerenciamento_representante/gerenciar_representantes.php"
               class="opcao-lateral">
                <i class="fa-solid fa-user-plus"></i>
                <span>Cadastro representante</span>
            </a>

            <a href="fixacao_agendamento.php" class="opcao-lateral">
                <i class="fa-solid fa-calendar-plus"></i>
                <span>Fixar</span>
            </a>

        <?php elseif ($usuario_tipo === 'administrador'): ?>

            <a href="../opcoes.php" class="opcao-lateral">
                <i class="fa-solid fa-gear"></i>
                <span>Opções</span>
            </a>

        <?php endif; ?>

    </div>

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

                        <a class="notificacao notificacao-link"
                           data-notificacao-id="<?= h($notificacao['id_agendamentos'] . '-' . $notificacao['status']) ?>"
                           href="solicitacoes_gestao.php">

                            <i class="fa-solid fa-bell notificacao-pendente"></i>

                            <span>
                                Nova solicitação: <?= h($notificacao['nome_prof']) ?> —
                                <?= h(nomeAmbiente($notificacao['nome_ambiente'], $notificacao['tipo_ambiente'])) ?>

                                <small>
                                    <?= h(date('d/m/Y', strtotime($notificacao['data_agendamento']))) ?>
                                    às <?= h($notificacao['horario']) ?>
                                </small>
                            </span>
                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            <?php elseif (in_array($usuario_tipo, ['professor', 'coordenador', 'administrador'], true)): ?>

                <?php if (empty($notificacoes)): ?>

                    <div class="notificacao">
                        <i class="fa-regular fa-bell"></i>
                        <span>Você não tem atualizações de solicitações.</span>
                    </div>

                <?php else: ?>

                    <?php foreach ($notificacoes as $notificacao): ?>

                        <?php $aprovada = $notificacao['status'] === 'Aprovada'; ?>

                        <a class="notificacao notificacao-link"
                           data-notificacao-id="<?= h($notificacao['id_agendamentos'] . '-' . $notificacao['status']) ?>"
                           href="meus-agendamentos.php">

                            <i class="fa-solid <?= $aprovada ? 'fa-circle-check notificacao-ok' : 'fa-circle-xmark notificacao-erro' ?>"></i>

                            <span>
                                Sua solicitação para
                                <?= h(nomeAmbiente($notificacao['nome_ambiente'], $notificacao['tipo_ambiente'])) ?>
                                foi <?= $aprovada ? 'aprovada' : 'recusada' ?>.

                                <small>
                                    <?= h(date('d/m/Y', strtotime($notificacao['data_agendamento']))) ?>
                                    às <?= h($notificacao['horario']) ?>
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

    <div class="barra-logout">
        <form action="agendamento.php" method="POST">
            <input type="hidden" name="logout" value="1">

            <button type="submit">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sair</span>
            </button>
        </form>
    </div>

</aside>

<main class="container historico-container">

    <section class="resumo-historico">

        <div class="resumo-texto">
            <i class="fa-solid fa-clock-rotate-left"></i>

            <div>
                <h2>Últimas utilizações</h2>
            </div>
        </div>

        <span class="total-registros">
            <?= count($historico) ?> registro(s)
        </span>

    </section>

    <?php if (empty($historico)): ?>

        <div class="sem-historico">
            <i class="fa-solid fa-clock-rotate-left"></i>

            <h2>Nenhuma utilização registrada</h2>

            <p>
                As utilizações aparecerão aqui depois que os agendamentos
                aprovados tiverem sua data concluída.
            </p>
        </div>

    <?php else: ?>

        <section class="secao-historico historico-completo">

 

            <div class="tabela-historico-container">

                <table class="tabela-historico">

                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pessoa</th>
                            <th>Ambiente utilizado</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($historico as $registro): ?>

                            <tr>
                                <td class="coluna-data">
                                    <?= h(date('d/m/Y', strtotime($registro['data_utilizacao']))) ?>
                                </td>

                                <td class="coluna-pessoa">
                                    <?= h($registro['usuario_nome']) ?>
                                </td>

                                <td class="coluna-ambiente">
                                    <?= h(nomeAmbiente($registro['nome_ambiente'], $registro['tipo_ambiente'])) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    <?php endif; ?>

    <br><br>

    <section>
        <div class="container">
            <div class="row">
                <div class="col-sm-2 offset-sm-5 text-center">

                    <button
                        type="button"
                        class="btn btn-warning text-white btn-block"
                        onclick="voltar()">
                        Voltar
                    </button>

                </div>
            </div>
        </div>
    </section>

</main>

<script>
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
    if (event.key === "Escape") fecharBarraLateral();
});

/* CONTROLE DAS NOTIFICAÇÕES LIDAS */
(function () {
    const areaNotificacoes = document.querySelector('.area-notificacoes');
    const indicador = document.getElementById('indicadorNotificacoes');

    if (!areaNotificacoes || !indicador) return;

    const usuarioId = <?= json_encode((string) $usuario_id) ?>;
    const usuarioTipo = <?= json_encode((string) $usuario_tipo) ?>;
    const chaveLeitura = 'tcc_notificacoes_lidas_' + usuarioTipo + '_' + usuarioId;

    const notificacoesAtuais = Array.from(
        areaNotificacoes.querySelectorAll('.notificacao-link')
    );

    let lidas = new Set();

    try {
        const salvas = JSON.parse(localStorage.getItem(chaveLeitura) || '[]');
        if (Array.isArray(salvas)) lidas = new Set(salvas.map(String));
    } catch (erro) {
        lidas = new Set();
    }

    function obterId(notificacao) {
        return notificacao.dataset.notificacaoId ||
            (notificacao.href + '|' + notificacao.textContent.replace(/\s+/g, ' ').trim());
    }

    function atualizarIndicador() {
        indicador.style.display = notificacoesAtuais.some(
            n => !lidas.has(String(obterId(n)))
        ) ? 'block' : 'none';
    }

    areaNotificacoes.addEventListener('click', function () {
        notificacoesAtuais.forEach(n => lidas.add(String(obterId(n))));

        try {
            localStorage.setItem(chaveLeitura, JSON.stringify(Array.from(lidas)));
        } catch (erro) {}

        atualizarIndicador();
    });

    atualizarIndicador();
})();

/* BOTÃO VOLTAR */
function voltar() {
    window.history.back();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/menu-responsivo.js"></script>
<script src="../js/pwa.js"></script>

</body>
</html>
