
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
    header("Location: ../login/login.php");
    exit();
}

// ==========================================================
// VERIFICA O TIPO DE USUÁRIO
// ==========================================================
$tipoPermitido = 'administrador';

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== $tipoPermitido) {
    header("Location: ../login/login.php");
    exit();
}

// ==========================================================
// LOGOUT
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../login/login.php");
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
// VERIFICAR DADOS
// =====================================================

if (
    !isset($_GET["acao"]) ||
    !isset($_GET["id"])
) {
    header("Location: gerenciar_representantes.php");
    exit;
}

$acao = $_GET["acao"];
$id = intval($_GET["id"]);

// =====================================================
// BLOQUEAR REPRESENTANTE
// =====================================================

if ($acao == "bloquear") {

    $sql = "
        UPDATE representante
        SET status = 'Bloqueado'
        WHERE id_representante = ?
    ";

    $stmt = $conexao->prepare($sql);

    if (!$stmt) {
        die(
            "Erro ao preparar a ação: " .
            $conexao->error
        );
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die(
            "Erro ao bloquear o representante: " .
            $stmt->error
        );
    }

    $stmt->close();

}

// =====================================================
// ATIVAR REPRESENTANTE
// =====================================================

elseif ($acao == "ativar") {

    $sql = "
        UPDATE representante
        SET status = 'Ativo'
        WHERE id_representante = ?
    ";

    $stmt = $conexao->prepare($sql);

    if (!$stmt) {
        die(
            "Erro ao preparar a ação: " .
            $conexao->error
        );
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die(
            "Erro ao ativar o representante: " .
            $stmt->error
        );
    }

    $stmt->close();

}

// =====================================================
// AÇÃO INVÁLIDA
// =====================================================

else {

    die("Ação inválida.");

}

// =====================================================
// RETORNAR AO GERENCIAMENTO COM MENSAGEM DE SUCESSO
// =====================================================

$_SESSION['mensagem_sucesso'] = ($acao === "bloquear")
    ? "Representante bloqueado com sucesso!"
    : "Representante ativado com sucesso!";
header("Location: gerenciar_representantes.php");
exit;

?>
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

