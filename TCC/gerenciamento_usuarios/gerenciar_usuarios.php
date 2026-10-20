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

// ==========================================================
// 3. VERIFICA O TIPO DE USUÁRIO
// ==========================================================
$tipoPermitido = 'administrador';

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== $tipoPermitido) {
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
// FILTRO POR TIPO DE USUÁRIO
// =====================================================

$tipoFiltro = "";

if (isset($_GET["tipo"])) {
    $tipoFiltro = $_GET["tipo"];
}


// Só permite os tipos existentes no filtro
$tiposPermitidos = [
    "",
    "administrador",
    "coordenador",
    "professor",
    "representante"
];

if (!in_array($tipoFiltro, $tiposPermitidos, true)) {
    $tipoFiltro = "";
}


// =====================================================
// ADMINISTRADORES
// =====================================================

$sql = "SELECT id_administrador, nome, email, status
        FROM administrador
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = mysqli_prepare($conexao, $sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultAdministrador = $stmt->get_result();


// =====================================================
// COORDENADORES
// =====================================================

$sql = "SELECT id_coordenador, nome, email, curso, status
        FROM coordenador
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = mysqli_prepare($conexao, $sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultCoordenador = $stmt->get_result();


// =====================================================
// PROFESSORES
// =====================================================

$sql = "SELECT id_professor, nome, email, status
        FROM professor
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = mysqli_prepare($conexao, $sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultProfessor = $stmt->get_result();


// =====================================================
// REPRESENTANTES
// =====================================================

$sql = "SELECT
            r.id_representante,
            r.nome,
            r.email,
            r.status,
            t.serie,
            t.curso,
            t.periodo

        FROM representante r

        LEFT JOIN turma t
        ON r.id_turma = t.id_turma

        WHERE r.nome LIKE ?
        OR r.email LIKE ?";

$stmt = mysqli_prepare($conexao, $sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultRepresentante = $stmt->get_result();


// =====================================================
// GESTÃO
// =====================================================

$sql = "SELECT id_gestao, nome, email, status
        FROM gestao
        WHERE nome LIKE ? OR email LIKE ?";

$stmt = mysqli_prepare($conexao, $sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();

$resultGestao = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Usuários</title>

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
    transition: opacity .5s ease;
}
.popup-sucesso .check {
    display: block;
    font-size: 32px;
    margin-bottom: 7px;
}

/* Reduz e centraliza a coluna de Ações */
table th:last-child,
table td:last-child {
    text-align: center !important;
    width: 1%;
    white-space: nowrap;
}

td.acoes {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}
</style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">


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

<header>
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
        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href=""> TCC</a>

    </nav>
</header>


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

<section class="titulo">
    <h1>Gerenciamento de Usuários</h1>

</section>
<br>


    <!-- =====================================================
         CADASTRAR USUÁRIO
    ====================================================== -->

    <div class="novo-usuario">

        <a href="cadastrar_usuario.php">
            + Cadastrar usuário
        </a>

    </div>


    <!-- =====================================================
         PESQUISA + FILTRO
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


        <!-- FILTRO POR TIPO -->

        <select name="tipo">

            <option value="">
                Todos os tipos
            </option>


            <option
                value="administrador"
                <?php
                echo ($tipoFiltro === "administrador")
                    ? "selected"
                    : "";
                ?>
            >
                Administrador
            </option>


            <option
                value="coordenador"
                <?php
                echo ($tipoFiltro === "coordenador")
                    ? "selected"
                    : "";
                ?>
            >
                Coordenador
            </option>


            <option
                value="professor"
                <?php
                echo ($tipoFiltro === "professor")
                    ? "selected"
                    : "";
                ?>
            >
                Professor
            </option>


            <option
                value="representante"
                <?php
                echo ($tipoFiltro === "representante")
                    ? "selected"
                    : "";
                ?>
            >
                Representante
            </option>

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
                        Curso/Turma
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


            <!-- =================================================
                 ADMINISTRADORES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "administrador"
            ):
            ?>

                <?php while ($usuario = $resultAdministrador->fetch_assoc()): ?>

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
                            Administrador
                        </td>


                        <td>
                            —
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
                                href="editar_usuario.php?tipo=administrador&id=<?php echo $usuario["id_administrador"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <!-- BLOQUEAR / ATIVAR -->

                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=administrador&id=<?php echo $usuario["id_administrador"]; ?>"
                                    class="bloquear acao-confirmar"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=administrador&id=<?php echo $usuario["id_administrador"]; ?>"
                                    class="ativar acao-confirmar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 COORDENADORES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "coordenador"
            ):
            ?>

                <?php while ($usuario = $resultCoordenador->fetch_assoc()): ?>

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
                            Coordenador
                        </td>


                        <td>

                            Curso:

                            <?php
                            echo htmlspecialchars(
                                $usuario["curso"]
                            );
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


                            <a
                                href="editar_usuario.php?tipo=coordenador&id=<?php echo $usuario["id_coordenador"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=coordenador&id=<?php echo $usuario["id_coordenador"]; ?>"
                                    class="bloquear acao-confirmar"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=coordenador&id=<?php echo $usuario["id_coordenador"]; ?>"
                                    class="ativar acao-confirmar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 PROFESSORES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "professor"
            ):
            ?>

                <?php while ($usuario = $resultProfessor->fetch_assoc()): ?>

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
                            Professor
                        </td>


                        <td>
                            —
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


                            <a
                                href="editar_usuario.php?tipo=professor&id=<?php echo $usuario["id_professor"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=professor&id=<?php echo $usuario["id_professor"]; ?>"
                                    class="bloquear acao-confirmar"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=professor&id=<?php echo $usuario["id_professor"]; ?>"
                                    class="ativar acao-confirmar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 REPRESENTANTES
            ================================================== -->

            <?php
            if (
                $tipoFiltro === ""
                ||
                $tipoFiltro === "representante"
            ):
            ?>

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

                                echo htmlspecialchars($usuario["serie"]
                                . " - "
                                . $usuario["curso"]
                                . " - "
                                . (
                                    $usuario["periodo"] == "I"
                                        ? "Integral"
                                        :(
                                            $usuario["periodo"] == "N"
                                                ? "Noturno"
                                                : "Não informado"
                                        )
                                    )
                                );
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


                            <a
                                href="editar_usuario.php?tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                    class="bloquear acao-confirmar"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=representante&id=<?php echo $usuario["id_representante"]; ?>"
                                    class="ativar acao-confirmar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            <!-- =================================================
                 GESTÃO
                 
                 Gestão NÃO possui opção no filtro.
                 Ela aparece somente quando "Todos os tipos"
                 estiver selecionado.
            ================================================== -->

            <?php if ($tipoFiltro === ""): ?>

                <?php while ($usuario = $resultGestao->fetch_assoc()): ?>

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
                            Gestão
                        </td>


                        <td>
                            —
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


                            <a
                                href="editar_usuario.php?tipo=gestao&id=<?php echo $usuario["id_gestao"]; ?>"
                                class="editar"
                            >
                                Editar
                            </a>


                            <?php
                            if ($usuario["status"] == "Ativo"):
                            ?>

                                <a
                                    href="acoes_usuario.php?acao=bloquear&tipo=gestao&id=<?php echo $usuario["id_gestao"]; ?>"
                                    class="bloquear acao-confirmar"
                                >
                                    Bloquear
                                </a>

                            <?php else: ?>

                                <a
                                    href="acoes_usuario.php?acao=ativar&tipo=gestao&id=<?php echo $usuario["id_gestao"]; ?>"
                                    class="ativar acao-confirmar"
                                >
                                    Ativar
                                </a>

                            <?php endif; ?>


                        </td>

                    </tr>

                <?php endwhile; ?>

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
              function voltar(){window.location.href = "../opcoes.php";
              }
            </script>
          </div>
        </div>
      </div>
    </section><!-- End About Section -->

<!--MENSAGEM DE CONFIRMAÇÃO -->
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
        <?php echo $_GET["mensagem"] === "bloqueado" ? "Usuário bloqueado com sucesso!" : "Usuário ativado com sucesso!"; ?>
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

    // TEMPORIZADOR DE 3 SEGUNDOS PARA A MENSAGEM
    const popup = document.getElementById("popupSucesso");
    if (popup) {
        setTimeout(function () {
            popup.style.opacity = "0";
            setTimeout(function () { popup.remove(); }, 500);
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

mysqli_close($conexao);

?>