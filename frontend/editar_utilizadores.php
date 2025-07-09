<?php
session_start();

// Redireciona se não estiver logado ou se não for administrador
if (!isset($_SESSION["nick"]) || $_SESSION["id_tipos_utilizador"] != 2) {
    header("Location: index.php");
    exit();
}

require "../backend/ligabd.php";

// Paginação
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Pesquisa e ordenação
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$order = isset($_GET['order']) ? $_GET['order'] : 'a-z';

// Consulta para contar total de utilizadores
$sqlCount = "SELECT COUNT(*) AS total 
             FROM utilizadores 
             WHERE 1";

if (!empty($search)) {
    $sqlCount .= " AND (nome_completo LIKE '%$search%' 
                    OR nick LIKE '%$search%' 
                    OR email LIKE '%$search%')";
}

$resultCount = mysqli_query($con, $sqlCount);
$rowCount = mysqli_fetch_assoc($resultCount);
$totalUsers = $rowCount['total'];
$totalPages = ceil($totalUsers / $limit);

// Consulta para buscar os registos com paginação
$sql = "SELECT utilizadores.*, tipos_utilizador.tipo_utilizador 
        FROM utilizadores
        JOIN tipos_utilizador ON utilizadores.id_tipos_utilizador = tipos_utilizador.id_tipos_utilizador
        WHERE 1";

if (!empty($search)) {
    $sql .= " AND (utilizadores.nome_completo LIKE '%$search%' 
               OR utilizadores.nick LIKE '%$search%' 
               OR utilizadores.email LIKE '%$search%')";
}

// Definir a ordenação
switch ($order) {
    case 'z-a':
        $sql .= " ORDER BY utilizadores.nome_completo DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY utilizadores.id ASC";
        break;
    case 'newest':
        $sql .= " ORDER BY utilizadores.id DESC";
        break;
    default: // 'a-z' (padrão)
        $sql .= " ORDER BY utilizadores.nome_completo ASC";
        break;
}

$sql .= " LIMIT $offset, $limit";
$resultado = mysqli_query($con, $sql);

if (!$resultado) {
    $_SESSION["erro"] = "Não foi possível obter os dados dos utilizadores";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores - Orange</title>
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="icon" type="image/x-icon" href="images/favicon/favicon_orange.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .feedback-message {
            display: block;
            margin-top: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Adicione também estas classes para os estados de validação */
        .is-valid {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
        }

        .is-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }

        .management-container {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
        }

        .management-header {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-xl);
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .management-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: var(--space-sm);
        }

        .management-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .controls-section {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            margin-bottom: var(--space-xl);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-lg);
        }

        .controls-header h2 {
            margin: 0;
            color: var(--text-light);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .controls-header i {
            color: var(--color-primary);
        }

        .search-filters {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: var(--space-md);
            align-items: end;
        }

        .search-group {
            position: relative;
        }

        .search-group label {
            display: block;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: var(--space-xs);
            font-size: 0.9rem;
        }

        .search-input {
            width: 100%;
            padding: var(--space-md) var(--space-md) var(--space-md) 45px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--bg-input);
            color: var(--text-light);
            font-size: 1rem;
            transition: all var(--transition-normal);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
        }

        .search-group::before {
            content: "\f002";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 15px;
            bottom: 12px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: var(--space-xs);
            font-size: 0.9rem;
        }

        .filter-select {
            padding: var(--space-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--bg-input);
            color: var(--text-light);
            font-size: 1rem;
            min-width: 150px;
        }

        .apply-btn {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .apply-btn:hover {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .users-section {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .section-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-light);
            background: var(--bg-input);
        }

        .section-header h3 {
            margin: 0;
            color: var(--text-light);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .section-header i {
            color: var(--color-primary);
        }

        .add-user-form {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-light);
            background: rgba(255, 87, 34, 0.02);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: var(--space-xs);
            font-size: 0.9rem;
        }

        .form-input {
            padding: var(--space-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--bg-input);
            color: var(--text-light);
            font-size: 1rem;
            transition: all var(--transition-normal);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
        }

        .form-select {
            padding: var(--space-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--bg-input);
            color: var(--text-light);
            font-size: 1rem;
        }

        .add-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            justify-self: start;
        }

        .add-btn:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .users-table-container {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th,
        .users-table td {
            padding: var(--space-md);
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        .users-table th {
            background: var(--bg-input);
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .users-table td {
            color: var(--text-primary);
        }

        .users-table tr:hover {
            background: var(--bg-hover);
        }

        .table-input {
            width: 100%;
            padding: var(--space-sm);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            background: var(--bg-input);
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .table-input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .table-select {
            width: 100%;
            padding: var(--space-sm);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            background: var(--bg-input);
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: var(--space-sm);
        }

        .btn-save {
            background: #10b981;
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .btn-save:hover {
            background: #059669;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-delete:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
        }

        .user-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .user-type-admin {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .user-type-user {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-lg);
            background: var(--bg-input);
        }

        .pagination-btn {
            padding: var(--space-sm) var(--space-md);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            background: var(--bg-card);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition-normal);
            font-weight: 500;
        }

        .pagination-btn:hover {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .pagination-btn.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .alert {
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            text-align: center;
            transition: transform var(--transition-normal);
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-md);
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .stat-icon.admins {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-icon.recent {
            background: linear-gradient(135deg, #10b981, #047857);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: var(--space-xs);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .search-filters {
                grid-template-columns: 1fr;
                gap: var(--space-md);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .management-header h1 {
                font-size: 2rem;
            }
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: var(--space-xl);
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .modal-content h3 {
            margin: 0 0 var(--space-md);
            color: var(--text-light);
        }

        .modal-content p {
            margin: 0 0 var(--space-lg);
            color: var(--text-secondary);
        }

        .modal-buttons {
            display: flex;
            gap: var(--space-md);
            justify-content: center;
        }

        .modal-btn {
            padding: var(--space-sm) var(--space-lg);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .modal-btn-cancel {
            background: var(--bg-input);
            color: var(--text-secondary);
        }

        .modal-btn-confirm {
            background: #ef4444;
            color: white;
        }
    </style>
</head>

<body>
    <?php require "parciais/header.php" ?>

    <div class="container">
        <?php require("parciais/sidebar.php"); ?>

        <main class="management-container">
            <!-- Header -->
            <div class="management-header">
                <h1><i class="fas fa-users-cog"></i> Gestão de Utilizadores</h1>
                <p>Administre utilizadores, permissões e configurações do sistema</p>
            </div>

            <!-- Estatísticas -->
            <div class="stats-cards">
                <?php
                $totalUsersQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM utilizadores");
                $totalUsersCount = mysqli_fetch_assoc($totalUsersQuery)['total'];

                $totalAdminsQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM utilizadores WHERE id_tipos_utilizador = 2");
                $totalAdminsCount = mysqli_fetch_assoc($totalAdminsQuery)['total'];

                $recentUsersQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM utilizadores WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $recentUsersCount = mysqli_fetch_assoc($recentUsersQuery)['total'];
                ?>
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalUsersCount; ?></div>
                    <div class="stat-label">Total de Utilizadores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon admins">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalAdminsCount; ?></div>
                    <div class="stat-label">Administradores</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon recent">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-value"><?php echo $recentUsersCount; ?></div>
                    <div class="stat-label">Novos (30 dias)</div>
                </div>
            </div>

            <!-- Mensagens -->
            <?php if (isset($_SESSION["erro"])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $_SESSION["erro"];
                    unset($_SESSION["erro"]); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION["sucesso"])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION["sucesso"];
                    unset($_SESSION["sucesso"]); ?>
                </div>
            <?php endif; ?>

            <!-- Controles de Pesquisa -->
            <div class="controls-section">
                <div class="controls-header">
                    <h2><i class="fas fa-search"></i> Pesquisa e Filtros</h2>
                </div>
                <form method="GET" action="editar_utilizadores.php">
                    <div class="search-filters">
                        <div class="search-group">
                            <label for="search">Pesquisar utilizadores</label>
                            <input type="text" id="search" name="search" class="search-input"
                                placeholder="Nome, utilizador ou email..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="order">Ordenar por</label>
                            <select name="order" id="order" class="filter-select">
                                <option value="a-z" <?php echo $order === 'a-z' ? 'selected' : ''; ?>>Nome (A-Z)</option>
                                <option value="z-a" <?php echo $order === 'z-a' ? 'selected' : ''; ?>>Nome (Z-A)</option>
                                <option value="newest" <?php echo $order === 'newest' ? 'selected' : ''; ?>>Mais Recente
                                </option>
                                <option value="oldest" <?php echo $order === 'oldest' ? 'selected' : ''; ?>>Mais Antigo
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="apply-btn">
                            <i class="fas fa-filter"></i>
                            Aplicar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Gestão de Utilizadores -->
            <div class="users-section">
                <!-- Adicionar Utilizador -->
                <div class="section-header">
                    <h3><i class="fas fa-user-plus"></i> Adicionar Novo Utilizador</h3>
                </div>
                <div class="add-user-form">
                    <form id="addUserForm" action='../backend/gestao_utilizadores/inserir.php' method='post'>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nome_completo">Nome Completo</label>
                                <input type="text" id="nome_completo" name="nome_completo" class="form-input"
                                    placeholder="Nome completo do utilizador" required>
                                <small id="nome-feedback" class="feedback-message"></small>
                            </div>
                            <div class="form-group">
                                <label for="nick">Nome de Utilizador</label>
                                <input type="text" id="nick" name="nick" class="form-input"
                                    placeholder="Nome de utilizador único" required>
                                <small id="nick-feedback" class="feedback-message"></small>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-input"
                                    placeholder="email@exemplo.com" required>
                                <small id="email-feedback" class="feedback-message"></small>
                            </div>
                            <div class="form-group">
                                <label for="password">Palavra-passe</label>
                                <input type="password" id="password" name="password" class="form-input"
                                    placeholder="Palavra-passe segura" required>
                                <small id="password-feedback" class="feedback-message"></small>
                            </div>
                            <div class="form-group">
                                <label for="id_tipos_utilizador">Tipo de Utilizador</label>
                                <select name="id_tipos_utilizador" id="id_tipos_utilizador" class="form-select"
                                    required>
                                    <option value="0">Utilizador</option>
                                    <option value="2">Administrador</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="botaoInserir" class="add-btn">
                                    <i class="fas fa-plus"></i>
                                    Adicionar Utilizador
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Lista de Utilizadores -->
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Lista de Utilizadores</h3>
                </div>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nome Completo</th>
                                <th>Utilizador</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Nova Palavra-passe</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($resultado) == 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        <i class="fas fa-users"
                                            style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        Nenhum utilizador encontrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($registo = mysqli_fetch_array($resultado)): ?>
                                    <?php if ($registo["nick"] == "admin" && $_SESSION["nick"] != "admin")
                                        continue; ?>
                                    <tr>
                                        <form id='form<?php echo $registo["id"]; ?>' action='' method='post'
                                            onsubmit='return confirmAction(event)'>
                                            <input type="hidden" name='id' value='<?php echo $registo["id"]; ?>'>
                                            <td>
                                                <input type="text" name='nome_completo' class="table-input"
                                                    value='<?php echo htmlspecialchars($registo["nome_completo"]); ?>' readonly>
                                            </td>
                                            <td>
                                                <input type="text" name='nick' class="table-input"
                                                    value='<?php echo htmlspecialchars($registo["nick"]); ?>' readonly>
                                            </td>
                                            <td>
                                                <input type="email" name='email' class="table-input"
                                                    value='<?php echo htmlspecialchars($registo["email"]); ?>' readonly>
                                            </td>
                                            <td>
                                                <select name='id_tipos_utilizador' class="table-select">
                                                    <option value='0' <?php echo ($registo["id_tipos_utilizador"] == '0') ? "selected" : ""; ?>>
                                                        Utilizador
                                                    </option>
                                                    <option value='2' <?php echo ($registo["id_tipos_utilizador"] == '2') ? "selected" : ""; ?>>
                                                        Administrador
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="password" name='password' class="table-input"
                                                    placeholder="Nova palavra-passe (opcional)">
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="submit" name='botaoGravar' class="btn-save"
                                                        onclick="setAction('gravar', 'form<?php echo $registo["id"]; ?>')">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                    <button type="submit" name='botaoRemover' class="btn-delete"
                                                        onclick="setAction('remover', 'form<?php echo $registo["id"]; ?>')"
                                                        <?php echo ($registo["nick"] == "admin") ? "disabled" : ""; ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&order=<?php echo $order; ?>"
                                class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>

                        <span class="pagination-info">
                            Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                            (<?php echo $totalUsers; ?> utilizadores)
                        </span>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&order=<?php echo $order; ?>"
                                class="pagination-btn">
                                Próxima <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal de Confirmação -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="modal-content">
            <h3>Confirmar Ação</h3>
            <p id="confirmationMessage">Tem certeza que deseja realizar esta ação?</p>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeConfirmation()">
                    Cancelar
                </button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="confirmAction()">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Função para verificar disponibilidade do nick
        function checkNickAvailability() {
            const nick = document.getElementById('nick').value.trim();
            const nickInput = document.getElementById('nick');
            const nickFeedback = document.getElementById('nick-feedback');

            if (nick.length === 0) {
                nickInput.classList.remove('is-valid', 'is-invalid');
                nickFeedback.textContent = 'O nome de utilizador é obrigatório.';
                nickFeedback.style.color = '#ef4444';
                nickInput.classList.add('is-invalid');
                return;
            }

            if (nick.length < 3 || nick.length > 16) {
                nickInput.classList.remove('is-valid');
                nickInput.classList.add('is-invalid');
                nickFeedback.textContent = 'O nome de utilizador deve ter entre 3 e 16 caracteres.';
                nickFeedback.style.color = '#ef4444';
                return;
            }

            fetch(`../backend/gestao_utilizadores/verificar_nick.php?nick=${encodeURIComponent(nick)}`)
                .then(response => response.text())
                .then(data => {
                    if (data === 'exist') {
                        nickInput.classList.remove('is-valid');
                        nickInput.classList.add('is-invalid');
                        nickFeedback.textContent = 'Este nome de utilizador já está em uso.';
                        nickFeedback.style.color = '#ef4444';
                    } else {
                        nickInput.classList.remove('is-invalid');
                        nickInput.classList.add('is-valid');
                        nickFeedback.textContent = 'Nome de utilizador disponível.';
                        nickFeedback.style.color = '#10b981';
                    }
                });
        }

        // Função para verificar disponibilidade do email
        function checkEmailAvailability() {
            const email = document.getElementById('email').value.trim();
            const emailInput = document.getElementById('email');
            const emailFeedback = document.getElementById('email-feedback');

            if (email.length === 0) {
                emailInput.classList.remove('is-valid', 'is-invalid');
                emailFeedback.textContent = 'O email é obrigatório.';
                emailFeedback.style.color = '#ef4444';
                emailInput.classList.add('is-invalid');
                return;
            }

            if (!email.includes('@')) {
                emailInput.classList.remove('is-valid');
                emailInput.classList.add('is-invalid');
                emailFeedback.textContent = 'Por favor, insira um email válido.';
                emailFeedback.style.color = '#ef4444';
                return;
            }

            fetch(`../backend/gestao_utilizadores/verificar_email.php?email=${encodeURIComponent(email)}`)
                .then(response => response.text())
                .then(data => {
                    if (data === 'exist') {
                        emailInput.classList.remove('is-valid');
                        emailInput.classList.add('is-invalid');
                        emailFeedback.textContent = 'Este email já está registado.';
                        emailFeedback.style.color = '#ef4444';
                    } else {
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                        emailFeedback.textContent = 'Email disponível.';
                        emailFeedback.style.color = '#10b981';
                    }
                });
        }

        // Validação da password em tempo real
        function validatePassword() {
            const password = document.getElementById('password').value;
            const passwordInput = document.getElementById('password');
            const passwordFeedback = document.getElementById('password-feedback');

            if (password.length === 0) {
                passwordInput.classList.remove('is-valid', 'is-invalid');
                passwordFeedback.textContent = '';
                return;
            }

            if (password.length < 6) {
                passwordInput.classList.remove('is-valid');
                passwordInput.classList.add('is-invalid');
                passwordFeedback.textContent = 'A palavra-passe deve ter pelo menos 6 caracteres.';
                passwordFeedback.style.color = '#ef4444';
            } else {
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
                passwordFeedback.textContent = 'Palavra-passe válida.';
                passwordFeedback.style.color = '#10b981';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Verificar nick enquanto o usuário digita
            document.getElementById('nick').addEventListener('input', function () {
                const nick = this.value.trim();
                if (nick.length > 0 && (nick.length < 3 || nick.length > 16)) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('nick-feedback').textContent = 'O nome de utilizador deve ter entre 3 e 16 caracteres.';
                    document.getElementById('nick-feedback').style.color = '#ef4444';
                } else {
                    checkNickAvailability();
                }
            });

            // Verificar email enquanto o usuário digita
            document.getElementById('email').addEventListener('input', function () {
                const email = this.value.trim();
                if (email.length > 0 && !email.includes('@')) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('email-feedback').textContent = 'Por favor, insira um email válido.';
                    document.getElementById('email-feedback').style.color = '#ef4444';
                } else {
                    checkEmailAvailability();
                }
            });

            // Validar password enquanto o usuário digita
            document.getElementById('password').addEventListener('input', validatePassword);
        });

        // Adicionar eventos aos campos
        document.addEventListener('DOMContentLoaded', function () {
            // Verificar nick quando o utilizador sai do campo
            document.getElementById('nick').addEventListener('blur', checkNickAvailability);

            // Verificar email quando o utilizador sai do campo
            document.getElementById('email').addEventListener('blur', checkEmailAvailability);

            // Validar password enquanto o utilizador digita
            document.getElementById('password').addEventListener('input', validatePassword);

            // Atualizar validação do formulário de adição
            // Atualizar validação do formulário de adição
            document.getElementById('addUserForm').addEventListener('submit', function (e) {
                const nome = document.getElementById('nome_completo').value.trim();
                const nickInput = document.getElementById('nick');
                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');

                const nickFeedback = document.getElementById('nick-feedback');
                const emailFeedback = document.getElementById('email-feedback');
                const passwordFeedback = document.getElementById('password-feedback');

                let isValid = true;

                // Validar nome
                if (!nome || nome.length < 3) {
                    isValid = false;
                    alert('O nome completo deve ter pelo menos 3 caracteres.');
                }

                // Validar nick
                if (nickInput.classList.contains('is-invalid') || !nickInput.value.trim()) {
                    isValid = false;
                    if (!nickInput.value.trim()) {
                        alert('O nome de utilizador é obrigatório.');
                    } else {
                        alert('Por favor, escolha um nome de utilizador diferente.');
                    }
                }

                // Validar email
                if (emailInput.classList.contains('is-invalid') || !emailInput.value.trim()) {
                    isValid = false;
                    if (!emailInput.value.trim()) {
                        alert('O email é obrigatório.');
                    } else {
                        alert('Este email já está registado no sistema.');
                    }
                }

                // Validar password
                if (passwordInput.classList.contains('is-invalid') || !passwordInput.value.trim()) {
                    isValid = false;
                    if (!passwordInput.value.trim()) {
                        alert('A palavra-passe é obrigatória.');
                    } else {
                        alert('A palavra-passe deve ter pelo menos 6 caracteres.');
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Adicionar loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
                submitBtn.disabled = true;
            });
            let currentAction = '';
            let currentForm = '';

            function setAction(action, formId) {
                currentAction = action;
                currentForm = formId;

                const form = document.getElementById(formId);
                if (action === 'remover') {
                    form.action = '../backend/gestao_utilizadores/remover.php';
                } else if (action === 'gravar') {
                    form.action = '../backend/gestao_utilizadores/gravar.php';
                }
            }

            function confirmAction(event) {
                if (event) {
                    event.preventDefault();
                }

                if (currentAction === 'remover') {
                    document.getElementById('confirmationMessage').textContent =
                        'Tem certeza que deseja remover este utilizador? Esta ação não pode ser desfeita.';
                    document.getElementById('confirmationModal').style.display = 'flex';
                    return false;
                }

                return true;
            }

            function closeConfirmation() {
                document.getElementById('confirmationModal').style.display = 'none';
            }

            function confirmAction() {
                closeConfirmation();
                if (currentForm) {
                    document.getElementById(currentForm).submit();
                }
            }

            // Fechar modal ao clicar fora
            document.getElementById('confirmationModal').addEventListener('click', function (e) {
                if (e.target === this) {
                    closeConfirmation();
                }
            });

            // Validação do formulário de adicionar utilizador
            document.getElementById('addUserForm').addEventListener('submit', function (e) {
                const nome = document.getElementById('nome_completo').value.trim();
                const nick = document.getElementById('nick').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();

                if (!nome || !nick || !email || !password) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }

                if (nome.length < 3) {
                    e.preventDefault();
                    alert('O nome completo deve ter pelo menos 3 caracteres.');
                    return false;
                }

                if (nick.length < 3 || nick.length > 16) {
                    e.preventDefault();
                    alert('O nome de utilizador deve ter entre 3 e 16 caracteres.');
                    return false;
                }

                if (password.length < 6) {
                    e.preventDefault();
                    alert('A palavra-passe deve ter pelo menos 6 caracteres.');
                    return false;
                }

                // Adicionar loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
                submitBtn.disabled = true;
            });

            // Auto-hide alerts
            document.addEventListener('DOMContentLoaded', function () {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            alert.remove();
                        }, 300);
                    }, 5000);
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function (e) {
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    document.getElementById('search').focus();
                }
            });
    </script>


</html>