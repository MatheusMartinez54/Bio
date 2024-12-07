<?php
require 'config.php';

// Habilitar a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se o ID do agendamento foi passado
if (isset($_GET['id_agendamento'])) {
    $id_agendamento = $_GET['id_agendamento'];

    // Consulta principal para obter os dados do agendamento, paciente e procedimento
    $stmt = $pdo->prepare(
        '
        SELECT 
            AG.ID_AGENDAMENTO,
            AG.data_agendamento,
            AG.status_agendamento,
            AG.ID_PROCEDIMENTO,
            P.ID_PACIENTE,
            P.nome_pac,
            P.data_nasc_pac,
            P.sexo,
            P.cpf_pac,
            P.convenio,
            PROC.nome_proced,
            PROC.descr_proced,
            PROC.modo_preparo,
            PROC.materiais,
            PROC.val_proced
        FROM 
            AGENDAMENTO AG
        INNER JOIN 
            PACIENTE P ON AG.ID_PACIENTE = P.ID_PACIENTE
        INNER JOIN 
            PROCEDIMENTO PROC ON AG.ID_PROCEDIMENTO = PROC.ID_PROCEDIMENTO
        WHERE 
            AG.ID_AGENDAMENTO = ?'
    );

    $stmt->execute([$id_agendamento]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        echo 'Agendamento não encontrado.';
        exit;
    }

    // Calcula a idade do paciente
    $data_nascimento = new DateTime($agendamento['data_nasc_pac']);
    $hoje = new DateTime();
    $idade = $data_nascimento->diff($hoje)->y;

    // Consulta para buscar o último valor cadastrado do exame TGO/TGP
    $stmt = $pdo->prepare('
    SELECT tgo_tgp.tgo_tgp 
    FROM tgo_tgp
    INNER JOIN EXAMES_E_AMOSTRAS E ON tgo_tgp.ID_EXAME = E.ID_EXAME
    WHERE E.ID_PROCEDIMENTO = ? AND E.ID_PACIENTE = ?
    ORDER BY tgo_tgp.id_resultado DESC
    LIMIT 1
    ');

    $stmt->execute([$agendamento['ID_PROCEDIMENTO'], $agendamento['ID_PACIENTE']]);
    $exame = $stmt->fetch();

    $valor_tgo_tgp = $exame['tgo_tgp'] ?? 'N/A'; // Caso não haja valor cadastrado

    // Consulta para buscar os valores de referência do exame TGO/TGP com DISTINCT por observações
    $stmt = $pdo->prepare('
    SELECT DISTINCT 
        valor_min, 
        valor_max, 
        unidade_medida, 
        observacoes
    FROM 
        referencia_hemograma
    WHERE 
        nome_parametro = "TGO/TGP"
    ');

    $stmt->execute();

    // Obter todos os resultados
    $referencias = $stmt->fetchAll();
} else {
    echo 'ID do agendamento não especificado.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Aprovação de Exame - TGO/TGP</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #2e7d32;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .info-table th {
            background: #2e7d32;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-size: 1.2em;
        }

        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .info-table ul {
            list-style: none;
            padding: 0;
        }

        .info-table li {
            margin: 5px 0;
        }

        .result-box {
            margin-top: 20px;
            text-align: center;
        }

        .result-box span {
            display: inline-block;
            font-size: 1.4em;
            font-weight: bold;
            color: #4caf50;
            background: #e8f5e9;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .links-retorno {
            margin-top: 20px;
            text-align: center;
        }

        a {
            text-decoration: none;
            color: #2e7d32;
            font-weight: bold;
            transition: color 0.3s;
        }

        a:hover {
            color: #1b5e20;
        }

        .result-box ul {
            margin-top: 10px;
        }

        .result-box li {
            margin-bottom: 5px;
            font-size: 1.1em;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Aprovação do Exame - TGO/TGP</h1>

        <table class="info-table">
            <tr>
                <th>Dados do Paciente</th>
                <th>Dados do Procedimento</th>
            </tr>
            <tr>
                <td>
                    <ul>
                        <li><strong>Nome:</strong> <?= htmlspecialchars($agendamento['nome_pac']) ?></li>
                        <li><strong>Convênio:</strong> <?= htmlspecialchars($agendamento['convenio']) ?></li>
                        <li><strong>Data de Nascimento:</strong> <?= date('d/m/Y', strtotime($agendamento['data_nasc_pac'])) ?></li>
                        <li><strong>Idade:</strong> <?= $idade ?> anos</li>
                        <li><strong>CPF:</strong> <?= htmlspecialchars($agendamento['cpf_pac']) ?></li>
                        <li><strong>Sexo:</strong> <?= htmlspecialchars($agendamento['sexo']) ?></li>
                    </ul>
                </td>
                <td>
                    <ul>
                        <li><strong>Procedimento:</strong> <?= htmlspecialchars($agendamento['nome_proced']) ?></li>
                        <li><strong>Descrição:</strong> <?= htmlspecialchars($agendamento['descr_proced']) ?></li>
                        <li><strong>Modo de Preparo:</strong> <?= htmlspecialchars($agendamento['modo_preparo']) ?></li>
                        <li><strong>Materiais:</strong> <?= htmlspecialchars($agendamento['materiais']) ?></li>
                        <li><strong>Valor:</strong> R$ <?= number_format($agendamento['val_proced'], 2, ',', '.') ?></li>
                    </ul>
                </td>
            </tr>
        </table>

        <div class="result-box">
            <h2>Resultado do Exame TGO/TGP</h2>
            <span><?= htmlspecialchars($valor_tgo_tgp) ?> <?= htmlspecialchars($referencias[0]['unidade_medida'] ?? 'N/A') ?></span>
        </div>

        <div class="result-box">
            <h3>Valores de Referência</h3>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($referencias as $referencia): ?>
                    <li><strong>Valor Mínimo:</strong> <?= htmlspecialchars($referencia['valor_min']) ?? '' ?> <?= htmlspecialchars($referencia['unidade_medida']) ?? '' ?></li>
                    <li><strong>Valor Máximo:</strong> <?= htmlspecialchars($referencia['valor_max']) ?? '' ?> <?= htmlspecialchars($referencia['unidade_medida']) ?? '' ?></li>
                    <li><strong>Observações:</strong> <?= htmlspecialchars($referencia['observacoes']) ?? '' ?></li>
                    <hr>
                <?php endforeach; ?>
            </ul>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <button onclick="emitirPDF()" style="
        background-color: #2e7d32;
        color: #fff;
        font-size: 1.1em;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    ">
                Aprovar e Emitir PDF
            </button>
        </div>

        <div class="links-retorno">
            <a href="aprovacao.php">Ir para a lista de aprovação</a>
        </div>
    </div>

</body>
<script>
    function emitirPDF() {
        // Emite a função de impressão
        window.print();
    }
</script>


</html>