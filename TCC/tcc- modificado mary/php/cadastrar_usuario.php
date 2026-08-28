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
// COMO EXISTE APENAS UM ADMINISTRADOR,
// USAREMOS O ID 1 AUTOMATICAMENTE
// =====================================================

$idAdministrador = 1;


// =====================================================
// CADASTRAR USUÁRIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senhaUsuario = trim($_POST["senha"]);
    $tipo = $_POST["tipo"];


    // =================================================
    // ADMINISTRADOR
    // =================================================

    if ($tipo == "administrador") {

        $sql = "INSERT INTO administrador
                (nome, email, senha, status)
                VALUES (?, ?, ?, 'Ativo')";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sss",
                $nome,
                $email,
                $senhaUsuario
            );

            if ($stmt->execute()) {

                $cadastroSucesso = true;

            } else {

                $erro = "Erro ao cadastrar administrador: "
                      . $stmt->error;

            }

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }


    // =================================================
    // COORDENADOR
    // =================================================

    elseif ($tipo == "coordenador") {

        $curso = $_POST["curso"];

        $sql = "INSERT INTO coordenador
                (nome, email, senha, curso, id_administrador, status)
                VALUES (?, ?, ?, ?, ?, 'Ativo')";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "ssssi",
                $nome,
                $email,
                $senhaUsuario,
                $curso,
                $idAdministrador
            );

            if ($stmt->execute()) {

                $cadastroSucesso = true;

            } else {

                $erro = "Erro ao cadastrar coordenador: "
                      . $stmt->error;

            }

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }


    // =================================================
    // PROFESSOR
    // =================================================

    elseif ($tipo == "professor") {

        $idCoordenador = intval($_POST["id_coordenador"]);

        $sql = "INSERT INTO professor
                (nome, email, senha, id_coordenador, id_administrador, status)
                VALUES (?, ?, ?, ?, ?, 'Ativo')";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sssii",
                $nome,
                $email,
                $senhaUsuario,
                $idCoordenador,
                $idAdministrador
            );

            if ($stmt->execute()) {

                $cadastroSucesso = true;

            } else {

                $erro = "Erro ao cadastrar professor: "
                      . $stmt->error;

            }

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }


    // =================================================
    // REPRESENTANTE
    // =================================================

    elseif ($tipo == "representante") {

        $idTurma = intval($_POST["id_turma"]);

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

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }


    // =================================================
    // GESTÃO
    // =================================================

    elseif ($tipo == "gestao") {

        $sql = "INSERT INTO gestao
                (nome, email, senha, id_administrador, status)
                VALUES (?, ?, ?, ?, 'Ativo')";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "sssi",
                $nome,
                $email,
                $senhaUsuario,
                $idAdministrador
            );

            if ($stmt->execute()) {

                $cadastroSucesso = true;

            } else {

                $erro = "Erro ao cadastrar gestão: "
                      . $stmt->error;

            }

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }


    else {

        $erro = "Selecione um tipo de usuário.";

    }

}


// =====================================================
// BUSCAR COORDENADORES
// =====================================================

$coordenadores = $conn->query(
    "SELECT id_coordenador, nome, curso
     FROM coordenador
     WHERE status = 'Ativo'
     ORDER BY nome"
);


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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Usuário</title>

    <link rel="stylesheet"
          href="../css/cadastrar_usuario.css">

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
                Usuário cadastrado com sucesso!!!
            </h1>


            <p>
                O novo usuário foi adicionado ao sistema.
            </p>


            <a
                href="gerenciar_usuarios.php"
                class="voltar">

                Voltar para gerenciamento

            </a>

        </div>

    </main>


<?php else: ?>


    <!-- =================================================
         FORMULÁRIO
    ================================================== -->

    <main class="container">


        <h1>
            Cadastrar Usuário
        </h1>


        <p class="subtitulo">

            Preencha os dados para cadastrar um novo usuário.

        </p>



        <?php if (!empty($erro)): ?>

            <div class="erro">

                <?php echo htmlspecialchars($erro); ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            class="formulario">


            <!-- NOME -->

            <label for="nome">
                Nome
            </label>

            <input
                type="text"
                id="nome"
                name="nome"
                required
            >



            <!-- E-MAIL -->

            <label for="email">
                E-mail
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >



            <!-- SENHA -->

            <label for="senha">
                Senha
            </label>

            <input
                type="password"
                id="senha"
                name="senha"
                required
            >



            <!-- TIPO -->

            <label for="tipo">
                Tipo de usuário
            </label>

            <select
                id="tipo"
                name="tipo"
                required
                onchange="mostrarCampos()"
            >

                <option value="">
                    Selecione o tipo
                </option>

                <option value="administrador">
                    Administrador
                </option>

                <option value="coordenador">
                    Coordenador
                </option>

                <option value="professor">
                    Professor
                </option>

                <option value="representante">
                    Representante
                </option>

                <option value="gestao">
                    Gestão
                </option>

            </select>



            <!-- =================================================
                 CURSO DO COORDENADOR
            ================================================== -->

            <div
                id="campoCurso"
                class="campo-extra">

                <label for="curso">
                    Curso
                </label>

                <select
                    name="curso"
                    id="curso"
                >

                    <option value="">
                        Selecione o curso
                    </option>

                    <option value="DS">
                        Desenvolvimento de Sistemas
                    </option>

                    <option value="ADM">
                        Administração
                    </option>

                    <option value="AUT">
                        Automação
                    </option>

                    <option value="RH">
                        Recursos Humanos
                    </option>

                </select>

            </div>



            <!-- =================================================
                 COORDENADOR DO PROFESSOR
            ================================================== -->

            <div
                id="campoCoordenador"
                class="campo-extra">

                <label for="id_coordenador">

                    Coordenador responsável

                </label>

                <select
                    name="id_coordenador"
                    id="id_coordenador"
                >

                    <option value="">
                        Selecione o coordenador
                    </option>


                    <?php while ($coordenador = $coordenadores->fetch_assoc()): ?>

                        <option
                            value="<?php echo $coordenador["id_coordenador"]; ?>">

                            <?php

                            echo htmlspecialchars(
                                $coordenador["nome"]
                            );

                            echo " - ";

                            echo htmlspecialchars(
                                $coordenador["curso"]
                            );

                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>



            <!-- =================================================
                 TURMA DO REPRESENTANTE
            ================================================== -->

            <div
                id="campoTurma"
                class="campo-extra">

                <label for="id_turma">

                    Turma

                </label>

                <select
                    name="id_turma"
                    id="id_turma"
                >

                    <option value="">
                        Selecione a turma
                    </option>


                    <?php while ($turma = $turmas->fetch_assoc()): ?>

                        <option
                            value="<?php echo $turma["id_turma"]; ?>">

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

                </select>

            </div>



            <!-- =================================================
                 BOTÕES
            ================================================== -->

            <div class="botoes">


                <button
                    type="button"
                    class="cancelar"
                    onclick="window.location.href='gerenciar_usuarios.php';">

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="cadastrar">

                    Cadastrar usuário

                </button>


            </div>


        </form>


    </main>


<?php endif; ?>



<script>

function mostrarCampos() {

    const tipo =
        document.getElementById("tipo").value;


    const campoCurso =
        document.getElementById("campoCurso");

    const campoCoordenador =
        document.getElementById("campoCoordenador");

    const campoTurma =
        document.getElementById("campoTurma");


    // Esconder todos os campos

    campoCurso.style.display = "none";

    campoCoordenador.style.display = "none";

    campoTurma.style.display = "none";


    // =================================================
    // COORDENADOR
    // =================================================

    if (tipo == "coordenador") {

        campoCurso.style.display = "block";

    }


    // =================================================
    // PROFESSOR
    // =================================================

    else if (tipo == "professor") {

        campoCoordenador.style.display = "block";

    }


    // =================================================
    // REPRESENTANTE
    // =================================================

    else if (tipo == "representante") {

        campoTurma.style.display = "block";

    }

}

</script>


</body>

</html>


<?php

$conn->close();

?>
