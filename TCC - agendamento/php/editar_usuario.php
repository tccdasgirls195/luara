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


// Verifica se o tipo existe

if (!isset($tabelas[$tipo])) {

    die("Tipo de usuário inválido.");

}


$tabela = $tabelas[$tipo]["tabela"];
$campoId = $tabelas[$tipo]["id"];


// =====================================================
// SALVAR ALTERAÇÕES
// =====================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);


    // ---------------------------------------------
    // Atualizar nome e e-mail
    // ---------------------------------------------

    $sql = "UPDATE $tabela
            SET nome = ?, email = ?
            WHERE $campoId = ?";

    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die("Erro ao preparar a atualização: " . $conn->error);

    }


    $stmt->bind_param(
        "ssi",
        $nome,
        $email,
        $id
    );


    if (!$stmt->execute()) {

        die("Erro ao atualizar os dados: " . $stmt->error);

    }


    // ---------------------------------------------
    // Alterar senha somente se foi preenchida
    // ---------------------------------------------

    if (!empty($_POST["senha"])) {

        $novaSenha = $_POST["senha"];


        $sql = "UPDATE $tabela
                SET senha = ?
                WHERE $campoId = ?";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            die("Erro ao preparar a alteração da senha: " . $conn->error);

        }


        $stmt->bind_param(
            "si",
            $novaSenha,
            $id
        );


        if (!$stmt->execute()) {

            die("Erro ao alterar a senha: " . $stmt->error);

        }

    }


    // =================================================
    // MENSAGEM DE SUCESSO
    // =================================================
    ?>

    <!DOCTYPE html>

    <html lang="pt-br">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>Alteração realizada</title>

        <link rel="stylesheet" href="../css/editar_usuario1.css">

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


// =====================================================
// BUSCAR USUÁRIO
// =====================================================

$sql = "SELECT *
        FROM $tabela
        WHERE $campoId = ?";


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

?>

<!DOCTYPE html>

<html lang="pt-br">


<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Editar Usuário</title>

    <link rel="stylesheet"
          href="editar_usuario.css">

</head>


<body>


<header class="menu">


    <div class="logo">

        ETEC

    </div>


    <nav>

        <a href="gerenciar_usuarios.php">
            Usuários
        </a>

        <a href="#">
            Home
        </a>

        <a href="#">
            Cursos
        </a>

        <a href="#">
            A Etec
        </a>

    </nav>


</header>



<main class="container">


    <h1>
        Editar Usuário
    </h1>


    <p class="subtitulo">

        Altere os dados do usuário.

    </p>



    <form
        method="POST"
        class="formulario">


        <!-- NOME -->

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



        <!-- E-MAIL -->

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



        <!-- SENHA -->

        <label for="senha">

            Nova senha

        </label>


        <input
            type="password"
            name="senha"
            id="senha"
            placeholder="Deixe vazio para manter a senha atual"
        >



        <!-- TIPO -->

        <label for="tipo">

            Tipo de usuário

        </label>


        <input
            type="text"
            id="tipo"
            value="<?php echo ucfirst($tipo); ?>"
            disabled
        >



        <!-- BOTÕES -->

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


</body>

</html>


<?php

$conn->close();

?>