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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Solicitações</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link
        rel="stylesheet"
        href="../css/solicitacoes_gestao.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

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

            <li>

                <a
                    href="selecionar_lab.html"class="has-submenu"> Agendamento
                </a>

                <ul class="submenu">

                    <li>

                        <a href="meus-agendamentos.php">
                            Meus agendamentos
                        </a>

                    </li>

                </ul>

            </li>

        </li>

        <a href="#" class="has-submenu">
            Notícias
        </a>

        <a href="">
            Empregos & Estágios
        </a>

        <a href="">
            Parceiros
        </a>

        <a href="">
            TCC
        </a>

    </nav>

</header>


    <div class="titulo">

        <h1>
            Solicitações
        </h1>

    </div>


    <br><br>

    <main class="container">

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


                        <!--
                        --------------------------------------------------
                        FORMULÁRIO DE APROVAÇÃO
                        --------------------------------------------------
                        -->

                        <form
                            method="POST"
                            onsubmit="abrirConfirmacao(this, 'aprovar'); return false;"
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


                        <!--
                        --------------------------------------------------
                        FORMULÁRIO DE RECUSA
                        --------------------------------------------------
                        -->

                        <form
                            method="POST"
                            onsubmit="abrirConfirmacao(this, 'recusar'); return false;"
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


<!--
|--------------------------------------------------------------------------
| CAIXINHA DE CONFIRMAÇÃO
|--------------------------------------------------------------------------
-->

<div
    id="modalConfirmacao"
    class="modal-confirmacao"
>

    <div class="caixa-confirmacao">


        <button
            type="button"
            class="fechar-modal"
            onclick="fecharConfirmacao()"
        >

            &times;

        </button>


        <h2 id="tituloConfirmacao">
            Aprovar solicitação?
        </h2>


        <p id="textoConfirmacao">
            Deseja realmente aprovar esta solicitação?
        </p>


        <div class="botoes-confirmacao">

          <button
                type="button"
                id="btnConfirmar"
                class="btn-confirmar"
                onclick="confirmarAcao()"
            >

                Aprovar

            </button>


            <button
                type="button"
                class="btn-cancelar"
                onclick="fecharConfirmacao()"
            >

                Cancelar

            </button>


        </div>


    </div>

</div>


<script>

let formularioSelecionado = null;


/*
|--------------------------------------------------------------------------
| ABRIR CAIXINHA
|--------------------------------------------------------------------------
*/

function abrirConfirmacao(formulario, acao) {

    formularioSelecionado = formulario;

    const modal =
        document.getElementById("modalConfirmacao");

    const titulo =
        document.getElementById("tituloConfirmacao");

    const texto =
        document.getElementById("textoConfirmacao");

    const botao =
        document.getElementById("btnConfirmar");


    if (acao === "aprovar") {

        titulo.innerText =
            "Aprovar solicitação?";

        texto.innerText =
            "Deseja realmente aprovar esta solicitação?";

        botao.innerText =
            "Aprovar";

        botao.className =
            "btn-confirmar";

    } else {

        titulo.innerText =
            "Recusar solicitação?";

        texto.innerText =
            "Deseja realmente recusar esta solicitação?";

        botao.innerText =
            "Recusar";

        botao.className =
            "btn-confirmar btn-confirmar-recusar";
    }


    modal.style.display = "flex";
}


/*
|--------------------------------------------------------------------------
| FECHAR CAIXINHA
|--------------------------------------------------------------------------
*/

function fecharConfirmacao() {

    document.getElementById(
        "modalConfirmacao"
    ).style.display = "none";

    formularioSelecionado = null;
}


/*
|--------------------------------------------------------------------------
| CONFIRMAR AÇÃO
|--------------------------------------------------------------------------
*/

function confirmarAcao() {

    if (formularioSelecionado) {

        formularioSelecionado.submit();

    }

}


/*
|--------------------------------------------------------------------------
| FECHAR AO CLICAR FORA DA CAIXINHA
|--------------------------------------------------------------------------
*/

document.getElementById(
    "modalConfirmacao"
).addEventListener(
    "click",
    function(event) {

        if (event.target === this) {

            fecharConfirmacao();

        }

    }
);

</script>

<br><br><br><br>
<section>
      <div class="container">
        <div class="row">
          <div class="col-sm-2 offset-sm-5 text-center">
            <button class="btn btn-warning text-white btn-block" onclick="voltar()">Voltar</button>
            <script>
              function voltar(){
                history.back();
              }
            </script>
          </div>
        </div>
      </div>
    </section><!-- End About Section -->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>