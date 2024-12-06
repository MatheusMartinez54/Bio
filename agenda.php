<?php
require 'config.php';

// Consulta para obter os agendamentos com data, hora e nome do procedimento
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
    <title>Agenda de Agendamentos</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9); /* Degradê suave de verde */
            color: #333;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
            font-size: 2.2em;
            color: #2e7d32; /* Verde escuro */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sutil sombra */
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background: #2e7d32; /* Verde escuro */
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:nth-child(odd) {
            background: #ffffff;
        }

        tr:hover {
            background-color: #c8e6c9; /* Destaque suave em verde */
            transition: background-color 0.3s ease;
        }

        .btn-atender {
            display: inline-block;
            background: #4caf50; /* Verde principal */
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-atender:hover {
            background: #388e3c; /* Verde mais escuro */
            transform: scale(1.05); /* Pequeno aumento ao passar o mouse */
        }

        .status-atendido {
            color: #9e9e9e; /* Cinza elegante para status atendido */
            font-style: italic;
        }

        footer {
            text-align: center;
            margin: 20px 0;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>Exames Agendados</h1>
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
                <?php if ($agendamento['status_agendamento'] != 'Atendido'): ?>
                    <?php if ($agendamento['nome_procedimento'] === 'Glicemia em Jejum'): ?>
                        <a href="cadastrar_glicemia.php?id_agendamento=<?= $agendamento['ID_AGENDAMENTO'] ?>" class="btn-atender">Atender Glicemia</a>
                    <?php elseif ($agendamento['nome_procedimento'] === 'TGO/TGP'): ?>
                        <a href="cadastrar_tgo_tgp.php?id_agendamento=<?= $agendamento['ID_AGENDAMENTO'] ?>" class="btn-atender">Atender TGO/TGP</a>
                    <?php else: ?>
                        <a href="cadastrar_amostra.php?id_agendamento=<?= $agendamento['ID_AGENDAMENTO'] ?>&nome_procedimento=<?= urlencode($agendamento['nome_procedimento']) ?>" class="btn-atender">Atender</a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="status-atendido">Atendido</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <footer>
        &copy; <?= date('Y') ?> Biomedicina - FASICLIN. Todos os direitos reservados.
    </footer>
</body>
</html>
