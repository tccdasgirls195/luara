<?php
// ==========================================================
// INICIA A SESSÃO E VERIFICA PERMISSÃO DE ADMINISTRADOR
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: ../login/login.php");
    exit();
}

require_once dirname(__DIR__) . "/conexao.php";

if (!isset($_GET["id"])) {
    header("Location: gerenciar_ambientes.php");
    exit();
}

$id = intval($_GET["id"]);
$erro = "";
$sucesso = false;

// Salvar alterações
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $tipo = trim($_POST["tipo"] ?? "");

    if (empty($nome) || empty($tipo)) {
        $erro = "Nome e tipo são obrigatórios.";
    } else {
        $sql = "UPDATE ambientes SET nome = ?, tipo = ? WHERE id_ambientes = ?";
        $stmt = $conexao->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssi", $nome, $tipo, $id);
            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = 'Alterações salvas com sucesso!';
                $stmt->close();
                header("Location: gerenciar_ambientes.php");
                exit();
            } else {
                $erro = "Erro ao atualizar o ambiente: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $erro = "Erro ao preparar a atualização: " . $conexao->error;
        }
    }
}

// Buscar dados atuais do ambiente
$sql = "SELECT * FROM ambientes WHERE id_ambientes = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$ambiente = $resultado->fetch_assoc();
$stmt->close();

if (!$ambiente) {
    die("Ambiente não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ambiente</title>
    <link rel="stylesheet" href="../css/editar_usuario.css">

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
        <img src="../logo.png" alt="Logo">
    </div>
  <nav>
        <a href="">Home</a>
        <a href="#" class="has-submenu">Cursos</a>
        <li>
            <a href="#" class="has-submenu">A Etec</a>
            <ul class="submenu">
                <li>
                    <a href="../calendario/calendario.php">Eventos</a>
                </li>
            </ul>
        </li>
            <a href="#" class="has-submenu">Equipe Etec</a>
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
        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href="">TCC</a>
    </nav>
</header>

<?php if ($sucesso): ?>
    <main class="mensagem">
        <div class="caixa-sucesso">
            <div class="icone-sucesso">✓</div>
            <h1>Dados alterados com sucesso!</h1>
            <p>As informações do ambiente foram atualizadas corretamente.</p>
            <a href="gerenciar_ambientes.php" class="voltar">Voltar para gerenciamento</a>
        </div>
    </main>
<?php else: ?>
    <section class="titulo">
        <h1>Editar Ambiente</h1>
    </section>
    <br>
    <main class="container">
        <?php if (!empty($erro)): ?>
            <div class="erro"><?= htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario">
            <label for="nome">Nome do Ambiente</label>
            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($ambiente["nome"]); ?>" required>

            <label for="tipo">Tipo do Ambiente</label>
            <select name="tipo" id="tipo" required>
                <option value="DS" <?= ($ambiente["tipo"] == "DS") ? "selected" : ""; ?>>DS (Desenvolvimento de Sistemas)</option>
                <option value="ADM" <?= ($ambiente["tipo"] == "ADM") ? "selected" : ""; ?>>ADM (Administração)</option>
                <option value="AUT" <?= ($ambiente["tipo"] == "AUT") ? "selected" : ""; ?>>AUT (Automação)</option>
                <option value="Outros" <?= ($ambiente["tipo"] == "Outros") ? "selected" : ""; ?>>Outros</option>
            </select>

            <div class="botoes">
                <button type="button" class="cancelar" onclick="window.location.href='gerenciar_ambientes.php';">Cancelar</button>
                <button type="submit" class="salvar">Salvar alterações</button>
            </div>
        </form>
    </main>
<?php endif; ?>





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
<?php $conexao->close(); ?>