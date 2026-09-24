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
// FILTRO POR TURMA
// =====================================================

$turmaFiltro = "";

if (isset($_GET["turma"])) {
    $turmaFiltro = trim($_GET["turma"]);
}

// =====================================================
// BUSCAR TURMAS PARA O FILTRO
// =====================================================

$sqlTurmas = "
    SELECT id_turma, serie, curso
    FROM turma
    ORDER BY curso, serie
";

$resultTurmas = $conn->query($sqlTurmas);

if (!$resultTurmas) {
    die("Erro ao buscar as turmas: " . $conn->error);
}

// =====================================================
// REPRESENTANTES
// =====================================================

$sql = "
    SELECT
        r.id_representante,
        r.nome,
        r.email,
        r.status,
        r.id_turma,
        t.serie,
        t.curso
    FROM representante r
    LEFT JOIN turma t
        ON r.id_turma = t.id_turma
    WHERE (r.nome LIKE ? OR r.email LIKE ?)
";

if ($turmaFiltro !== "") {
    $sql .= " AND r.id_turma = ?";
}

$sql .= " ORDER BY t.curso, t.serie, r.nome";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a pesquisa: " . $conn->error);
}

if ($turmaFiltro !== "") {
    $idTurmaFiltro = intval($turmaFiltro);
    $stmt->bind_param("ssi", $busca, $busca, $idTurmaFiltro);
} else {
    $stmt->bind_param("ss", $busca, $busca);
}

$stmt->execute();

$resultRepresentante = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Representantes</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

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

<section class="titulo">

    <h1>Gerenciamento de Representantes</h1>

</section>
<br>

    <p class="subtitulo" align="center">
        Gerencie os representantes cadastrados no sistema.
    </p>

    <!-- =====================================================
         CADASTRAR REPRESENTANTE
    ====================================================== -->

    <div class="novo-usuario">

        <a href="cadastrar_representante.php">
            + cadastrar representante
        </a>

    </div>

    <!-- =====================================================
         PESQUISA + FILTRO POR TURMA
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

        <!-- FILTRO POR TURMA -->

        <select name="turma">

            <option value="">
                Todas as turmas
            </option>

            <?php while ($turma = $resultTurmas->fetch_assoc()): ?>

                <option
                    value="<?php echo $turma["id_turma"]; ?>"
                    <?php
                    echo ($turmaFiltro == $turma["id_turma"])
                        ? "selected"
                        : "";
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $turma["serie"] . " - " . $turma["curso"]
                    );
                    ?>

                </option>

            <?php endwhile; ?>

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
                        Turma
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

                <?php if ($resultRepresentante->num_rows > 0): ?>

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

                                if (
                                    $usuario["serie"] !== null &&
                                    $usuario["curso"] !== null
                                ) {

                                    echo htmlspecialchars(
                                        $usuario["serie"] .
                                        " - " .
                                        $usuario["curso"]
                                    );

                                } else {

                                    echo "Sem turma";

                                }

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

                                <!-- EDITAR -->

                                <a
                                    href="editar_representante.php?tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                    class="editar">
                                    Editar
                                </a>

                                <!-- BLOQUEAR / ATIVAR -->

                                <?php
                                if ($usuario["status"] == "Ativo"):
                                ?>

                                    <a
                                        href="acoes_representante.php?acao=bloquear&id=<?php echo $usuario["id_representante"]; ?>"
                                        class="bloquear"
                                    >
                                        Bloquear
                                    </a>

                                <?php else: ?>

                                    <a
                                        href="acoes_representante.php?acao=ativar&id=<?php echo $usuario["id_representante"]; ?>"
                                        class="ativar"
                                    >
                                        Ativar
                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" style="text-align: center;">
                            Nenhum representante encontrado.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

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


<?php

$stmt->close();
$conn->close();

?>
