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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nova senha</title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet"href="../css/login.css">

</head>
<body>
<main class="container-login">

    <div class="card-login">
        <h2>Nova senha</h2>

        <form
            action="salvar_senha.php" method="POST" id="formNovaSenha">

            <!-- Token da recuperação -->

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <!-- =====================================================
                 NOVA SENHA
                 ===================================================== -->

            <div class="campo">
                <label for="senha">
                    Nova senha:
                </label>

                <div class="input-com-icone senha-container">
                    <input type="password"id="senha" name="senha"placeholder="Digite sua nova senha" required>

                    <i class="fa-solid fa-eye olho-senha" id="mostrarSenha"></i>
                </div>
            </div>

            <!-- =====================================================
                 CONFIRMAR SENHA
                 ===================================================== -->

            <div class="campo">

                <label for="confirmar_senha">
                    Confirmar nova senha:
                </label>

                <div class="input-com-icone senha-container">

                    <input type="password" id="confirmar_senha"name="confirmar_senha" placeholder="Confirme sua nova senha"required>

                    <i class="fa-solid fa-eye olho-senha" id="mostrarConfirmacao"></i>
                </div>

                <!-- Mensagem de erro -->

                <span id="mensagemSenha" class="mensagem-senha" ></span>

            </div>

            <!-- =====================================================
                 BOTÃO
                 ===================================================== -->

            <button type="submit"class="btn-entrar">
                Alterar senha
                <i class="fa-solid fa-key"></i>
            </button>
        </form>
    </div>
</main>

<script>
const senha = document.getElementById("senha");
const mostrarSenha = document.getElementById("mostrarSenha");

mostrarSenha.addEventListener("click", function () {

    if (senha.type === "password") {
        senha.type = "text";
        mostrarSenha.classList.remove("fa-eye");
        mostrarSenha.classList.add("fa-eye-slash");
    } else {
        senha.type = "password";
        mostrarSenha.classList.remove("fa-eye-slash");
        mostrarSenha.classList.add("fa-eye");
    }

});

const confirmarSenha = document.getElementById("confirmar_senha");

const mostrarConfirmacao = document.getElementById("mostrarConfirmacao");


mostrarConfirmacao.addEventListener("click", function () {

    if (confirmarSenha.type === "password") {
        confirmarSenha.type = "text";
        mostrarConfirmacao.classList.remove("fa-eye");
        mostrarConfirmacao.classList.add("fa-eye-slash");
    } else {
        confirmarSenha.type = "password";
        mostrarConfirmacao.classList.remove("fa-eye-slash");
        mostrarConfirmacao.classList.add("fa-eye");
    }
});

/*
   CONFIRMAR SE AS SENHAS SÃO IGUAIS
 */

const formulario =document.getElementById("formNovaSenha");
const mensagemSenha =document.getElementById("mensagemSenha");

formulario.addEventListener("submit", function (event) {
    if (senha.value !== confirmarSenha.value) {
        event.preventDefault();
        mensagemSenha.textContent =
            "As senhas não coincidem.";
        confirmarSenha.focus();

    } else {

        mensagemSenha.textContent = "";

    }

});

confirmarSenha.addEventListener("input", function () {

    if (
        confirmarSenha.value !== "" &&
        senha.value !== confirmarSenha.value
    ) {

        mensagemSenha.textContent =
            "As senhas não coincidem.";

    } else {

        mensagemSenha.textContent = "";

    }

});

</script>



<!-- =========================================================
     BLOQUEIA VOLTAR PARA A PÁGINA
     ========================================================= -->

<script>

window.onpageshow = function(event) {

    if (event.persisted ||
        (
            performance &&
            performance.navigation.type === 2
        )
    ) {
        document.body.innerHTML = '';
        window.location.replace("login.php");
    }
};

</script>
</body>
</html>