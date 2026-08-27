<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "MODELO_TCC";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8");


// =====================================================
// PESQUISA
// =====================================================

$pesquisa = "";

if (isset($_GET["pesquisa"])) {
    $pesquisa = trim($_GET["pesquisa"]);
}

$busca = "%" . $pesquisa . "%";


// =====================================================
// FILTRO POR TIPO DE USUÁRIO
// =====================================================

$tipoFiltro = "";

if (isset($_GET["tipo"])) {
    $tipoFiltro = $_GET["tipo"];
}


// Só permite os tipos existentes no filtro
$tiposPermitidos = [
    "",
    "administrador",
    "coordenador",
    "professor",
    "representante"
];

if (!in_array($tipoFiltro, $tiposPermitidos, true)) {
    $tipoFiltro = "";
}


// =====================================================
// ADMINISTRADORES
// =====================================================

$sql = "SELECT id_administrador, nome, email, status
        FROM administrador
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultAdministrador = $stmt->get_result();


// =====================================================
// COORDENADORES
// =====================================================

$sql = "SELECT id_coordenador, nome, email, curso, status
        FROM coordenador
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultCoordenador = $stmt->get_result();


// =====================================================
// PROFESSORES
// =====================================================

$sql = "SELECT id_professor, nome, email, status
        FROM professor
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultProfessor = $stmt->get_result();


// =====================================================
// REPRESENTANTES
// =====================================================

$sql = "SELECT
            r.id_representante,
            r.nome,
            r.email,
            r.status,
            t.serie,
            t.curso

        FROM representante r

        LEFT JOIN turma t
        ON r.id_turma = t.id_turma

        WHERE r.nome LIKE ?
        OR r.email LIKE ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultRepresentante = $stmt->get_result();


// =====================================================
// GESTÃO
// =====================================================

$sql = "SELECT id_gestao, nome, email, status
        FROM gestao
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultGestao = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Usuários</title>

    <link rel="stylesheet"
          href="../css/gerenciar_usuarios.css">

</head>


<body>


<header class="menu">

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
            <ul class="submenu">
                <li>
                    <a href="meus-agendamentos.php">
                        Meus agendamentos
                    </a>
                </li>
            </ul>
        </li>
        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href=""> TCC</a>

    </nav>
</header>


<main class="container">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <h1>
        Gerenciamento de Usuários
    </h1>


    <p class="subtitulo" align="center">
        Gerencie os usuários cadastrados no sistema.
    </p>


    <!-- =====================================================
         CADASTRAR USUÁRIO
    ====================================================== -->

    <div class="novo-usuario">

        <a href="cadastrar_usuario.php">
            + Cadastrar usuário
        </a>

    </div>


    <!-- =====================================================
         PESQUISA + FILTRO
    ====================================================== -->

    <form
        method="GET"
        class="pesquisa"
    >

        <!-- PESQUISA POR NOME OU E-MAIL -->

        <input
            type="text"
            name="pesquisa"
            placeholder="Pesquisar por nome ou e-mail..."
            value="<?php echo htmlspecialchars($pesquisa); ?>"
        >


        <!-- FILTRO POR TIPO -->

        <select name="tipo">

            <option value="">
                Todos os tipos
            </option>


            <option
                value="administrador"
                <?php
                echo ($tipoFiltro === "administrador")
                    ? "selected"
                    : "";
                ?>
            >
                Administrador
            </option>


            <option
                value="coordenador"
                <?php
                echo ($tipoFiltro === "coordenador")
                    ? "selected"
                    : "";
                ?>
            >
                Coordenador
            </option>


            <option
                value="professor"
                <?php
                echo ($tipoFiltro === "professor")
                    ? "selected"
                    : "";
                ?>
            >
                Professor
            </option>


            <option
                value="representante"
                <?php
                echo ($tipoFiltro === "representante")
                    ? "selected"
                    : "";
                ?>
            >
                Representante
            </option>

        </select>


        <!-- BOTÃO -->

        <button type="submit">
            Pesquisar
        </button>

    </form>


    <!-- =====================================================
         TABELA
    ====================================================== -->

    <div class="tabela-container">

        <table>

            <thead>

                <tr>

                    <th>
                        Nome
                    </th>

                    <th>
                        E-mail
                    </th>

                    <th>
                        Tipo
                    </th>

                    <th>
                        Curso/Turma
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Ações
                    </th>

                </tr>

            </thead>


            <tbody>


            <!-- =================================================
                 ADMINISTRADORES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "administrador"
            ):
            ?>

                <?php while ($usuario = $resultAdministrador->fetch_assoc()): ?>

                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["nome"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["email"]
                            );
                            ?>

                        </td>


                        <td>
                            Administrador
                        </td>


                        <td>
                            —
                        </td>


                        <td>

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <span class="status ativo">
                                    Ativo
                                </span>

                            <?php else: ?>

                                <span class="status bloqueado">
                                    Bloqueado
                                </span>

                            <?php endif; ?>

                        </td>


                        <td class="acoes">


                            <!-- EDITAR -->

                            <a
                                href="editar_usuario.php?tipo=administrador&id=<?php echo $usuario["id_administrador"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <!-- BLOQUEAR / ATIVAR -->

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=administrador&id=<?php echo $usuario["id_administrador"]; ?>"
                                    class="bloquear"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=administrador&id=<?php echo $usuario["id_administrador"]; ?>"
                                    class="ativar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 COORDENADORES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "coordenador"
            ):
            ?>

                <?php while ($usuario = $resultCoordenador->fetch_assoc()): ?>

                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["nome"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["email"]
                            );
                            ?>

                        </td>


                        <td>
                            Coordenador
                        </td>


                        <td>

                            Curso:

                            <?php
                            echo htmlspecialchars(
                                $usuario["curso"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <span class="status ativo">
                                    Ativo
                                </span>

                            <?php else: ?>

                                <span class="status bloqueado">
                                    Bloqueado
                                </span>

                            <?php endif; ?>

                        </td>


                        <td class="acoes">


                            <a
                                href="editar_usuario.php?tipo=coordenador&id=<?php echo $usuario["id_coordenador"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=coordenador&id=<?php echo $usuario["id_coordenador"]; ?>"
                                    class="bloquear"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=coordenador&id=<?php echo $usuario["id_coordenador"]; ?>"
                                    class="ativar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 PROFESSORES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "professor"
            ):
            ?>

                <?php while ($usuario = $resultProfessor->fetch_assoc()): ?>

                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["nome"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["email"]
                            );
                            ?>

                        </td>


                        <td>
                            Professor
                        </td>


                        <td>
                            —
                        </td>


                        <td>

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <span class="status ativo">
                                    Ativo
                                </span>

                            <?php else: ?>

                                <span class="status bloqueado">
                                    Bloqueado
                                </span>

                            <?php endif; ?>

                        </td>


                        <td class="acoes">


                            <a
                                href="editar_usuario.php?tipo=professor&id=<?php echo $usuario["id_professor"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=professor&id=<?php echo $usuario["id_professor"]; ?>"
                                    class="bloquear"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=professor&id=<?php echo $usuario["id_professor"]; ?>"
                                    class="ativar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 REPRESENTANTES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "representante"
            ):
            ?>

                <?php while ($usuario = $resultRepresentante->fetch_assoc()): ?>

                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["nome"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["email"]
                            );
                            ?>

                        </td>


                        <td>
                            Representante
                        </td>


                        <td>

                            <?php

                            echo htmlspecialchars(
                                $usuario["serie"]
                                . " - "
                                . $usuario["curso"]
                            );

                            ?>

                        </td>


                        <td>

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <span class="status ativo">
                                    Ativo
                                </span>

                            <?php else: ?>

                                <span class="status bloqueado">
                                    Bloqueado
                                </span>

                            <?php endif; ?>

                        </td>


                        <td class="acoes">


                            <a
                                href="editar_usuario.php?tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                    class="bloquear"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                    class="ativar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 GESTÃO
                 
                 Gestão NÃO possui opção no filtro.
                 Ela aparece somente quando "Todos os tipos"
                 estiver selecionado.
            ================================================== -->

            <?php if ($tipoFiltro === ""): ?>

                <?php while ($usuario = $resultGestao->fetch_assoc()): ?>

                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["nome"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $usuario["email"]
                            );
                            ?>

                        </td>


                        <td>
                            Gestão
                        </td>


                        <td>
                            —
                        </td>


                        <td>

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <span class="status ativo">
                                    Ativo
                                </span>

                            <?php else: ?>

                                <span class="status bloqueado">
                                    Bloqueado
                                </span>

                            <?php endif; ?>

                        </td>


                        <td class="acoes">


                            <a
                                href="editar_usuario.php?tipo=gestao&id=<?php echo $usuario["id_gestao"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=gestao&id=<?php echo $usuario["id_gestao"]; ?>"
                                    class="bloquear"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=gestao&id=<?php echo $usuario["id_gestao"]; ?>"
                                    class="ativar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            </tbody>

        </table>

    </div>


</main>


</body>

</html>


<?php

$conn->close();

?>
