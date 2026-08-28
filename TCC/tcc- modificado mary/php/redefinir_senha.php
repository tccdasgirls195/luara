<?php

include("conexao.php");

if (!isset($_GET['token'])) {
    die("Token de recuperação não informado.");
}

$token = $_GET['token'];

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nova senha</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/login.css">

</head>

<body>

<main class="container-login">

    <div class="card-login">

        <h2>Nova senha</h2>

        <form action="salvar_senha.php" method="POST">

            <!-- Token da recuperação -->
            <input
                type="hidden"
                name="token"
                value="<?php echo htmlspecialchars($token); ?>"
            >

            <!-- CAMPO DA NOVA SENHA -->
            <div class="campo">

                <label for="senha">Nova senha:</label>

                <div class="input-com-icone senha-container">

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua nova senha"
                        required
                    >

                    <!-- OLHO -->
                    <i
                        class="fa-solid fa-eye olho-senha"
                        id="mostrarSenha">
                    </i>

                </div>

            </div>

            <!-- BOTÃO -->
            <button type="submit" class="btn-entrar">

                Alterar senha

                <i class="fa-solid fa-key"></i>

            </button>

        </form>

    </div>

</main>


<!-- =========================
     MOSTRAR / ESCONDER SENHA
     ========================= -->

<script>

const senha = document.getElementById("senha");

const mostrarSenha = document.getElementById("mostrarSenha");


mostrarSenha.addEventListener("click", function () {

    if (senha.type === "password") {

        // Mostra a senha
        senha.type = "text";

        // Troca o ícone
        mostrarSenha.classList.remove("fa-eye");
        mostrarSenha.classList.add("fa-eye-slash");

    } else {

        // Esconde a senha
        senha.type = "password";

        // Volta para o olho normal
        mostrarSenha.classList.remove("fa-eye-slash");
        mostrarSenha.classList.add("fa-eye");

    }

});

</script>

<script>
window.onpageshow = function(event) {
    if (event.persisted || (performance && performance.navigation.type === 2)) {
        // Se veio do botão voltar, esconde o corpo do site e redireciona
        document.body.innerHTML = '';
        window.location.replace("login.php");
    }
};
</script>


</body>

</html>