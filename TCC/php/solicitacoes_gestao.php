<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| SOMENTE GESTÃO
|--------------------------------------------------------------------------
*/

if ($_SESSION['usuario_tipo'] !== 'gestao') {
    header("Location: login.php");
    exit();
}

include("conexao.php");

/*
|--------------------------------------------------------------------------
| APROVAR / RECUSAR SOLICITAÇÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_POST['acao']) &&
        isset($_POST['id_agendamentos'])
    ) {

        $id_agendamento = intval($_POST['id_agendamentos']);
        $acao = $_POST['acao'];

        if ($acao === 'aprovar') {

            $novo_status = 'Aprovada';

        } elseif ($acao === 'recusar') {

            $novo_status = 'Recusada';

        } else {

            $novo_status = null;
        }

        if ($novo_status !== null) {

            $sqlAtualizar = "
                UPDATE agendamentos
                SET status = ?
                WHERE id_agendamentos = ?
            ";

            $stmtAtualizar = mysqli_prepare(
                $conexao,
                $sqlAtualizar
            );

            if ($stmtAtualizar) {

                mysqli_stmt_bind_param(
                    $stmtAtualizar,
                    "si",
                    $novo_status,
                    $id_agendamento
                );

                mysqli_stmt_execute($stmtAtualizar);

                mysqli_stmt_close($stmtAtualizar);
            }
        }
    }

    /*
     * Evita reenviar o formulário ao atualizar a página
     */
    header("Location: solicitacoes_gestao.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| BUSCAR SOLICITAÇÕES PENDENTES
|--------------------------------------------------------------------------
*/

$solicitacoes = [];

$sql = "
    SELECT
        a.id_agendamentos,
        a.nome_prof,
        a.descr,
        a.data_agendamento,
        a.horario,
        a.status,
        a.id_ambientes,

        amb.nome AS nome_ambiente,
        amb.tipo AS tipo_ambiente

    FROM agendamentos a

    INNER JOIN ambientes amb
        ON a.id_ambientes = amb.id_ambientes

    WHERE a.status = 'Pendente'

    ORDER BY
        a.data_agendamento ASC,
        a.horario ASC,
        a.id_agendamentos ASC
";

$stmt = mysqli_prepare($conexao, $sql);

if ($stmt) {

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    while ($linha = mysqli_fetch_assoc($resultado)) {

        $solicitacoes[] = $linha;
    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Solicitações</title>

    <link
        rel="stylesheet"href="../css/solicitacoes_gestao.css">

    <link
        rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>


<header>

     <div class="logo">
        <img src="../logo.png">
    </div>

    <nav>
        <a href="">Home</a>
        <a href="#" class="has-submenu">
            Cursos
        </a>
        <a href="#" class="has-submenu">
            A Etec
        </a>
        <a href="#" class="has-submenu">
            Equipe Etec
        </a>
        <li>
            <a
                href="../selecionar_lab.html"
                class="has-submenu">
                Agendamento
            </a>
        </li>
        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href=""> TCC</a>

    </nav>
</header>



<main class="container">


    <div class="titulo">

        <h1>
            Solicitações
        </h1>

    </div>


    <div class="lista-solicitacoes">


        <?php if (empty($solicitacoes)): ?>

            <div class="sem-solicitacoes">

                <i class="fa-regular fa-calendar-check"></i>

                <h2>
                    Nenhuma solicitação pendente
                </h2>

                <p>
                    Não existem solicitações aguardando aprovação.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($solicitacoes as $solicitacao): ?>


                <?php

                /*
                 * Formata a data
                 */

                $data = date(
                    'd/m',
                    strtotime(
                        $solicitacao['data_agendamento']
                    )
                );


                /*
                 * Monta o nome do ambiente
                 */

                if (
                    $solicitacao['tipo_ambiente']
                    === 'Auditório'
                ) {

                    $ambiente =
                        $solicitacao['nome_ambiente'];

                } else {

                    $ambiente =
                        $solicitacao['nome_ambiente']
                        . ' - '
                        . $solicitacao['tipo_ambiente'];
                }

                ?>


                <div class="card-solicitacao">


                    <div class="informacoes">


                        <h2>

                            <?= htmlspecialchars($ambiente) ?>

                        </h2>


                        <p>

                            <strong>
                                *Nome do Professor(a):
                            </strong>

                            <?= htmlspecialchars(
                                $solicitacao['nome_prof']
                            ) ?>

                            :

                            <?= htmlspecialchars(
                                $solicitacao['horario']
                            ) ?>

                            -

                            <?= htmlspecialchars($data) ?>

                        </p>


                        <?php if (!empty($solicitacao['descr'])): ?>

                            <p class="descricao">

                                <?= htmlspecialchars(
                                    $solicitacao['descr']
                                ) ?>

                            </p>

                        <?php endif; ?>


                    </div>


                    <div class="botoes">


                        <form
                            method="POST"
                            onsubmit="return confirmarAprovacao();"
                        >

                            <input
                                type="hidden"
                                name="id_agendamentos"
                                value="<?= $solicitacao['id_agendamentos'] ?>"
                            >

                            <input
                                type="hidden"
                                name="acao"
                                value="aprovar"
                            >

                            <button
                                type="submit"
                                class="btn-aprovar"
                            >

                                Aprovar

                            </button>

                        </form>


                        <form
                            method="POST"
                            onsubmit="return confirmarRecusa();"
                        >

                            <input
                                type="hidden"
                                name="id_agendamentos"
                                value="<?= $solicitacao['id_agendamentos'] ?>"
                            >

                            <input
                                type="hidden"
                                name="acao"
                                value="recusar"
                            >

                            <button
                                type="submit"
                                class="btn-recusar"
                            >

                                Recusar

                            </button>

                        </form>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</main>


<script>

function confirmarAprovacao() {

    return confirm(
        "Deseja aprovar esta solicitação?"
    );
}


function confirmarRecusa() {

    return confirm(
        "Deseja recusar esta solicitação?"
    );
}

</script>


</body>

</html>