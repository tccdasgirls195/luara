<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

require_once "../conexao.php";

// QUEM ESTÁ LOGADO
$usuario_id = $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];

$erro = "";
$sucesso = "";
$editar = null;

$diasSemana = [
    1 => "Segunda-feira",
    2 => "Terça-feira",
    3 => "Quarta-feira",
    4 => "Quinta-feira",
    5 => "Sexta-feira",
    6 => "Sábado"
];

$horariosDisponiveis = [
    ["inicio" => "07:30", "fim" => "08:20", "nome" => "7h30 - 8h20"],
    ["inicio" => "08:20", "fim" => "09:10", "nome" => "8h20 - 9h10"],
    ["inicio" => "09:10", "fim" => "10:00", "nome" => "9h10 - 10h"],
    ["inicio" => "10:20", "fim" => "11:10", "nome" => "10h20 - 11h10"],
    ["inicio" => "11:10", "fim" => "12:00", "nome" => "11h10 - 12h"],
    ["inicio" => "13:00", "fim" => "13:50", "nome" => "13h - 13h50"],
    ["inicio" => "13:50", "fim" => "14:40", "nome" => "13h50 - 14h40"],
    ["inicio" => "14:40", "fim" => "15:30", "nome" => "14h40 - 15h30"],
    ["inicio" => "15:30", "fim" => "16:20", "nome" => "15h30 - 16h20"],
    ["inicio" => "16:20", "fim" => "17:10", "nome" => "16h20 - 17h10"],
    ["inicio" => "18:00", "fim" => "18:50", "nome" => "18h - 18h50"],
    ["inicio" => "18:50", "fim" => "19:40", "nome" => "18h50 - 19h40"],
    ["inicio" => "19:40", "fim" => "20:00", "nome" => "19h40 - 20h"],
    ["inicio" => "20:00", "fim" => "20:50", "nome" => "20h - 20h50"],
    ["inicio" => "20:50", "fim" => "21:40", "nome" => "20h50 - 21h40"],
    ["inicio" => "21:40", "fim" => "22:30", "nome" => "21h40 - 22h30"]
];

$tiposResponsaveis = [
    "professor" => ["professor", "id_professor"],
    "coordenador" => ["coordenador", "id_coordenador"],
    "administrador" => ["administrador", "id_administrador"]
];

function e($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

// TRANSFORMAR OS HORÁRIOS MARCADOS EM INTERVALOS CONTÍNUOS
function agruparHorarios($selecionados, $horariosDisponiveis) {
    $intervalos = [];

    foreach ($selecionados as $dia => $indices) {
        $indices = array_values(array_unique(array_map("intval", $indices)));
        sort($indices);

        $inicioGrupo = null;
        $fimGrupo = null;
        $ultimoIndice = null;

        foreach ($indices as $indice) {
            if (!isset($horariosDisponiveis[$indice])) {
                continue;
            }

            $horario = $horariosDisponiveis[$indice];

            if (
                $inicioGrupo === null ||
                $ultimoIndice === null ||
                $indice !== $ultimoIndice + 1 ||
                $fimGrupo !== $horario["inicio"]
            ) {
                if ($inicioGrupo !== null) {
                    $intervalos[] = [
                        "dia" => (int)$dia,
                        "inicio" => $inicioGrupo,
                        "fim" => $fimGrupo
                    ];
                }

                $inicioGrupo = $horario["inicio"];
                $fimGrupo = $horario["fim"];
            } else {
                $fimGrupo = $horario["fim"];
            }

            $ultimoIndice = $indice;
        }

        if ($inicioGrupo !== null) {
            $intervalos[] = [
                "dia" => (int)$dia,
                "inicio" => $inicioGrupo,
                "fim" => $fimGrupo
            ];
        }
    }

    return $intervalos;
}

// EXCLUIR FIXAÇÃO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir"])) {
    $id = filter_input(INPUT_POST, "id_horario_fixo", FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $sql = "DELETE FROM horarios_fixos WHERE id_horario_fixo = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION["mensagem_sucesso"] = "Fixação excluída com sucesso!";

        header("Location: fixacao_agendamento.php");
        exit();
    }

    $erro = "Não foi possível excluir a fixação.";
}

// CADASTRAR OU ATUALIZAR FIXAÇÕES
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["salvar"])) {
    $id = filter_input(INPUT_POST, "id_horario_fixo", FILTER_VALIDATE_INT);
    $id = $id ?: 0;

    $responsavelSelecionado = trim($_POST["responsavel"] ?? "");
    $partesResponsavel = explode("|", $responsavelSelecionado, 2);

    $responsavelTipo = $partesResponsavel[0] ?? "";
    $responsavelId = isset($partesResponsavel[1])
        ? filter_var($partesResponsavel[1], FILTER_VALIDATE_INT)
        : false;

    $ambiente = filter_input(INPUT_POST, "id_ambientes", FILTER_VALIDATE_INT);
    $selecionados = $_POST["horarios"] ?? [];

    // MANTER OS DADOS NO FORMULÁRIO CASO OCORRA UM ERRO
    $editar = [
        "id_horario_fixo" => $id,
        "responsavel_id" => $responsavelId ?: "",
        "responsavel_tipo" => $responsavelTipo,
        "id_ambientes" => $ambiente ?: ""
    ];

    // VALIDAR OS HORÁRIOS RECEBIDOS
    $selecionadosValidos = [];

    if (is_array($selecionados)) {
        foreach ($selecionados as $dia => $indices) {
            $dia = filter_var($dia, FILTER_VALIDATE_INT);

            if (!$dia || !isset($diasSemana[$dia]) || !is_array($indices)) {
                continue;
            }

            foreach ($indices as $indice) {
                $indice = filter_var($indice, FILTER_VALIDATE_INT);

                if ($indice !== false && isset($horariosDisponiveis[$indice])) {
                    $selecionadosValidos[$dia][] = $indice;
                }
            }
        }
    }

    $intervalos = agruparHorarios($selecionadosValidos, $horariosDisponiveis);

    // VALIDAR DADOS
    if (
        !isset($tiposResponsaveis[$responsavelTipo]) ||
        !$responsavelId ||
        $responsavelId < 1
    ) {
        $erro = "Selecione um responsável válido.";
    } elseif (!$ambiente || $ambiente < 1) {
        $erro = "Selecione um laboratório válido.";
    } elseif (count($intervalos) === 0) {
        $erro = "Selecione pelo menos um horário na grade.";
    } elseif ($id > 0 && count($intervalos) !== 1) {
        $erro = "Para editar, selecione apenas um intervalo contínuo. Para cadastrar vários dias ou intervalos, utilize um novo cadastro.";
    } else {
        // VERIFICAR SE O RESPONSÁVEL EXISTE
        [$tabela, $colunaId] = $tiposResponsaveis[$responsavelTipo];

        $sql = "SELECT COUNT(*) AS total FROM $tabela WHERE $colunaId = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $responsavelId);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $responsavelExiste = (int)mysqli_fetch_assoc($resultado)["total"] > 0;
        mysqli_stmt_close($stmt);

        // VERIFICAR SE O LABORATÓRIO EXISTE
        $sql = "SELECT COUNT(*) AS total FROM ambientes WHERE id_ambientes = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $ambiente);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $ambienteExiste = (int)mysqli_fetch_assoc($resultado)["total"] > 0;
        mysqli_stmt_close($stmt);

        if (!$responsavelExiste) {
            $erro = "O responsável selecionado não foi encontrado.";
        } elseif (!$ambienteExiste) {
            $erro = "O laboratório selecionado não foi encontrado.";
        } else {
            mysqli_begin_transaction($conexao);

            try {
                foreach ($intervalos as $intervalo) {
                    $dia = $intervalo["dia"];
                    $inicio = $intervalo["inicio"] . ":00";
                    $fim = $intervalo["fim"] . ":00";

                    // VERIFICAR CONFLITOS DE HORÁRIO
                    // Só existe conflito quando os intervalos se sobrepõem.
                    // A própria fixação editada é desconsiderada.
                    $sql = "SELECT id_horario_fixo
                            FROM horarios_fixos
                            WHERE dia_semana = ?
                              AND horario_inicio < ?
                              AND horario_fim > ?
                              AND (
                                  (responsavel_id = ? AND responsavel_tipo = ?)
                                  OR id_ambientes = ?
                              )
                              AND id_horario_fixo <> ?
                            LIMIT 1";

                    $stmt = mysqli_prepare($conexao, $sql);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "issisii",
                        $dia,
                        $fim,
                        $inicio,
                        $responsavelId,
                        $responsavelTipo,
                        $ambiente,
                        $id
                    );

                    mysqli_stmt_execute($stmt);
                    $resultado = mysqli_stmt_get_result($stmt);
                    $temConflito = mysqli_num_rows($resultado) > 0;
                    mysqli_stmt_close($stmt);

                    if ($temConflito) {
                        throw new Exception(
                            "Já existe uma fixação conflitante para esse responsável ou laboratório nesse dia e horário."
                        );
                    }
                }

                if ($id > 0) {
                    // ATUALIZAR FIXAÇÃO
                    $intervalo = $intervalos[0];

                    $inicio = $intervalo["inicio"] . ":00";
                    $fim = $intervalo["fim"] . ":00";

                    $sql = "UPDATE horarios_fixos
                            SET responsavel_id = ?,
                                responsavel_tipo = ?,
                                id_ambientes = ?,
                                dia_semana = ?,
                                horario_inicio = ?,
                                horario_fim = ?
                            WHERE id_horario_fixo = ?";

                    $stmt = mysqli_prepare($conexao, $sql);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "isiissi",
                        $responsavelId,
                        $responsavelTipo,
                        $ambiente,
                        $intervalo["dia"],
                        $inicio,
                        $fim,
                        $id
                    );

                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $mensagemSucesso = "atualizado";

                } else {
                    // CADASTRAR TODAS AS FIXAÇÕES
                    $sql = "INSERT INTO horarios_fixos
                            (
                                responsavel_id,
                                responsavel_tipo,
                                id_ambientes,
                                dia_semana,
                                horario_inicio,
                                horario_fim
                            )
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = mysqli_prepare($conexao, $sql);

                    foreach ($intervalos as $intervalo) {
                        $dia = $intervalo["dia"];
                        $inicio = $intervalo["inicio"] . ":00";
                        $fim = $intervalo["fim"] . ":00";

                        mysqli_stmt_bind_param(
                            $stmt,
                            "isiiss",
                            $responsavelId,
                            $responsavelTipo,
                            $ambiente,
                            $dia,
                            $inicio,
                            $fim
                        );

                        mysqli_stmt_execute($stmt);
                    }

                    mysqli_stmt_close($stmt);
                    $mensagemSucesso = "cadastrado";
                }

                mysqli_commit($conexao);

                if ($mensagemSucesso === "cadastrado") {
                    $_SESSION["mensagem_sucesso"] = "Fixações cadastradas com sucesso!";
                } else {
                    $_SESSION["mensagem_sucesso"] = "Fixação atualizada com sucesso!";
                }

                header("Location: fixacao_agendamento.php");
                exit();

            } catch (Exception $ex) {
                mysqli_rollback($conexao);
                $erro = $ex->getMessage();
            }
        }
    }
}

// CARREGAR FIXAÇÃO PARA EDIÇÃO
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["editar"])) {
    $idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

    if ($idEditar && $idEditar > 0) {
        $sql = "SELECT * FROM horarios_fixos WHERE id_horario_fixo = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $idEditar);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $editar = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmt);

        if (!$editar) {
            $erro = "Fixação não encontrada.";
        }
    }
}

// MENSAGEM DE SUCESSO TEMPORÁRIA
if (isset($_SESSION["mensagem_sucesso"])) {
    $sucesso = $_SESSION["mensagem_sucesso"];
    unset($_SESSION["mensagem_sucesso"]);
}

// LISTAR RESPONSÁVEIS
$sqlResponsaveis = "
    SELECT id_professor AS responsavel_id, nome, 'professor' AS responsavel_tipo
    FROM professor

    UNION ALL

    SELECT id_coordenador AS responsavel_id, nome, 'coordenador' AS responsavel_tipo
    FROM coordenador

    UNION ALL

    SELECT id_administrador AS responsavel_id, nome, 'administrador' AS responsavel_tipo
    FROM administrador

    ORDER BY nome
";

$responsaveis = mysqli_query($conexao, $sqlResponsaveis);

// LISTAR LABORATÓRIOS
$sqlAmbientes = "SELECT id_ambientes, nome FROM ambientes ORDER BY nome";
$ambientes = mysqli_query($conexao, $sqlAmbientes);

// LISTAR FIXAÇÕES
$sqlFixacoes = "
    SELECT
        h.*,
        CASE
            WHEN h.responsavel_tipo = 'professor' THEN p.nome
            WHEN h.responsavel_tipo = 'coordenador' THEN c.nome
            WHEN h.responsavel_tipo = 'administrador' THEN ad.nome
        END AS responsavel_nome,
        a.nome AS ambiente_nome
    FROM horarios_fixos h
    LEFT JOIN professor p
        ON h.responsavel_id = p.id_professor
        AND h.responsavel_tipo = 'professor'
    LEFT JOIN coordenador c
        ON h.responsavel_id = c.id_coordenador
        AND h.responsavel_tipo = 'coordenador'
    LEFT JOIN administrador ad
        ON h.responsavel_id = ad.id_administrador
        AND h.responsavel_tipo = 'administrador'
    INNER JOIN ambientes a
        ON h.id_ambientes = a.id_ambientes
    ORDER BY h.dia_semana, h.horario_inicio
";

$fixacoes = mysqli_query($conexao, $sqlFixacoes);

// DADOS PARA EDIÇÃO
$responsavelAtual = "";
$horariosMarcados = [];

if ($editar) {
    $responsavelAtual =
        ($editar["responsavel_tipo"] ?? "") . "|" .
        ($editar["responsavel_id"] ?? "");

    // VERIFICAR SE OS CAMPOS DE HORÁRIO EXISTEM ANTES DE UTILIZÁ-LOS
    $horarioInicio = $editar["horario_inicio"] ?? "";
    $horarioFim = $editar["horario_fim"] ?? "";

    if ($horarioInicio !== "" && $horarioFim !== "") {
        $horarioInicio = substr((string)$horarioInicio, 0, 5);
        $horarioFim = substr((string)$horarioFim, 0, 5);

        foreach ($horariosDisponiveis as $indice => $horario) {
            if (
                $horario["inicio"] >= $horarioInicio &&
                $horario["fim"] <= $horarioFim
            ) {
                $horariosMarcados[(int)$editar["dia_semana"]][] = $indice;
            }
        }
    }
}

// MANTER SELEÇÕES CASO OCORRA ERRO NO CADASTRO
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["salvar"]) &&
    isset($selecionadosValidos)
) {
    $horariosMarcados = $selecionadosValidos;
    $responsavelAtual = $responsavelTipo . "|" . $responsavelId;
}

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

} elseif (in_array($usuario_tipo, ['professor', 'coordenador', 'administrador'], true)) {

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fixar laboratórios</title>

    <link rel="stylesheet" href="../css/agendamento.css">
    <link rel="stylesheet" href="../css/fixacao_agendamento.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/responsivo.css">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#72c67c">
    <link rel="apple-touch-icon" href="../icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Agendamento">
</head>

<body>

<header>
    <div class="logo">
        <img src="../logo.png" alt="Logo Etec">
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

    <!-- BOTÕES DA BARRA LATERAL -->
    <div class="acoes-header">

        <button
            type="button"
            class="botao-header botao-menu" style="position: relative;"
            aria-label="Abrir menu lateral"
            onclick="abrirBarraLateral()">

            <i class="fa-solid fa-bars"></i>

            <span
                id="indicadorNotificacoes"
                aria-label="Há notificações não lidas"
                style="display: none; position: absolute; top: -3px; right: -3px; width: 11px; height: 11px; background: #dc2626; border: 2px solid #fff; border-radius: 50%; pointer-events: none;">
            </span>

        </button>
</div>
</header>

<section class="titulo">
    <h1>Fixar laboratórios</h1>
</section>
<br>

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

            <a href="../gerenciamento_representante/gerenciar_representantes.php" class="opcao-lateral">
                <i class="fa-solid fa-user-plus"></i>
                <span>Cadastro representante</span>
            </a>

            <br>

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

                        <a
                            class="notificacao notificacao-link"
                            data-notificacao-id="<?= htmlspecialchars((string) ($notificacao['id_agendamentos'] ?? '') . '-' . (string) ($notificacao['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            href="solicitacoes_gestao.php">

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

            <?php elseif (in_array($usuario_tipo, ['professor', 'coordenador', 'administrador'], true)): ?>

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

                        <a
                            class="notificacao notificacao-link"
                            data-notificacao-id="<?= htmlspecialchars((string) ($notificacao['id_agendamentos'] ?? '') . '-' . (string) ($notificacao['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            href="meus-agendamentos.php">

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

        <form action="agendamento.php" method="POST">

            <input type="hidden" name="logout" value="1">

            <button type="submit">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sair</span>
            </button>

        </form>

    </div>

</aside>

<main>

    <?php if ($erro !== ""): ?>
        <div class="mensagem erro">
            <?= e($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== ""): ?>
        <div class="mensagem sucesso">
            <?= e($sucesso) ?>
        </div>
    <?php endif; ?>

    <section class="formulario">

        <form method="POST" action="fixacao_agendamento.php" id="formFixacao">

            <input
                type="hidden"
                name="id_horario_fixo"
                value="<?= (int)($editar["id_horario_fixo"] ?? 0) ?>"
            >

            <div class="campos-superiores">

                <div class="campo">
                    <label for="responsavel">Responsável:</label>

                    <select name="responsavel" id="responsavel" required>
                        <option value="">Selecione o responsável</option>

                        <?php
                        $tipoAtual = "";

                        $nomesTipos = [
                            "professor" => "Professores",
                            "coordenador" => "Coordenadores",
                            "administrador" => "Administradores"
                        ];

                        while ($r = mysqli_fetch_assoc($responsaveis)):
                            if ($tipoAtual !== $r["responsavel_tipo"]):
                                if ($tipoAtual !== "") {
                                    echo "</optgroup>";
                                }

                                $tipoAtual = $r["responsavel_tipo"];
                        ?>
                                <optgroup label="<?= e($nomesTipos[$tipoAtual]) ?>">
                        <?php endif; ?>

                            <?php
                            $valorResponsavel = $r["responsavel_tipo"] . "|" . $r["responsavel_id"];
                            ?>

                            <option
                                value="<?= e($valorResponsavel) ?>"
                                <?= $responsavelAtual === $valorResponsavel ? "selected" : "" ?>
                            >
                                <?= e($r["nome"]) ?>
                            </option>

                        <?php endwhile; ?>

                        <?php if ($tipoAtual !== ""): ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="id_ambientes">Laboratório:</label>

                    <select name="id_ambientes" id="id_ambientes" required>
                        <option value="">Selecione o laboratório</option>

                        <?php while ($a = mysqli_fetch_assoc($ambientes)): ?>
                            <option
                                value="<?= (int)$a["id_ambientes"] ?>"
                                <?= (string)($editar["id_ambientes"] ?? "") === (string)$a["id_ambientes"] ? "selected" : "" ?>
                            >
                                <?= e($a["nome"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

            </div>

            <div class="grade-container">

                <table class="grade-horarios">

                    <thead>
                        <tr>
                            <th class="coluna-dia">Dia da semana</th>

                            <?php foreach ($horariosDisponiveis as $horario): ?>
                                <th>
                                    <?= e($horario["nome"]) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($diasSemana as $numeroDia => $nomeDia): ?>

                            <tr>
                                <th class="nome-dia">
                                    <?= e($nomeDia) ?>
                                </th>

                                <?php foreach ($horariosDisponiveis as $indice => $horario): ?>

                                    <?php
                                    $marcado = in_array(
                                        $indice,
                                        $horariosMarcados[$numeroDia] ?? [],
                                        true
                                    );
                                    ?>

                                    <td>
                                        <label class="celula-horario">

                                            <input
                                                type="checkbox"
                                                name="horarios[<?= $numeroDia ?>][]"
                                                value="<?= $indice ?>"
                                                <?= $marcado ? "checked" : "" ?>
                                            >

                                            <span></span>

                                        </label>
                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="legenda">

                <span class="legenda-item">
                    <span class="quadrado"></span>
                    Disponível
                </span>

                <span class="legenda-item">
                    <span class="quadrado selecionado"></span>
                    Selecionado
                </span>

            </div>

            <div class="botoes-formulario">

                <button type="submit" name="salvar" class="botao-salvar">
                    <?= $editar ? "Salvar alterações" : "Fixar laboratório" ?>
                </button>

                <?php if ($editar): ?>
                    <a href="fixacao_agendamento.php" class="botao-cancelar">
                        Cancelar edição
                    </a>
                <?php endif; ?>

            </div>

        </form>

    </section>

    <section class="lista-fixacoes">
        <br><br>

        <h2>Laboratórios fixos:</h2>

        <!-- FILTROS POR DIA DA SEMANA -->
        <div class="filtros-dias" id="filtrosDias">

            <button
                type="button"
                class="filtro-dia ativo"
                data-filtro-dia="todos"
            >
                Todos os dias
            </button>

            <?php foreach ($diasSemana as $numeroDia => $nomeDia): ?>

                <button
                    type="button"
                    class="filtro-dia"
                    data-filtro-dia="<?= $numeroDia ?>"
                >
                    <?= e($nomeDia) ?>
                </button>

            <?php endforeach; ?>

        </div>

        <?php if (mysqli_num_rows($fixacoes) === 0): ?>

            <p class="sem-registros">
                Nenhum laboratório foi fixado.
            </p>

        <?php else: ?>

            <div class="tabela-container">

                <table class="tabela-fixacoes">

                    <thead>
                        <tr>
                            <th>Responsável</th>
                            <th>Tipo</th>
                            <th>Laboratório</th>
                            <th>Dia</th>
                            <th>Horário</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody id="corpoFixacoes">

                        <?php
                        $nomesExibicao = [
                            "professor" => "Professor",
                            "coordenador" => "Coordenador",
                            "administrador" => "Administrador"
                        ];

                        while ($h = mysqli_fetch_assoc($fixacoes)):
                            $horarioInicioLista = $h["horario_inicio"] ?? "";
                            $horarioFimLista = $h["horario_fim"] ?? "";
                        ?>

                            <tr data-dia-fixacao="<?= (int)$h["dia_semana"] ?>">

                                <td>
                                    <?= e($h["responsavel_nome"] ?? "Responsável não encontrado") ?>
                                </td>

                                <td>
                                    <?= e($nomesExibicao[$h["responsavel_tipo"]] ?? $h["responsavel_tipo"]) ?>
                                </td>

                                <td>
                                    <?= e($h["ambiente_nome"] ?? "") ?>
                                </td>

                                <td>
                                    <?= e($diasSemana[(int)$h["dia_semana"]] ?? "Não informado") ?>
                                </td>

                                <td>
                                    <?= e($horarioInicioLista !== "" ? substr((string)$horarioInicioLista, 0, 5) : "--:--") ?>
                                    às
                                    <?= e($horarioFimLista !== "" ? substr((string)$horarioFimLista, 0, 5) : "--:--") ?>
                                </td>

                                <td class="acoes">

                                    <a
                                        href="fixacao_agendamento.php?editar=<?= (int)$h["id_horario_fixo"] ?>"
                                        class="botao-editar"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="fixacao_agendamento.php"
                                        onsubmit="return confirm('Tem certeza de que deseja excluir esta fixação?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id_horario_fixo"
                                            value="<?= (int)$h["id_horario_fixo"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="excluir"
                                            class="botao-excluir"
                                        >
                                            Excluir
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

            <p class="sem-registros" id="semFixacoesDia" hidden>
                Nenhuma fixação cadastrada para este dia.
            </p>

        <?php endif; ?>

    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const botoesFiltro = document.querySelectorAll("[data-filtro-dia]");
    const linhasFixacoes = document.querySelectorAll("[data-dia-fixacao]");
    const avisoSemFixacoes = document.getElementById("semFixacoesDia");

    botoesFiltro.forEach(function(botao) {

        botao.addEventListener("click", function() {

            const diaSelecionado = this.dataset.filtroDia;
            let quantidadeVisivel = 0;

            linhasFixacoes.forEach(function(linha) {

                const mostrar =
                    diaSelecionado === "todos" ||
                    linha.dataset.diaFixacao === diaSelecionado;

                linha.hidden = !mostrar;

                if (mostrar) {
                    quantidadeVisivel++;
                }

            });

            // DESTACAR O BOTÃO SELECIONADO
            botoesFiltro.forEach(function(outroBotao) {
                outroBotao.classList.remove("ativo");
            });

            this.classList.add("ativo");

            // MOSTRAR AVISO QUANDO NÃO EXISTIR FIXAÇÃO NO DIA
            if (avisoSemFixacoes) {
                avisoSemFixacoes.hidden = quantidadeVisivel !== 0;
            }

        });

    });

});
</script>

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


// ==========================================================
// SELEÇÃO INDIVIDUAL E POR ARRASTE
// ==========================================================

document.addEventListener("DOMContentLoaded", function () {

    const grade = document.querySelector(".grade-horarios");

    if (!grade) return;

    let arrastando = false;
    let estadoSelecao = false;
    let celulaInicial = null;

    function selecionarCelula(celula) {

        const checkbox = celula.querySelector('input[type="checkbox"]');

        if (!checkbox) return;

        checkbox.checked = estadoSelecao;
    }

    // INICIAR O ARRASTE
    grade.addEventListener("pointerdown", function (event) {

        const celula = event.target.closest(".celula-horario");

        if (!celula || event.button !== 0) return;

        const checkbox = celula.querySelector('input[type="checkbox"]');

        if (!checkbox) return;

        arrastando = true;
        celulaInicial = celula;

        // Define se vai marcar ou desmarcar
        estadoSelecao = !checkbox.checked;

    });

    // PASSAR PELOS OUTROS HORÁRIOS
    grade.addEventListener("pointerover", function (event) {

        if (!arrastando) return;

        const celula = event.target.closest(".celula-horario");

        if (!celula || !grade.contains(celula)) return;

        selecionarCelula(celula);

    });

    // FINALIZAR O ARRASTE
    document.addEventListener("pointerup", function () {

        arrastando = false;
        celulaInicial = null;

    });

    document.addEventListener("pointercancel", function () {

        arrastando = false;
        celulaInicial = null;

    });

    window.addEventListener("blur", function () {

        arrastando = false;
        celulaInicial = null;

    });

});

    </script>

<script src="../js/menu-responsivo.js"></script>
<script src="../js/pwa.js"></script>
</body>
</html>
