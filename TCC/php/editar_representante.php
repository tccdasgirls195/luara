<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "MODELO_TCC";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// =====================================================
// VERIFICAR TIPO E ID
// =====================================================

if (
    !isset($_GET["tipo"]) ||
    !isset($_GET["id"]) ||
    $_GET["tipo"] !== "representante"
) {

    header("Location: gerenciar_representantes.php");
    exit;

}

$tipo = "representante";
$id = intval($_GET["id"]);

// =====================================================
// CONFIGURAÇÃO DO REPRESENTANTE
// =====================================================

$tabela = "representante";
$campoId = "id_representante";

// =====================================================
// VARIÁVEL DE ERRO
// =====================================================

$erro = "";

// =====================================================
// SALVAR ALTERAÇÕES
// =====================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $novaSenha = $_POST["senha"] ?? "";

    // =================================================
    // VALIDAR NOME E E-MAIL
    // =================================================

    if ($nome == "" || $email == "") {

        $erro = "Nome e e-mail são obrigatórios.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } else {

        // =================================================
        // ATUALIZAR NOME E E-MAIL
        // =================================================

        $sql = "
            UPDATE representante
            SET nome = ?, email = ?
            WHERE id_representante = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $erro =
                "Erro ao preparar a atualização: " .
                $conn->error;

        } else {

            $stmt->bind_param(
                "ssi",
                $nome,
                $email,
                $id
            );

            if (!$stmt->execute()) {

                $erro =
                    "Erro ao atualizar os dados: " .
                    $stmt->error;

            }

            $stmt->close();

        }

        // =================================================
        // ALTERAR SENHA
        // =================================================

        if ($erro == "" && !empty($novaSenha)) {

            $sql = "
                UPDATE representante
                SET senha = ?
                WHERE id_representante = ?
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                $erro =
                    "Erro ao preparar a alteração da senha: " .
                    $conn->error;

            } else {

                $stmt->bind_param(
                    "si",
                    $novaSenha,
                    $id
                );

                if (!$stmt->execute()) {

                    $erro =
                        "Erro ao alterar a senha: " .
                        $stmt->error;

                }

                $stmt->close();

            }

        }

        // =================================================
        // MENSAGEM DE SUCESSO
        // =================================================

        if ($erro == "") {

            ?>

            <!DOCTYPE html>

            <html lang="pt-br">

            <head>

                <meta charset="UTF-8">

                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0">

                <title>Alteração realizada</title>

                <link
                    rel="stylesheet"
                    href="../css/editar_usuario.css">

            </head>

            <body>

                <main class="mensagem">

                    <div class="caixa-sucesso">

                        <div class="icone-sucesso">
                            ✓
                        </div>

                        <h1>
                            Dados alterados com sucesso!!!
                        </h1>

                        <p>
                            As informações do representante foram
                            atualizadas corretamente.
                        </p>

                        <a
                            href="gerenciar_representantes.php"
                            class="voltar">

                            Voltar para gerenciamento

                        </a>

                    </div>

                </main>

            </body>

            </html>

            <?php

            $conn->close();
            exit;

        }

    }

}

// =====================================================
// BUSCAR REPRESENTANTE
// =====================================================

$sql = "
    SELECT
        r.*,
        t.serie,
        t.curso
    FROM representante r
    LEFT JOIN turma t
        ON r.id_turma = t.id_turma
    WHERE r.id_representante = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Erro ao preparar a busca do representante: " .
        $conn->error
    );

}

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$resultado = $stmt->get_result();

$usuario = $resultado->fetch_assoc();

$stmt->close();

if (!$usuario) {

    die("Representante não encontrado.");

}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Representante</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link
        rel="stylesheet"
        href="../css/editar_usuario.css">

</head>

<body>

<header class="menu">

    <div class="logo">

        <img src="../logo.png">

    </div>

    <nav>

        <a href="">
            Home
        </a>

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

        <h1>Editar Usuário</h1>

    </section>
    <br>

    <p class="subtitulo">
        Altere os dados do representante.
    </p>

    <main class="container">

    <?php if (!empty($erro)): ?>

        <div class="erro">

            <?php
            echo htmlspecialchars($erro);
            ?>

        </div>

    <?php endif; ?>

    <form
        method="POST"
        class="formulario">

        <!-- =================================================
             NOME
        ================================================== -->

        <label for="nome">
            Nome
        </label>

        <input
            type="text"
            name="nome"
            id="nome"
            value="<?php echo htmlspecialchars($usuario["nome"]); ?>"
            required
        >

        <!-- =================================================
             E-MAIL
        ================================================== -->

        <label for="email">
            E-mail
        </label>

        <input
            type="email"
            name="email"
            id="email"
            value="<?php echo htmlspecialchars($usuario["email"]); ?>"
            required
        >

        <!-- =================================================
             SENHA
        ================================================== -->

        <label for="senha">
            Nova senha
        </label>

        <input
            type="password"
            name="senha"
            id="senha"
            placeholder="Deixe vazio para manter a senha atual"
        >

        <!-- =================================================
             TIPO DE USUÁRIO
        ================================================== -->

        <label for="tipo">
            Tipo de usuário
        </label>

        <input
            type="text"
            id="tipo"
            value="Representante"
            disabled
        >

        <!-- =================================================
             TURMA
        ================================================== -->

        <label for="turma">
            Turma
        </label>

        <input
            type="text"
            id="turma"
            value="<?php
                echo htmlspecialchars(
                    ($usuario["serie"] ?? "") .
                    " - " .
                    ($usuario["curso"] ?? "")
                );
            ?>"
            disabled
        >

        <!-- =================================================
             BOTÕES
        ================================================== -->

        <div class="botoes">

            <!-- CANCELAR -->

            <button
                type="button"
                class="cancelar"
                onclick="window.location.href='gerenciar_representantes.php';">

                Cancelar

            </button>

            <!-- SALVAR -->

            <button
                type="submit"
                class="salvar">

                Salvar alterações

            </button>

        </div>

    </form>

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

$conn->close();

?>
