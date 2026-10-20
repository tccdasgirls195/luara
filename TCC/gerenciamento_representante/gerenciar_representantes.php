<?php
// ==========================================================
// 1. INICIA A SESSÃO
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// 2. VERIFICA SE O USUÁRIO ESTÁ LOGADO
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
// 4. LOGOUT
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// ==========================================================
// 5. CABEÇALHOS ANTI-CACHE
// ==========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================================
// 6. CONEXÃO COM O BANCO
// ==========================================================
require_once dirname(__DIR__) . "/conexao.php";


// =====================================================
// PESQUISA
// =====================================================

$pesquisa = "";

if (isset($_GET["pesquisa"])) {
    $pesquisa = trim($_GET["pesquisa"]);
}

$busca = "%" . $pesquisa . "%";

// =====================================================
// FILTRO POR TURMA
// =====================================================

$turmaFiltro = "";

if (isset($_GET["turma"])) {
    $turmaFiltro = trim($_GET["turma"]);
}

// =====================================================
// BUSCAR TURMAS PARA O FILTRO
// =====================================================

$sqlTurmas = "
    SELECT id_turma, serie, curso
    FROM turma
    ORDER BY curso, serie
";

$resultTurmas = $conexao->query($sqlTurmas);

if (!$resultTurmas) {
    die("Erro ao buscar as turmas: " . $conexao->error);
}

// =====================================================
// REPRESENTANTES
// =====================================================

$sql = "
    SELECT
        r.id_representante,
        r.nome,
        r.email,
        r.status,
        r.id_turma,
        t.serie,
        t.curso,
        t.periodo
    FROM representante r
    LEFT JOIN turma t
        ON r.id_turma = t.id_turma
    WHERE (r.nome LIKE ? OR r.email LIKE ?)
";

if ($turmaFiltro !== "") {
    $sql .= " AND r.id_turma = ?";
}

$sql .= " ORDER BY t.curso, t.serie, r.nome";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a pesquisa: " . $conexao->error);
}

if ($turmaFiltro !== "") {
    $idTurmaFiltro = intval($turmaFiltro);
    $stmt->bind_param("ssi", $busca, $busca, $idTurmaFiltro);
} else {
    $stmt->bind_param("ss", $busca, $busca);
}

$stmt->execute();

$resultRepresentante = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Representantes</title>

    <link rel="stylesheet"
          href="../css/gerenciar_usuarios.css">


<style>
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.ativo { display: flex; }
.modal-caixa {
    width: 420px;
    max-width: calc(100% - 40px);
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0,0,0,.25);
}
.modal-icone {
    width: 58px;
    height: 58px;
    margin: 0 auto 15px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff3cd;
    color: #856404;
    font-size: 28px;
    font-weight: bold;
}
.modal-caixa h2 { margin: 0 0 10px; color: #333; }
.modal-caixa p { margin: 0 0 22px; color: #555; }
.modal-botoes { display: flex; justify-content: center; gap: 12px; }
.modal-botoes button {
    border: none;
    border-radius: 22px;
    padding: 11px 22px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}
.btn-cancelar { background: #e9e9e9; color: #555; }
.btn-confirmar { background: #e52b2b; color: #fff; }
.btn-confirmar.ativar { background: #5dcc7b; }
.popup-sucesso {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10000;
    min-width: 320px;
    max-width: calc(100% - 40px);
    padding: 22px 28px;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,.2);
    text-align: center;
    font-size: 17px;
    font-weight: bold;
}
.popup-sucesso .check {
    display: block;
    font-size: 32px;
    margin-bottom: 7px;
}
</style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">


<style>
.mensagem-sucesso {
    max-width: 900px; margin: 18px auto; padding: 14px 20px;
    background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
    border-radius: 8px; text-align: center; font-weight: 600;
}
</style>


<style>
.toast-sucesso {
  position: fixed; top: 50%; left: 50%; z-index: 99999;
  display: flex; align-items: center; gap: 12px;
  width: max-content; max-width: min(420px, calc(100vw - 32px));
  padding: 16px 20px; border-radius: 12px; color: #155724;
  background: #d4edda; border: 1px solid #b7dfc1;
  box-shadow: 0 10px 30px rgba(0,0,0,.18);
  font: 600 15px/1.4 Arial, sans-serif;
  transform: translate(-50%, -50%);
  animation: toastEntrar .25s ease-out;
}
.toast-sucesso .toast-icone { font-size: 23px; line-height: 1; }
.toast-sucesso.saindo { opacity: 0; transform: translate(-50%, -55%); transition: opacity .35s, transform .35s; }
@keyframes toastEntrar { from { opacity: 0; transform: translate(-50%, -45%); } to { opacity: 1; transform: translate(-50%, -50%); } }
</style>

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

    <h1>Gerenciamento de Representantes</h1>

</section>
<br>
    <!-- =====================================================
         CADASTRAR REPRESENTANTE
    ====================================================== -->

<div class="novo-usuario">

        <a href="cadastrar_representante.php">
            + Cadastrar representante
        </a>

    </div>

    <!-- =====================================================
         PESQUISA + FILTRO POR TURMA
    ====================================================== -->

    <form
        method="GET"
        class="pesquisa"
    >

        <!-- PESQUISA POR NOME OU E-MAIL -->

        <input
            type="text"
            name="pesquisa"
            placeholder="Pesquisar por nome ou e-mail..."
            value="<?php echo htmlspecialchars($pesquisa); ?>"
        >

        <!-- FILTRO POR TURMA -->

        <select name="turma">

            <option value="">
                Todas as turmas
            </option>

            <?php while ($turma = $resultTurmas->fetch_assoc()): ?>

                <option
                    value="<?php echo $turma["id_turma"]; ?>"
                    <?php
                    echo ($turmaFiltro == $turma["id_turma"])
                        ? "selected"
                        : "";
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $turma["serie"] . " - " . $turma["curso"]
                    );
                    ?>

                </option>

            <?php endwhile; ?>

        </select>

        <!-- BOTÃO -->

        <button type="submit">
            Pesquisar
        </button>

    </form>

    <!-- =====================================================
         TABELA
    ====================================================== -->

    <div class="tabela-container">

        <table>

            <thead>

                <tr>

                    <th>
                        Nome
                    </th>

                    <th>
                        E-mail
                    </th>

                    <th>
                        Tipo
                    </th>

                    <th>
                        Turma
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Ações
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php if ($resultRepresentante->num_rows > 0): ?>

                    <?php while ($usuario = $resultRepresentante->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $usuario["nome"]
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $usuario["email"]
                                );
                                ?>

                            </td>

                            <td>
                                Representante
                            </td>

<td>

    <?php

        if (
            $usuario["serie"] !== null &&
            $usuario["curso"] !== null
        ) {

            echo htmlspecialchars(
                $usuario["serie"] .
                " - " .
                $usuario["curso"] .
                " - " .
                (
                    $usuario["periodo"] == "I"
                        ? "Integral"
                        : (
                            $usuario["periodo"] == "N"
                                ? "Noturno"
                                : "Não informado"
                        )
                )
        );

        } else {

            echo "Sem turma";

        }

    ?>

</td>
                            <td>

                                <?php
                                if ($usuario["status"] == "Ativo"):
                                ?>

                                    <span class="status ativo">
                                        Ativo
                                    </span>

                                <?php else: ?>

                                    <span class="status bloqueado">
                                        Bloqueado
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="acoes">

                                <!-- EDITAR -->

                                <a
                                    href="editar_representante.php?tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                    class="editar">
                                    Editar
                                </a>

                                <!-- BLOQUEAR / ATIVAR -->

                                <?php
                                if ($usuario["status"] == "Ativo"):
                                ?>

                                    <a
                                        href="acoes_representante.php?acao=bloquear&id=<?php echo $usuario["id_representante"]; ?>"
                                        class="bloquear acao-confirmar"
                                    >
                                        Bloquear
                                    </a>

                                <?php else: ?>

                                    <a
                                        href="acoes_representante.php?acao=ativar&id=<?php echo $usuario["id_representante"]; ?>"
                                        class="ativar acao-confirmar"
                                    >
                                        Ativar
                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" style="text-align: center;">
                            Nenhum representante encontrado.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<section>
      <div class="container">
        <div class="row">
          <div class="col-sm-2 offset-sm-5 text-center">
            <button class="btn btn-warning text-white btn-block" onclick="voltar()">Voltar</button>
            <script>
              function voltar(){history.back();
              }
            </script>
          </div>
        </div>
      </div>
    </section><!-- End About Section -->

<!-- MENSAGEM DE CONFIRMAÇÃO -->
<div id="modalConfirmacao" class="modal-overlay">
    <div class="modal-caixa">
        <div class="modal-icone">!</div>
        <h2>Confirmar ação</h2>
        <p id="textoConfirmacao"></p>
        <div class="modal-botoes">
            <button type="button" id="btnCancelar" class="btn-cancelar">Cancelar</button>
            <button type="button" id="btnConfirmar" class="btn-confirmar">Bloquear</button>
        </div>
    </div>
</div>

<?php if (isset($_GET["mensagem"]) && in_array($_GET["mensagem"], ["bloqueado", "ativado"], true)): ?>
    <div id="popupSucesso" class="popup-sucesso">
        <span class="check">✓</span>
        <?php echo $_GET["mensagem"] === "bloqueado" ? "Representante bloqueado com sucesso!" : "Representante ativado com sucesso!"; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalConfirmacao");
    const texto = document.getElementById("textoConfirmacao");
    const btnConfirmar = document.getElementById("btnConfirmar");
    const btnCancelar = document.getElementById("btnCancelar");
    let linkConfirmado = null;

    document.querySelectorAll(".acao-confirmar").forEach(function (link) {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            linkConfirmado = link;
            const ativar = link.classList.contains("ativar");
            texto.textContent = ativar
                ? "Tem certeza que deseja ativar este usuário?"
                : "Tem certeza que deseja bloquear este usuário?";
            btnConfirmar.textContent = ativar ? "Ativar" : "Bloquear";
            btnConfirmar.classList.toggle("ativar", ativar);
            modal.classList.add("ativo");
        });
    });

    btnCancelar.addEventListener("click", function () {
        modal.classList.remove("ativo");
        linkConfirmado = null;
    });

    btnConfirmar.addEventListener("click", function () {
        if (linkConfirmado) {
            window.location.href = linkConfirmado.href;
        }
    });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.classList.remove("ativo");
            linkConfirmado = null;
        }
    });

    const popup = document.getElementById("popupSucesso");
    if (popup) {
        setTimeout(function () {
            popup.style.opacity = "0";
            popup.style.transition = "opacity .3s";
            setTimeout(function () { popup.remove(); }, 300);
        }, 3000);

        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete("mensagem");
            window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ""));
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
<div id="toastSucesso" class="toast-sucesso" role="status" aria-live="polite">
  <span class="toast-icone" aria-hidden="true">✓</span>
  <span><?= htmlspecialchars($_SESSION['mensagem_sucesso'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php unset($_SESSION['mensagem_sucesso']); ?>
<script>
  window.setTimeout(function () {
    const toast = document.getElementById('toastSucesso');
    if (toast) {
      toast.classList.add('saindo');
      window.setTimeout(function () { toast.remove(); }, 400);
    }
  }, 3000);
</script>
<?php endif; ?>

</body>

</html>

<?php

$stmt->close();
$conexao->close();

?>
