<?php
require 'config.php';

// Habilitar a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se o ID do agendamento foi passado
if (isset($_GET['id_agendamento'])) {
    $id_agendamento = $_GET['id_agendamento'];

    // Consulta para obter os dados do agendamento, paciente e procedimento de hemograma
    $stmt = $pdo->prepare('
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
            AG.ID_AGENDAMENTO = ? AND PROC.nome_proced = "Hemograma Completo"
    ');

    $stmt->execute([$id_agendamento]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        echo 'Agendamento não encontrado ou procedimento incorreto.';
        exit;
    }

    // Calcula a idade do paciente
    $data_nascimento = new DateTime($agendamento['data_nasc_pac']);
    $hoje = new DateTime();
    $idade = $data_nascimento->diff($hoje)->y;

    // Consulta para buscar os últimos resultados de hemograma
    $stmt = $pdo->prepare('
    SELECT 
        hemograma.hemacias, 
        hemograma.hemoglobina, 
        hemograma.hematocrito, 
        hemograma.vcm, 
        hemograma.hcm, 
        hemograma.chcm, 
        hemograma.rdw, 
        hemograma.leucocitos_totais, 
        hemograma.mielocitos, 
        hemograma.metamielocitos, 
        hemograma.bastonetes, 
        hemograma.segmentados, 
        hemograma.eosinofilos, 
        hemograma.basofilos, 
        hemograma.linfocitos_tipicos, 
        hemograma.linfocitos_atipicos, 
        hemograma.monocitos, 
        hemograma.blastos, 
        hemograma.plaquetas, 
        hemograma.vpm
    FROM 
        hemograma
    INNER JOIN 
        EXAMES_E_AMOSTRAS E ON hemograma.ID_EXAME = E.ID_EXAME
    WHERE 
        E.ID_PROCEDIMENTO = ? AND E.ID_PACIENTE = ?
    ORDER BY hemograma.ID_RESULTADO DESC
    LIMIT 1
');

    $stmt->execute([$agendamento['ID_PROCEDIMENTO'], $agendamento['ID_PACIENTE']]);
    $hemograma = $stmt->fetch();

    // Caso não haja resultados de hemograma
    if (!$hemograma) {
        echo 'Resultados de hemograma não encontrados.';
        exit;
    }

    // Consulta para obter os valores de referência do hemograma
    $stmt = $pdo->prepare('
        SELECT 
            valor_min, 
            valor_max, 
            unidade_medida, 
            observacoes, 
            nome_parametro
        FROM 
            referencia_hemograma
        WHERE 
            nome_parametro IN (
                "Hemácias", "Hemoglobina", "Hematócrito", "VCM", "HCM", "CHCM", "RDW", 
                "Leucócitos Totais", "Mielócitos", "Metamielócitos", "Bastonetes", "Segmentados", 
                "Eosinófilos", "Basófilos", "Linfócitos Típicos", "Linfócitos Atípicos", 
                "Monócitos", "Blastos", "Plaquetas", "VPM"
            )
    ');

    $stmt->execute();
    $referencias_hemograma = $stmt->fetchAll();
} else {
    echo 'ID do agendamento não especificado.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Aprovação de Exame - Hemograma</title>
    <style>
        /* O estilo permanece o mesmo da página de glicemia */
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
        <h1>Aprovação do Exame - Hemograma</h1>

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
            <h2>Resultado do Exame Hemograma</h2>
            <span><strong>Hemácias:</strong> <?= htmlspecialchars($hemograma['hemacias']) ?? '' ?> milhões/mm³</span>
            <span><strong>Hemoglobina:</strong> <?= htmlspecialchars($hemograma['hemoglobina']) ?? '' ?> g/dL</span>
            <span><strong>Hematócrito:</strong> <?= htmlspecialchars($hemograma['hematocrito']) ?? '' ?> %</span>
            <span><strong>VCM:</strong> <?= htmlspecialchars($hemograma['vcm']) ?? '' ?> fL</span>
            <span><strong>HCM:</strong> <?= htmlspecialchars($hemograma['hcm']) ?? '' ?> pg</span>
            <span><strong>CHCM:</strong> <?= htmlspecialchars($hemograma['chcm']) ?? '' ?> g/dL</span>
            <span><strong>RDW:</strong> <?= htmlspecialchars($hemograma['rdw']) ?? '' ?> %</span>
            <span><strong>Leucócitos Totais:</strong> <?= htmlspecialchars($hemograma['leucocitos_totais']) ?? '' ?> células/mm³</span>
            <span><strong>Mielócitos:</strong> <?= htmlspecialchars($hemograma['mielocitos']) ?? '' ?> células/mm³</span>
            <span><strong>Metamielócitos:</strong> <?= htmlspecialchars($hemograma['metamielocitos']) ?? '' ?> células/mm³</span>
            <span><strong>Bastonetes:</strong> <?= htmlspecialchars($hemograma['bastonetes']) ?? '' ?> %</span>
            <span><strong>Segmentados:</strong> <?= htmlspecialchars($hemograma['segmentados']) ?? '' ?> %</span>
            <span><strong>Eosinófilos:</strong> <?= htmlspecialchars($hemograma['eosinofilos']) ?? '' ?> %</span>
            <span><strong>Basófilos:</strong> <?= htmlspecialchars($hemograma['basofilos']) ?? '' ?> %</span>
            <span><strong>Linfócitos Típicos:</strong> <?= htmlspecialchars($hemograma['linfocitos_tipicos']) ?? '' ?> %</span>
            <span><strong>Linfócitos Atípicos:</strong> <?= htmlspecialchars($hemograma['linfocitos_atipicos']) ?? '' ?> %</span>
            <span><strong>Monócitos:</strong> <?= htmlspecialchars($hemograma['monocitos']) ?? '' ?> %</span>
            <span><strong>Blastos:</strong> <?= htmlspecialchars($hemograma['blastos']) ?? '' ?> células/mm³</span>
            <span><strong>Plaquetas:</strong> <?= htmlspecialchars($hemograma['plaquetas']) ?? '' ?> mil/mm³</span>
            <span><strong>VPM:</strong> <?= htmlspecialchars($hemograma['vpm']) ?? '' ?> fL</span>
        </div>

        <div class="result-box">
            <h3>Valores de Referência para Hemograma</h3>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($referencias_hemograma as $referencia): ?>
                    <li><strong>Parâmetro:</strong> <?= htmlspecialchars($referencia['nome_parametro']) ?></li>
                    <li><strong>Valor Mínimo:</strong> <?= htmlspecialchars($referencia['valor_min']) ?? '' ?> <?= htmlspecialchars($referencia['unidade_medida']) ?? '' ?></li>
                    <li><strong>Valor Máximo:</strong> <?= htmlspecialchars($referencia['valor_max']) ?? '' ?> <?= htmlspecialchars($referencia['unidade_medida']) ?? '' ?></li>
                    <li><strong>Observações:</strong> <?= htmlspecialchars($referencia['observacoes'] ?? '') ?></li>
                    <hr>
                <?php endforeach; ?>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <button onclick="emitirPDF()" style="background-color: #2e7d32; color: #fff; font-size: 1.1em; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);">
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