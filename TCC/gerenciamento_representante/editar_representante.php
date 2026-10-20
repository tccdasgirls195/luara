<?php
// ==========================================================
// INICIA A SESSÃO
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// VERIFICA SE O USUÁRIO ESTÁ LOGADO
// ==========================================================
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// 3. VERIFICA O TIPO DE USUÁRIO

$tipoPermitido = ['administrador', 'coordenador'];

if (
    !isset($_SESSION['usuario_tipo']) ||
    !in_array($_SESSION['usuario_tipo'], $tipoPermitido, true)
) {
    header("Location: ../index.php");
    exit();
}
// ==========================================================
// LOGOUT
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// ==========================================================
// CABEÇALHOS ANTI-CACHE
// ==========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================================
// CONEXÃO COM O BANCO
// ==========================================================
require_once dirname(__DIR__) . "/conexao.php";

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

        $stmt = $conexao->prepare($sql);

        if (!$stmt) {

            $erro =
                "Erro ao preparar a atualização: " .
                $conexao->error;

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

            $stmt = $conexao->prepare($sql);

            if (!$stmt) {

                $erro =
                    "Erro ao preparar a alteração da senha: " .
                    $conexao->error;

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

            $_SESSION['mensagem_sucesso'] = 'Alterações salvas com sucesso!';
            header("Location: gerenciar_representantes.php");
            $conexao->close();
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
        t.curso,
        t.periodo
    FROM representante r
    LEFT JOIN turma t
        ON r.id_turma = t.id_turma
    WHERE r.id_representante = ?
";

$stmt = $conexao->prepare($sql);

if (!$stmt) {

    die(
        "Erro ao preparar a busca do representante: " .
        $conexao->error
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

    <link
        rel="stylesheet"
        href="../css/editar_usuario.css">


<style>
/* Modal de confirmação no mesmo padrão das ações Ativar/Bloquear */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:99999; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.ativo { display:flex; }
.modal-caixa { width:420px; max-width:100%; background:#fff; border-radius:14px; padding:28px; text-align:center; box-shadow:0 8px 30px rgba(0,0,0,.25); }
.modal-icone { width:58px; height:58px; margin:0 auto 15px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:#fff3cd; color:#856404; font-size:28px; font-weight:bold; }
.modal-caixa h2 { margin:0 0 12px; font-size:22px; }
.modal-caixa p { margin:0 0 22px; color:#444; }
.modal-botoes { display:flex; justify-content:center; gap:12px; flex-wrap:wrap; }
.modal-botoes button { padding:10px 20px; border-radius:20px; border:none; cursor:pointer; font-weight:600; }
.btn-cancelar { background:#e9e9e9; color:#222; }
.btn-confirmar { background:#e52b2b; color:#fff; }
</style>

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
                href="../agendamento/agendamento.php"
                class="has-submenu">

                Agendamento

            </a>

            <ul class="submenu">

                <li>

                    <a href="../agendamento/meus-agendamentos.php">
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

        <h1>Editar Representante</h1>

    </section>
    <br>

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

                $turmaTexto =
                    ($usuario["serie"] ?? "") .
                    " - " .
                    ($usuario["curso"] ?? "") .
                    " - ";

                if (($usuario["periodo"] ?? "") == "I") {

                    $turmaTexto .= "Integral";

                } elseif (($usuario["periodo"] ?? "") == "N") {

                    $turmaTexto .= "Noturno";

                } else {

                    $turmaTexto .= "Não informado";

                }

                echo htmlspecialchars($turmaTexto);

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





<!-- Pop-up de confirmação de salvamento, no mesmo estilo de Ativar/Bloquear -->
<div id="modalConfirmacaoSalvar" class="modal-overlay" aria-hidden="true">
  <div class="modal-caixa" role="dialog" aria-modal="true" aria-labelledby="tituloConfirmacaoSalvar">
    <div class="modal-icone">!</div>
    <h2 id="tituloConfirmacaoSalvar">Confirmar alterações</h2>
    <p>Deseja realmente salvar as alterações realizadas?</p>
    <div class="modal-botoes">
      <button type="button" id="btnCancelarSalvar" class="btn-cancelar">Cancelar</button>
      <button type="button" id="btnConfirmarSalvar" class="btn-confirmar">Sim, salvar</button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('modalConfirmacaoSalvar');
  const btnCancelar = document.getElementById('btnCancelarSalvar');
  const btnConfirmar = document.getElementById('btnConfirmarSalvar');
  let formularioPendente = null;
  let botaoPendente = null;
  let confirmado = false;

  function fecharModal() {
    modal.classList.remove('ativo');
    modal.setAttribute('aria-hidden', 'true');
    formularioPendente = null;
    botaoPendente = null;
    confirmado = false;
  }

  document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (confirmado) { confirmado = false; return; }
      const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
      const textoBotao = submitter ? (submitter.innerText || submitter.value || '').toLowerCase() : '';
      if (!/(salvar|cadastrar|alterar|atualizar)/.test(textoBotao)) return;
      event.preventDefault();
      formularioPendente = form;
      botaoPendente = submitter;
      modal.classList.add('ativo');
      modal.setAttribute('aria-hidden', 'false');
      btnConfirmar.focus();
    });
  });

  btnCancelar.addEventListener('click', fecharModal);
  btnConfirmar.addEventListener('click', function () {
    if (!formularioPendente) return;
    const form = formularioPendente;
    const submitter = botaoPendente;
    confirmado = true;
    fecharModal();
    confirmado = true;
    if (typeof form.requestSubmit === 'function') {
      submitter ? form.requestSubmit(submitter) : form.requestSubmit();
    } else {
      form.submit();
    }
  });
  modal.addEventListener('click', function (event) { if (event.target === modal) fecharModal(); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && modal.classList.contains('ativo')) fecharModal(); });
});
</script>

</body>

</html>

<?php

$conexao->close();

?>
