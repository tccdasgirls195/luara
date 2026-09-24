<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "MODELO_TCC";

$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8");


// =====================================================
// VARIÁVEIS INICIAIS
// =====================================================

$cadastroSucesso = false;
$erro = "";


// =====================================================
// CADASTRAR REPRESENTANTE
// =====================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senhaUsuario = trim($_POST["senha"]);
    $idTurma = intval($_POST["id_turma"]);


    // =================================================
    // VALIDAÇÃO
    // =================================================

    if (
        empty($nome) ||
        empty($email) ||
        empty($senhaUsuario) ||
        $idTurma <= 0
    ) {

        $erro = "Preencha todos os campos.";

    } else {


        // =================================================
        // CADASTRO DO REPRESENTANTE
        // =================================================

        $sql = "INSERT INTO representante
                (nome, email, senha, id_turma, status)
                VALUES (?, ?, ?, ?, 'Ativo')";

        $stmt = $conn->prepare($sql);


        if ($stmt) {

            $stmt->bind_param(
                "sssi",
                $nome,
                $email,
                $senhaUsuario,
                $idTurma
            );


            if ($stmt->execute()) {

                $cadastroSucesso = true;

            } else {

                $erro = "Erro ao cadastrar representante: "
                      . $stmt->error;

            }

            $stmt->close();

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }

}


// =====================================================
// BUSCAR TURMAS
// =====================================================

$turmas = $conn->query(
    "SELECT id_turma, serie, curso
     FROM turma
     ORDER BY serie, curso"
);

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cadastrar Representante</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link
        rel="stylesheet"
        href="../css/cadastrar_usuario.css"
    >

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
                class="has-submenu"
            >
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



<?php if ($cadastroSucesso): ?>


    <!-- =================================================
         MENSAGEM DE SUCESSO
    ================================================== -->

    <main class="mensagem">

        <div class="caixa-sucesso">

            <div class="icone-sucesso">
                ✓
            </div>


            <h1>
                Representante cadastrado com sucesso!!!
            </h1>


            <p>
                O novo representante foi adicionado ao sistema.
            </p>


            <a
                href="gerenciar_representantes.php"
                class="voltar"
            >

                Voltar para gerenciamento

            </a>

        </div>

    </main>


<?php else: ?>


    <!-- =================================================
         FORMULÁRIO
    ================================================== -->


    <section class="titulo">

        <h1>Cadastro de Representantes</h1>

    </section>
    <br>
        <p class="subtitulo">

            Preencha os dados para cadastrar um novo representante.

        </p>

    <main class="container">


        <?php if (!empty($erro)): ?>

            <div class="erro">

                <?php echo htmlspecialchars($erro); ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            class="formulario"
        >


            <!-- =================================================
                 NOME
            ================================================== -->

            <label for="nome">
                Nome
            </label>

            <input
                type="text"
                id="nome"
                name="nome"
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
                id="email"
                name="email"
                required
            >



            <!-- =================================================
                 SENHA
            ================================================== -->

            <label for="senha">
                Senha
            </label>

            <input
                type="password"
                id="senha"
                name="senha"
                required
            >



            <!-- =================================================
                 TURMA DO REPRESENTANTE
            ================================================== -->

            <label for="id_turma">
                Turma
            </label>

            <select
                name="id_turma"
                id="id_turma"
                required
            >

                <option value="">
                    Selecione a turma
                </option>


                <?php if ($turmas && $turmas->num_rows > 0): ?>

                    <?php while ($turma = $turmas->fetch_assoc()): ?>

                        <option
                            value="<?php echo $turma["id_turma"]; ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $turma["serie"]
                            );

                            echo " - ";

                            echo htmlspecialchars(
                                $turma["curso"]
                            );

                            ?>

                        </option>

                    <?php endwhile; ?>

                <?php endif; ?>


            </select>



            <!-- =================================================
                 BOTÕES
            ================================================== -->

            <div class="botoes">


                <button
                    type="button"
                    class="cancelar"
                    onclick="window.location.href='gerenciar_representantes.php';"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="cadastrar"
                >

                    Cadastrar representante

                </button>


            </div>


        </form>


    </main>


<?php endif; ?>

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