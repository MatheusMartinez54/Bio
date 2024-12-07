<?php
require 'config.php'; // Inclui a configuração do banco

// Verificar se o ID do agendamento foi enviado
if (!isset($_GET['id_agendamento'])) {
    die('Erro: ID do agendamento não fornecido.');
}

$id_agendamento = intval($_GET['id_agendamento']);

// Buscar os dados do agendamento no banco
$stmt = $pdo->prepare('
    SELECT 
        P.nome_pac AS nome_paciente,
        P.logra_pac AS endereco,
        P.tel_pac AS telefone,
        AG.data_agendamento,
        PROC.nome_proced AS nome_procedimento
    FROM 
        AGENDAMENTO AG
    INNER JOIN 
        PACIENTE P ON AG.ID_PACIENTE = P.ID_PACIENTE
    LEFT JOIN
        PROCEDIMENTO PROC ON AG.ID_PROCEDIMENTO = PROC.ID_PROCEDIMENTO
    WHERE AG.ID_AGENDAMENTO = ?
');
$stmt->execute([$id_agendamento]);
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    die('Erro: Agendamento não encontrado.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Agendamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .popup {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        button {
            margin: 10px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-confirm {
            background-color: #28a745;
            color: white;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <h1>Detalhes do Agendamento</h1>
    <p><strong>Nome:</strong> <?= htmlspecialchars($paciente['nome_paciente']) ?></p>
    <p><strong>Endereço:</strong> <?= htmlspecialchars($paciente['endereco']) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($paciente['telefone']) ?></p>
    <p><strong>Data Agendada:</strong> <?= date('d/m/Y', strtotime($paciente['data_agendamento'])) ?></p>
    <p><strong>Procedimento:</strong> <?= htmlspecialchars($paciente['nome_procedimento'] ?? 'Procedimento não definido') ?></p>

    <button onclick="showPopup()">Confirmar Aprovação</button>

    <div class="popup-overlay" id="popup-overlay">
        <div class="popup">
            <h2>Confirmar Aprovação</h2>
            <p>Deseja aprovar este agendamento?</p>
            <form method="POST" action="gerar_pdf.php">
                <input type="hidden" name="id_agendamento" value="<?= $id_agendamento ?>">
                <button type="submit" class="btn-confirm">Sim</button>
                <button type="button" class="btn-cancel" onclick="closePopup()">Não</button>
            </form>
        </div>
    </div>

    <script>
        function showPopup() {
            document.getElementById('popup-overlay').style.display = 'flex';
        }

        function closePopup() {
            document.getElementById('popup-overlay').style.display = 'none';
        }
    </script>
</body>
</html>
