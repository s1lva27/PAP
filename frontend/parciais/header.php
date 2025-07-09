<!-- Header -->
<header>
    <div class="flex flex-1"><a href="index.php" class="logo">orange.</a></div>
    <div class="search-bar">
        <form action="pesquisar.php" method="GET">
            <i class="fas fa-search"></i>
            <input type="text" name="pesquisa" placeholder="Pesquise por pessoas e publicações..."
                value="<?php echo htmlspecialchars($_GET['pesquisa'] ?? ''); ?>">
            <button type="submit">Pesquisar</button>
        </form>
    </div>
    <nav class="user">


        <?php if (isset($_SESSION["id"]) && $_SESSION["id_tipos_utilizador"] == 2): ?>
            <a href="editar_utilizadores.php" class="gst-btn">Gestão</a>
        <?php endif; ?>

        <?php if (isset($_SESSION["id"])): ?>
            <a href="perfil.php"><?php echo htmlspecialchars($_SESSION['nick']); ?></a>
            <a href="../backend/logout.php">Sair</a>
        <?php else: ?>
            <a href="login.php" class="cta">Entrar</a>
            <a href="registar.php" class="cta">Criar Conta</a>
        <?php endif; ?>

    </nav>

</header>

<div class="header-offset">
    

</div>
<style>
    .logo {
        font-size: 2rem;
        font-weight: 700;
        color: var(--color-primary);
        text-shadow: 0 0 10px rgba(255, 87, 34, 0.3);
        letter-spacing: -0.5px;
        transition: transform var(--transition-normal);
    }

    .logo:hover {
        transform: scale(1.05);
    }

    .search-bar {
        flex: 0 1 500px;
        position: relative;
    }

    .search-bar input {
        width: 100%;
        padding: 0.8rem 1rem 0.8rem 3rem;
        border: 1px solid var(--border-light);
        border-radius: 30px;
        font-size: 0.95rem;
        background-color: var(--bg-input);
        color: var(--text-light);
        transition: all var(--transition-normal);
    }

    .search-bar input:focus {
        background-color: var(--bg-hover);
        border-color: var(--border-focus);
        outline: none;
        box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
    }

    .search-bar i {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
        flex: 1;
    }

    .search-bar button {
        display: none;
    }

    .user {
        display: flex;
        gap: var(--space-lg);
        margin-left: auto;
        flex: 1;
        justify-content: end;
    }


    .user a {
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-md);
        transition: all var(--transition-normal);
    }

    .user a:hover {
        background-color: var(--bg-hover);
    }

    .user a.cta {
        background-color: var(--color-primary);
        color: var(--text-light);
        padding: var(--space-sm) var(--space-lg);
        border-radius: 30px;
        font-weight: 500;
    }

    .user a.cta:hover {
        background-color: var(--color-primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    header {
        background-color: var(--bg-card);
        padding: 0 var(--space-xl);
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: var(--header-height);
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        box-shadow: var(--shadow-md);
        border-bottom: 1px solid var(--border-light);
    }
    .header-offset{
        height: var(--header-height);
    }
</style>