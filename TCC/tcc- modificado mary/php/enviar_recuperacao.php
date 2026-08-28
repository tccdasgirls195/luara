<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<script>
// Evita que o navegador salve os dados do formulário no histórico de navegação
document.querySelector("form").addEventListener("submit", function() {
    // Substitui o estado atual do histórico por uma versão "limpa" antes do envio
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Agenda a limpeza dos campos logo após o disparo da requisição
    setTimeout(function() {
        document.getElementById("email").value = "";
        document.getElementById("senha").value = "";
    }, 10);
});
</script>
    
</body>
</html>


<?php

include("conexao.php");

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    die("Digite um e-mail.");
}

/*
|--------------------------------------------------------------------------
| Tabelas de usuários
|--------------------------------------------------------------------------
| Cada tabela possui uma coluna de ID diferente.
*/

$tabelas = [
    'administrador' => 'id_administrador',
    'coordenador'   => 'id_coordenador',
    'professor'     => 'id_professor',
    'representante' => 'id_representante',
    'gestao'        => 'id_gestao'
];

$usuarioEncontrado = false;
$usuario_id = null;
$usuario_tipo = null;

/*
|--------------------------------------------------------------------------
| Procura o e-mail nas tabelas
|--------------------------------------------------------------------------
*/

foreach ($tabelas as $tabela => $id_coluna) {

    $sql = "SELECT $id_coluna, email FROM $tabela WHERE email = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        continue;
    }

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {

        $usuario = mysqli_fetch_assoc($resultado);

        $usuario_id = $usuario[$id_coluna];
        $usuario_tipo = $tabela;

        $usuarioEncontrado = true;

        mysqli_stmt_close($stmt);

        break;
    }

    mysqli_stmt_close($stmt);
}

/*
|--------------------------------------------------------------------------
| Se o e-mail não estiver em nenhuma tabela
|--------------------------------------------------------------------------
*/

if (!$usuarioEncontrado) {

    die("E-mail não encontrado.");
}

/*
|--------------------------------------------------------------------------
| Cria o token
|--------------------------------------------------------------------------
*/

$token = bin2hex(random_bytes(32));

/*
|--------------------------------------------------------------------------
| Define validade de 30 minutos
|--------------------------------------------------------------------------
*/

$expiracao = date(
    'Y-m-d H:i:s',
    strtotime('+30 minutes')
);

/*
|--------------------------------------------------------------------------
| Salva a recuperação
|--------------------------------------------------------------------------
*/

$sql = "INSERT INTO recuperacao_senha
        (usuario_id, usuario_tipo, token, expiracao)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {

    die(
        "Erro ao preparar recuperação: " .
        mysqli_error($conexao)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "isss",
    $usuario_id,
    $usuario_tipo,
    $token,
    $expiracao
);

if (mysqli_stmt_execute($stmt)) {

    /*
    |--------------------------------------------------------------------------
    | Cria o link de recuperação
    |--------------------------------------------------------------------------
    */
$link = "http://localhost/TCC/php/redefinir_senha.php?token=" . $token;

    echo "Link de recuperação:<br><br>";

    echo '<a href="' . htmlspecialchars($link) . '">';
    echo htmlspecialchars($link);
    echo '</a>';

} else {

    echo "Erro ao criar recuperação: " .
         mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);

?>

<script>
window.onpageshow = function(event) {
    if (event.persisted || (performance && performance.navigation.type === 2)) {
        // Se veio do botão voltar, esconde o corpo do site e redireciona
        document.body.innerHTML = '';
        window.location.replace("login.php");
    }
};
</script>