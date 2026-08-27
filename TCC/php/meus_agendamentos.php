<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$tiposPermitidos = [
    'professor',
    'administrador',
    'coordenador',
    'gestao'
];

if (!in_array($_SESSION['usuario_tipo'], $tiposPermitidos)) {
    header("Location: login.php");
    exit();
}

include("conexao.php");

$usuario_id = $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];

$agendamentos = [];

/*
|--------------------------------------------------------------------------
| BUSCA OS AGENDAMENTOS DO USUÁRIO
|--------------------------------------------------------------------------
*/

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

    WHERE a.solicitante_id = ?
    AND a.solicitante_tipo = ?

    ORDER BY
        a.data_agendamento DESC,
        a.id_agendamentos DESC
";

$stmt = mysqli_prepare($conexao, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $usuario_id,
        $usuario_tipo
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    while ($linha = mysqli_fetch_assoc($resultado)) {
        $agendamentos[] = $linha;
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

<title>Meus Agendamentos</title>

<link
    rel="stylesheet"
    href="../css/meus_agendamentos.css">

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<header>

    <div class="logo">
        <img src="../logo.png">
    </div>

    <nav>

        <a href="">Home</a>

        <a href="#">Cursos</a>

        <a href="#">A Etec</a>

        <a href="#">Equipe Etec</a>

        <a href="../selecionar_lab.html">
            Agendamento
        </a>

        <a href="meus-agendamentos.php"
           class="ativo">
            Meus agendamentos
        </a>

        <a href="#">Notícias</a>

        <a href="#">Empregos & Estágios</a>

        <a href="#">Parceiros</a>

        <a href="#">TCC</a>

    </nav>

</header>


<main class="container">

    <div class="titulo">

        <h1>
            Meus Agendamentos
        </h1>
    </div>
    <br><br>


    <?php if (empty($agendamentos)): ?>

        <div class="sem-agendamentos">

            <i class="fa-regular fa-calendar-xmark"></i>

            <h2>
                Nenhum agendamento encontrado
            </h2>

            <p>
                Você ainda não possui solicitações de agendamento.
            </p>

            <a href="../selecionar_lab.html">
                Fazer um agendamento
            </a>

        </div>


    <?php else: ?>


        <div class="lista-agendamentos">

            <?php foreach ($agendamentos as $agendamento): ?>

                <?php

                /*
                 * Formata a data
                 */

                $data = date(
                    'd/m/Y',
                    strtotime($agendamento['data_agendamento'])
                );


                /*
                 * Monta o nome do ambiente
                 */

                if ($agendamento['tipo_ambiente'] === 'Auditório') {

                    $ambiente = $agendamento['nome_ambiente'];

                } else {

                    $ambiente =
                        $agendamento['nome_ambiente']
                        . " - "
                        . $agendamento['tipo_ambiente'];
                }


                /*
                 * Status
                 */

                $status = $agendamento['status'];

                $classeStatus = '';

                if ($status === 'Aprovada') {
                    $classeStatus = 'aprovada';
                } elseif ($status === 'Recusada') {
                    $classeStatus = 'recusada';
                } else {
                    $classeStatus = 'pendente';
                }

                ?>

                <div class="card-agendamento">

                    <div class="informacoes">

                        <h2>
                            <?= htmlspecialchars($ambiente) ?>
                            -
                            Solicitação
                            <?= htmlspecialchars($status) ?>
                        </h2>

                        <p class="data-horario">

                            <i class="fa-regular fa-clock"></i>

                            <?= htmlspecialchars($agendamento['horario']) ?>

                            -

                            <?= htmlspecialchars($data) ?>

                        </p>

                        <p class="descricao">

                            <strong>Descrição:</strong>

                            <?= htmlspecialchars($agendamento['descr']) ?>

                        </p>

                    </div>


                    <div class="status <?= $classeStatus ?>">

                        <?php if ($status === 'Aprovada'): ?>

                            <i class="fa-solid fa-circle-check"></i>

                        <?php elseif ($status === 'Recusada'): ?>

                            <i class="fa-solid fa-circle-xmark"></i>

                        <?php else: ?>

                            <i class="fa-solid fa-clock"></i>

                        <?php endif; ?>

                        <?= htmlspecialchars($status) ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>

</html>
