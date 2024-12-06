<?php
require 'config.php';

// Consulta para obter os agendamentos
$stmt = $pdo->query('
    SELECT 
        AG.ID_AGENDAMENTO,
        AG.data_agendamento,
        AG.status_agendamento,
        AG.ID_PROCEDIMENTO,
        P.nome_pac AS nome_paciente,
        PROC.nome_proced AS nome_procedimento
    FROM 
        AGENDAMENTO AG
    INNER JOIN 
        PACIENTE P ON AG.ID_PACIENTE = P.ID_PACIENTE
    LEFT JOIN
        PROCEDIMENTO PROC ON AG.ID_PROCEDIMENTO = PROC.ID_PROCEDIMENTO
    ORDER BY 
        AG.data_agendamento ASC
');
$agendamentos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aprovação de Agendamentos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .btn-acao {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
        }
        .btn-aprovar {
            background-color: #28a745;
        }
        .btn-aprovar:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h1>Aprovação de Agendamentos</h1>
    <table>
        <tr>
            <th>Paciente</th>
            <th>Data Agendada</th>
            <th>Horário</th>
            <th>Procedimento</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($agendamentos as $agendamento): ?>
        <tr>
            <td><?= htmlspecialchars($agendamento['nome_paciente']) ?></td>
            <td><?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></td>
            <td><?= date('H:i', strtotime($agendamento['data_agendamento'])) ?></td>
            <td><?= htmlspecialchars($agendamento['nome_procedimento'] ?? 'Procedimento não definido') ?></td>
            <td>
                <a href="detalhes.php?id_agendamento=<?= $agendamento['ID_AGENDAMENTO'] ?>" class="btn-acao btn-aprovar">Aprovar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
