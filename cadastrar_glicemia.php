<?php
require 'config.php';

// Habilitar a exibição de erros (opcional para depuração)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se o ID do agendamento foi passado
if (isset($_GET['id_agendamento'])) {
    $id_agendamento = $_GET['id_agendamento'];
    $nome_procedimento = isset($_GET['nome_procedimento']) ? $_GET['nome_procedimento'] : '';

    // Mapeia o procedimento para 'glicemia' caso seja 'Glicemia em Jejum'
    if ($nome_procedimento === 'Glicemia em Jejum') {
        $nome_procedimento = 'glicemia';
    }

    // Consulta para obter os dados do agendamento, paciente e procedimento, garantindo ser "Glicemia em Jejum"
    $stmt = $pdo->prepare('
        SELECT 
            AG.ID_AGENDAMENTO,
            AG.data_agendamento,
            AG.status_agendamento,
            AG.ID_PROCEDIMENTO,
            AG.id_profissional,
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
            AG.ID_AGENDAMENTO = ? AND PROC.nome_proced = ?
    ');
    $stmt->execute([$id_agendamento, 'Glicemia em Jejum']);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        echo 'Agendamento não encontrado ou procedimento incorreto.';
        exit;
    }

    // Calcula a idade do paciente
    $data_nascimento = new DateTime($agendamento['data_nasc_pac']);
    $hoje = new DateTime();
    $idade = $data_nascimento->diff($hoje)->y;

} else {
    echo 'ID do agendamento não especificado.';
    exit;
}

// Processa o formulário quando submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Dados do exame de glicemia
        $glicose = $_POST['glicose']; 

        if (empty($glicose)) {
            throw new Exception('Valor de glicose não fornecido.');
        }

        // Define a data e hora atuais
        $dt_realizado = date('Y-m-d H:i:s');

        // Inicia transação
        $pdo->beginTransaction();

        // Insere na tabela EXAMES_E_AMOSTRAS
        $stmt = $pdo->prepare('
            INSERT INTO EXAMES_E_AMOSTRAS (nome_exame, tipo, dt_realizado, ID_PACIENTE, ID_PROCEDIMENTO)
            VALUES (?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            'Glicemia em Jejum',
            1,
            $dt_realizado,
            $agendamento['ID_PACIENTE'],
            $agendamento['ID_PROCEDIMENTO']
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Falha ao inserir o exame.');
        }

        // Obtém o ID do exame recém-inserido
        $ID_EXAME = $pdo->lastInsertId();

        // Insere na tabela GLICEMIA
        $stmt = $pdo->prepare('
            INSERT INTO glicemia (ID_EXAME, glicose)
            VALUES (?, ?)
        ');
        $stmt->execute([
            $ID_EXAME,
            $glicose
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Falha ao registrar a glicose.');
        }

        // Atualiza o status do agendamento para 'Atendido'
        $stmt = $pdo->prepare("UPDATE AGENDAMENTO SET status_agendamento = 'Atendido' WHERE ID_AGENDAMENTO = ?");
        $stmt->execute([$id_agendamento]);

        // Confirma a transação
        $pdo->commit();

        // Redireciona para a agenda
        header('Location: agenda.php');
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Erro ao cadastrar o exame: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Amostra - Glicemia</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2e7d32;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table th {
            text-align: center;
            background: #2e7d32;
            color: #fff;
            padding: 10px;
            font-size: 1.2em;
        }

        .info-table td {
            vertical-align: top;
            padding: 8px;
            border-bottom: 1px solid #ccc;
            font-size: 0.95em;
        }

        .info-table strong {
            color: #2e7d32;
        }

        .info-table td ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-table td ul li {
            margin-bottom: 5px;
        }

        form {
            margin-top: 20px;
            text-align: center;
        }

        form h2 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.3em;
            color: #2e7d32;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.95em;
            color: #333;
        }

        input[type="number"] {
            width: 100px;
            padding: 5px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 0.9em;
            text-align: center;
        }

        button[type="submit"] {
            background: #4caf50;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button[type="submit"]:hover {
            background: #388e3c;
        }

        a {
            text-decoration: none;
            color: #2e7d32;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #1b5e20;
        }

        .links-retorno {
            text-align: center;
            margin-top: 20px;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastrar Exame - Glicemia</h1>
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
                        <li><strong>Data do Exame:</strong> <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></li>
                        <li><strong>Data de Nascimento:</strong> <?= date('d/m/Y', strtotime($agendamento['data_nasc_pac'])) ?></li>
                        <li><strong>Idade:</strong> <?= $idade ?> anos</li>
                        <li><strong>Sexo:</strong> <?= htmlspecialchars($agendamento['sexo']) ?></li>
                        <li><strong>CPF:</strong> <?= htmlspecialchars($agendamento['cpf_pac']) ?></li>
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

        <form method="post">
            <h2>Glicemia em Jejum</h2>
            <label for="glicose">Glicemia (mg/dL):</label>
            <input type="number" step="0.01" name="glicose" id="glicose" required>

            <br><br>
            <button type="submit">Cadastrar Glicemia</button>
        </form>
        
        <div class="links-retorno">
            <br>
            <a href="agenda.php">Voltar para a Agenda</a>
        </div>
    </div>
</body>
</html>
