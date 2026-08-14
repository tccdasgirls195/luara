<?php
// Inicia a sessão no PHP para armazenar o usuário logado
session_start();

// Inclui o arquivo de conexão com o banco de dados MODELO_TCC
include("conexao.php");

// Variável para armazenar mensagens de erro de autenticação
$erro = "";

// Verifica se o formulário foi enviado através do método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Remove espaços em branco extras do início e fim dos campos digitados
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Valida se ambos os campos foram preenchidos
    if (!empty($email) && !empty($senha)) {

        // Mapeia todas as tabelas de perfil do sistema e suas respectivas chaves primárias
        $tabelas = [
            'administrador' => 'id_administrador',
            'coordenador'   => 'id_coordenador',
            'professor'     => 'id_professor',
            'representante' => 'id_representante',
            'gestao'        => 'id_gestao'
        ];

        $usuarioEncontrado = false;

           // Percorre cada tabela de perfil para tentar encontrar o usuário
           foreach ($tabelas as $tabela => $id_coluna) {

            // Prepara a instrução SQL para prevenir SQL Injection
            $sql = "SELECT $id_coluna, email, senha FROM $tabela WHERE email = ?";
            $stmt = mysqli_prepare($conexao, $sql);

            if ($stmt) {
                // Vincula os parâmetros inseridos pelo usuário ("s" = string)
                mysqli_stmt_bind_param($stmt, "s", $email);
                
                // Executa a consulta no banco de dados
                mysqli_stmt_execute($stmt);
                
                // Obtém o resultado da busca
                $resultado = mysqli_stmt_get_result($stmt);

                // Se encontrou exatamente 1 registro
                if (mysqli_num_rows($resultado) === 1) {
                    $usuario = mysqli_fetch_assoc($resultado);

                    // a partir daqui, verifica se a senha fornecida corresponde à senha armazenada no banco de dados
                    // mary - dia 12 d0 8 2026 

                    if (password_verify($senha, $usuario['senha'])) {

                    // Cria a sessão do usuário
                    $_SESSION['usuario_id']    = $usuario[$id_coluna];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_tipo']  = $tabela;

                    // ==========================================
                    // REGISTRA O LOGIN BEM-SUCEDIDO
                    // ==========================================

                    $ip = $_SERVER['REMOTE_ADDR'];
                    $pagina = $_SERVER['REQUEST_URI'];

                    $sqlLog = "INSERT INTO registros_acesso
                            (usuario_id, usuario_tipo, ip, pagina)
                            VALUES (?, ?, ?, ?)";

                    $stmtLog = mysqli_prepare($conexao, $sqlLog);

                    // o if abaixo garante que a instrução preparada foi criada com sucesso antes de tentar vincular 
                    // os parâmetros e executar a inserção

                    if ($stmtLog) {

                        mysqli_stmt_bind_param(
                            $stmtLog,
                            "isss",
                            $usuario[$id_coluna],
                            $tabela,
                            $ip,
                            $pagina
                        );

                        mysqli_stmt_execute($stmtLog);

                        mysqli_stmt_close($stmtLog);
                    }

                    $usuarioEncontrado = true;

                    mysqli_stmt_close($stmt);

                    // Redireciona depois de registrar o acesso
                    header("Location: ../selecionar_lab.html");
                    exit();
                }

                // acaba a modificação mary - dia 12 d0 8 2026

                }

                // Fecha a instrução preparada
                mysqli_stmt_close($stmt);
            }
        }

        // Se após consultar todas as tabelas o usuário não for encontrado
        if (!$usuarioEncontrado) {
            $erro = "E-mail ou senha incorretos!";
        }

    } else {
        $erro = "Por favor, preencha todos os campos!";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>

    <main class="container-login">
        <div class="card-login">
            
            <h2>Entrar</h2>

            <?php if (!empty($erro)): ?>
                <div class="mensagem-erro">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">

                <div class="campo">
                    <label for="email">E-mail:</label>
                    <div class="input-com-icone">
                        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>

                <div class="campo">
                    <label for="senha">Senha:</label>
                    <div class="input-com-icone">
                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn-entrar">
                    Entrar <i class="fa-solid fa-right-to-bracket"></i>
                </button>

        <!--LUARA: ALTERAÇÕES RECUPERAR SENHA  !-->
    
        <div align = center>
            <br>
                <a href="esqueci_senha.php" class="esqueci-senha">
    Esqueci minha senha
</a>
            </div>
<!--ACABOU ALTERAÇÕES LUARA  !-->

            </form>

        </div>
    </main>

    <!-- Maria A. - Alterações: não aparecer a senha e tudo mais ao voltar !-->
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
<!-- Maria A. Câmbio desligo !-->

    

</body>
</html>