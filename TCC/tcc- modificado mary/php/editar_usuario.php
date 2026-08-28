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

if (!isset($_GET["tipo"]) || !isset($_GET["id"])) {

    header("Location: gerenciar_usuarios.php");
    exit;

}

$tipo = $_GET["tipo"];
$id = intval($_GET["id"]);


// =====================================================
// TABELAS
// =====================================================

$tabelas = [

    "administrador" => [
        "tabela" => "administrador",
        "id" => "id_administrador"
    ],

    "coordenador" => [
        "tabela" => "coordenador",
        "id" => "id_coordenador"
    ],

    "professor" => [
        "tabela" => "professor",
        "id" => "id_professor"
    ],

    "representante" => [
        "tabela" => "representante",
        "id" => "id_representante"
    ],

    "gestao" => [
        "tabela" => "gestao",
        "id" => "id_gestao"
    ]

];


// =====================================================
// VERIFICAR TIPO
// =====================================================

if (!isset($tabelas[$tipo])) {

    die("Tipo de usuário inválido.");

}


$tabela = $tabelas[$tipo]["tabela"];
$campoId = $tabelas[$tipo]["id"];


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

    /*
     * O tipo novo só será considerado para
     * professor e coordenador.
     */
    $novoTipo = $_POST["novo_tipo"] ?? $tipo;


    // =================================================
    // VALIDAR NOME E E-MAIL
    // =================================================

    if ($nome == "" || $email == "") {

        $erro = "Nome e e-mail são obrigatórios.";

    }


    // =================================================
    // PROFESSOR -> COORDENADOR
    // =================================================

    elseif (
        $tipo == "professor" &&
        $novoTipo == "coordenador"
    ) {

        $curso = $_POST["curso"] ?? "";

        if ($curso == "") {

            $erro = "Selecione o curso do coordenador.";

        } else {

            try {

                $conn->begin_transaction();


                // -----------------------------------------
                // VERIFICAR SE O PROFESSOR EXISTE
                // -----------------------------------------

                $sql = "
                    SELECT *
                    FROM professor
                    WHERE id_professor = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $professor = $resultado->fetch_assoc();

                $stmt->close();


                if (!$professor) {

                    throw new Exception(
                        "Professor não encontrado."
                    );

                }


                // -----------------------------------------
                // VERIFICAR AGENDAMENTOS
                // -----------------------------------------

                $sql = "
                    SELECT COUNT(*) AS total
                    FROM agendamentos
                    WHERE id_professor = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $dependencias = $resultado->fetch_assoc();

                $stmt->close();


                if ($dependencias["total"] > 0) {

                    throw new Exception(
                        "Este professor possui agendamentos cadastrados. " .
                        "Não é possível transformá-lo em coordenador enquanto " .
                        "existirem esses agendamentos."
                    );

                }


                // -----------------------------------------
                // PEGAR O ADMINISTRADOR RESPONSÁVEL
                //
                // Existe apenas um administrador no projeto.
                // -----------------------------------------

                $sql = "
                    SELECT id_administrador
                    FROM administrador
                    ORDER BY id_administrador
                    LIMIT 1
                ";

                $resultado = $conn->query($sql);

                if (!$resultado || $resultado->num_rows == 0) {

                    throw new Exception(
                        "Nenhum administrador foi encontrado."
                    );

                }

                $admin = $resultado->fetch_assoc();

                $idAdministrador =
                    $admin["id_administrador"];


                // -----------------------------------------
                // INSERIR COMO COORDENADOR
                // -----------------------------------------

                $sql = "
                    INSERT INTO coordenador
                    (
                        nome,
                        email,
                        senha,
                        curso,
                        status,
                        id_administrador
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {

                    throw new Exception(
                        "Erro ao preparar a criação do coordenador: " .
                        $conn->error
                    );

                }

                $stmt->bind_param(
                    "sssssi",
                    $nome,
                    $email,
                    $professor["senha"],
                    $curso,
                    $professor["status"],
                    $idAdministrador
                );

                if (!$stmt->execute()) {

                    throw new Exception(
                        "Erro ao transformar o professor em coordenador: " .
                        $stmt->error
                    );

                }

                $stmt->close();


                // -----------------------------------------
                // EXCLUIR PROFESSOR ANTIGO
                // -----------------------------------------

                $sql = "
                    DELETE FROM professor
                    WHERE id_professor = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);

                if (!$stmt->execute()) {

                    throw new Exception(
                        "Não foi possível finalizar a troca de tipo: " .
                        $stmt->error
                    );

                }

                $stmt->close();


                // -----------------------------------------
                // FINALIZAR
                // -----------------------------------------

                $conn->commit();


                $mensagemSucesso =
                    "Os dados foram alterados com sucesso!!!";


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
                                <?php
                                echo $mensagemSucesso;
                                ?>
                            </h1>

                            <p>
                                O usuário foi transformado em coordenador
                                corretamente.
                            </p>

                            <a
                                href="gerenciar_usuarios.php"
                                class="voltar">

                                Voltar para gerenciamento

                            </a>

                        </div>

                    </main>

                </body>

                </html>

                <?php

                exit;


            } catch (Exception $e) {

                $conn->rollback();

                $erro = $e->getMessage();

            }

        }

    }


    // =================================================
    // COORDENADOR -> PROFESSOR
    // =================================================

    elseif (
        $tipo == "coordenador" &&
        $novoTipo == "professor"
    ) {

        $idCoordenadorResponsavel =
            intval($_POST["id_coordenador_responsavel"] ?? 0);


        if ($idCoordenadorResponsavel <= 0) {

            $erro =
                "Selecione um coordenador responsável.";

        } elseif (
            $idCoordenadorResponsavel == $id
        ) {

            $erro =
                "O coordenador que está sendo transformado " .
                "não pode ser responsável por ele mesmo.";

        } else {

            try {

                $conn->begin_transaction();


                // -----------------------------------------
                // BUSCAR COORDENADOR
                // -----------------------------------------

                $sql = "
                    SELECT *
                    FROM coordenador
                    WHERE id_coordenador = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $coordenador = $resultado->fetch_assoc();

                $stmt->close();


                if (!$coordenador) {

                    throw new Exception(
                        "Coordenador não encontrado."
                    );

                }


                // -----------------------------------------
                // VERIFICAR TURMAS
                // -----------------------------------------

                $sql = "
                    SELECT COUNT(*) AS total
                    FROM turma
                    WHERE id_coordenador = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $turmas = $resultado->fetch_assoc();

                $stmt->close();


                if ($turmas["total"] > 0) {

                    throw new Exception(
                        "Este coordenador possui turmas vinculadas. " .
                        "Não é possível transformá-lo em professor " .
                        "enquanto essas turmas estiverem vinculadas a ele."
                    );

                }


                // -----------------------------------------
                // VERIFICAR PROFESSORES VINCULADOS
                // -----------------------------------------

                $sql = "
                    SELECT COUNT(*) AS total
                    FROM professor
                    WHERE id_coordenador = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $professores = $resultado->fetch_assoc();

                $stmt->close();


                if ($professores["total"] > 0) {

                    throw new Exception(
                        "Este coordenador possui professores vinculados. " .
                        "Altere o coordenador responsável desses professores " .
                        "antes de fazer a troca."
                    );

                }


                // -----------------------------------------
                // INSERIR COMO PROFESSOR
                // -----------------------------------------

                $sql = "
                    INSERT INTO professor
                    (
                        nome,
                        email,
                        senha,
                        status,
                        id_coordenador,
                        id_administrador
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {

                    throw new Exception(
                        "Erro ao preparar a criação do professor: " .
                        $conn->error
                    );

                }


                $stmt->bind_param(
                    "ssssii",
                    $nome,
                    $email,
                    $coordenador["senha"],
                    $coordenador["status"],
                    $idCoordenadorResponsavel,
                    $coordenador["id_administrador"]
                );


                if (!$stmt->execute()) {

                    throw new Exception(
                        "Erro ao transformar o coordenador em professor: " .
                        $stmt->error
                    );

                }

                $stmt->close();


                // -----------------------------------------
                // EXCLUIR COORDENADOR ANTIGO
                // -----------------------------------------

                $sql = "
                    DELETE FROM coordenador
                    WHERE id_coordenador = ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);

                if (!$stmt->execute()) {

                    throw new Exception(
                        "Não foi possível finalizar a troca de tipo: " .
                        $stmt->error
                    );

                }

                $stmt->close();


                // -----------------------------------------
                // FINALIZAR
                // -----------------------------------------

                $conn->commit();


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
                        href="editar_usuario.css">

                </head>

                <body>

                    <main class="mensagem">

                        <div class="caixa-sucesso">

                            <div class="icone-sucesso">
                                ✓
                            </div>

                            <h1>
                                Os dados foram alterados com sucesso!!!
                            </h1>

                            <p>
                                O usuário foi transformado em professor
                                corretamente.
                            </p>

                            <a
                                href="gerenciar_usuarios.php"
                                class="voltar">

                                Voltar para gerenciamento

                            </a>

                        </div>

                    </main>

                </body>

                </html>

                <?php

                exit;


            } catch (Exception $e) {

                $conn->rollback();

                $erro = $e->getMessage();

            }

        }

    }


    // =================================================
    // OUTROS TIPOS
    //
    // Administrador, representante e gestão
    // continuam apenas com edição normal.
    // =================================================

    else {

        // ---------------------------------------------
        // ATUALIZAR NOME E E-MAIL
        // ---------------------------------------------

        $sql = "
            UPDATE $tabela
            SET nome = ?, email = ?
            WHERE $campoId = ?
        ";

        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            die(
                "Erro ao preparar a atualização: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "ssi",
            $nome,
            $email,
            $id
        );


        if (!$stmt->execute()) {

            die(
                "Erro ao atualizar os dados: " .
                $stmt->error
            );

        }


        $stmt->close();


        // ---------------------------------------------
        // ALTERAR SENHA
        // ---------------------------------------------

        if (!empty($novaSenha)) {

            $sql = "
                UPDATE $tabela
                SET senha = ?
                WHERE $campoId = ?
            ";

            $stmt = $conn->prepare($sql);


            if (!$stmt) {

                die(
                    "Erro ao preparar a alteração da senha: " .
                    $conn->error
                );

            }


            $stmt->bind_param(
                "si",
                $novaSenha,
                $id
            );


            if (!$stmt->execute()) {

                die(
                    "Erro ao alterar a senha: " .
                    $stmt->error
                );

            }


            $stmt->close();

        }


        // ---------------------------------------------
        // MENSAGEM DE SUCESSO
        // ---------------------------------------------

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
                href="editar_usuario.css">

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
                        As informações do usuário foram
                        atualizadas corretamente.
                    </p>

                    <a
                        href="gerenciar_usuarios.php"
                        class="voltar">

                        Voltar para gerenciamento

                    </a>

                </div>

            </main>

        </body>

        </html>

        <?php

        exit;

    }

}


// =====================================================
// BUSCAR USUÁRIO
// =====================================================

$sql = "
    SELECT *
    FROM $tabela
    WHERE $campoId = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$resultado = $stmt->get_result();

$usuario = $resultado->fetch_assoc();


if (!$usuario) {

    die("Usuário não encontrado.");

}


// =====================================================
// BUSCAR COORDENADORES
//
// Necessário quando um coordenador estiver sendo
// transformado em professor.
// =====================================================

$coordenadores = null;

if ($tipo == "coordenador") {

    $sql = "
        SELECT
            id_coordenador,
            nome,
            curso
        FROM coordenador
        WHERE id_coordenador != ?
        AND status = 'Ativo'
        ORDER BY nome
    ";

    $stmtCoord = $conn->prepare($sql);

    $stmtCoord->bind_param(
        "i",
        $id
    );

    $stmtCoord->execute();

    $coordenadores =
        $stmtCoord->get_result();

}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Usuário</title>

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


    <h1>
        Editar Usuário
    </h1>


    <p class="subtitulo">

        Altere os dados do usuário.

    </p>



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

        <label for="novo_tipo">

            Tipo de usuário

        </label>


        <?php if ($tipo == "professor"): ?>


            <!-- PROFESSOR PODE VIRAR COORDENADOR -->

            <select
                name="novo_tipo"
                id="novo_tipo"
                onchange="mostrarCurso()"
            >

                <option
                    value="professor"
                    selected>

                    Professor

                </option>

                <option
                    value="coordenador">

                    Coordenador

                </option>

            </select>


            <!-- CURSO DO COORDENADOR -->

            <div
                id="campoCurso"
                style="display: none;">

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


        <?php elseif ($tipo == "coordenador"): ?>


            <!-- COORDENADOR PODE VIRAR PROFESSOR -->

            <select
                name="novo_tipo"
                id="novo_tipo"
                onchange="mostrarCoordenador()"
            >

                <option
                    value="coordenador"
                    selected>

                    Coordenador

                </option>

                <option
                    value="professor">

                    Professor

                </option>

            </select>


            <!-- COORDENADOR RESPONSÁVEL -->

            <div
                id="campoCoordenador"
                style="display: none;">

                <label for="id_coordenador_responsavel">

                    Coordenador responsável

                </label>


                <select
                    name="id_coordenador_responsavel"
                    id="id_coordenador_responsavel"
                >

                    <option value="">

                        Selecione o coordenador

                    </option>


                    <?php if ($coordenadores): ?>

                        <?php while (
                            $coord =
                            $coordenadores->fetch_assoc()
                        ): ?>

                            <option
                                value="<?php echo $coord["id_coordenador"]; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $coord["nome"]
                                );

                                echo " - ";

                                echo htmlspecialchars(
                                    $coord["curso"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>


                </select>

            </div>


        <?php else: ?>


            <!-- OUTROS TIPOS NÃO PODEM SER ALTERADOS -->

            <input
                type="text"
                id="tipo"
                value="<?php echo ucfirst($tipo); ?>"
                disabled
            >

            <input
                type="hidden"
                name="novo_tipo"
                value="<?php echo $tipo; ?>"
            >


        <?php endif; ?>



        <!-- =================================================
             BOTÕES
        ================================================== -->

        <div class="botoes">


            <!-- CANCELAR -->

            <button
                type="button"
                class="cancelar"
                onclick="window.location.href='gerenciar_usuarios.php';">

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



<script>


// =====================================================
// PROFESSOR -> COORDENADOR
// =====================================================

function mostrarCurso() {

    const tipo =
        document.getElementById("novo_tipo");

    const campo =
        document.getElementById("campoCurso");


    if (!tipo || !campo) {

        return;

    }


    if (tipo.value === "coordenador") {

        campo.style.display = "block";

    } else {

        campo.style.display = "none";

    }

}



// =====================================================
// COORDENADOR -> PROFESSOR
// =====================================================

function mostrarCoordenador() {

    const tipo =
        document.getElementById("novo_tipo");

    const campo =
        document.getElementById("campoCoordenador");


    if (!tipo || !campo) {

        return;

    }


    if (tipo.value === "professor") {

        campo.style.display = "block";

    } else {

        campo.style.display = "none";

    }

}


</script>


</body>

</html>


<?php

$conn->close();

?>
