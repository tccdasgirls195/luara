<?php
include("conexao.php");

$id_ambiente = $_GET["id"];
$data = $_GET["data"];
$horario = $_GET["horario"];

// Exemplo.
// Depois você pode pegar o professor logado pela sessão.
$id_professor = 1;
$id_gestao = 1;

$sql = "INSERT INTO agendamentos
(nome_prof,
descr,
data_agendamento,
id_gestao,
id_professor,
id_ambientes,
horario)

VALUES(

'Professor',
'Aula',
'$data',
$id_gestao,
$id_professor,
$id_ambiente,
'$horario'

)";

mysqli_query($conexao,$sql);

header("Location: agendamento.php");