<?php
include_once("../backend/ligabd.php");
session_start();

$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$termo_sql = mysqli_real_escape_string($con, $termo);

$userId = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
$excludeSelf = $userId ? "AND u.id != $userId" : "";

$sqlPerfis = "SELECT u.id, u.nome_completo, u.nick, p.foto_perfil,
                    (SELECT COUNT(*) FROM seguidores WHERE id_seguido = u.id) AS seguidores,
                    (SELECT COUNT(*) FROM seguidores WHERE id_seguidor = u.id) AS a_seguir,
                    " . ($userId ? 
                        "(SELECT COUNT(*) FROM seguidores 
                          WHERE id_seguidor = $userId AND id_seguido = u.id) AS is_following" 
                        : "0 AS is_following") . "
             FROM utilizadores u
             JOIN perfis p ON u.id = p.id_utilizador
             WHERE (u.nome_completo LIKE '%$termo_sql%' 
                OR u.nick LIKE '%$termo_sql%' 
                OR p.biografia LIKE '%$termo_sql%')
                $excludeSelf
             LIMIT $offset, 3";

$resPerfis = mysqli_query($con, $sqlPerfis);

if (mysqli_num_rows($resPerfis) > 0) {
    while ($perfil = mysqli_fetch_assoc($resPerfis)) {
        ?>
        <div class="profile-card">
            <a href="perfil.php?id=<?php echo $perfil['id']; ?>" class="profile-link">
                <img src="images/perfil/<?php echo htmlspecialchars($perfil['foto_perfil']); ?>" class="profile-img">
                <div class="profile-info">
                    <h4><?php echo htmlspecialchars($perfil['nome_completo']); ?></h4>
                    <p>@<?php echo htmlspecialchars($perfil['nick']); ?></p>
                    <small>
                        <strong><?php echo $perfil['seguidores']; ?></strong> seguidores | 
                        <strong><?php echo $perfil['a_seguir']; ?></strong> a seguir
                    </small>
                </div>
            </a>
            <?php if ($userId && $userId != $perfil['id']) { ?>
                <button class="follow-btn <?php echo $perfil['is_following'] ? 'following' : ''; ?>" 
                        data-user-id="<?php echo $perfil['id']; ?>">
                    <?php echo $perfil['is_following'] ? 'Seguindo' : 'Seguir'; ?>
                </button>
            <?php } ?>
        </div>
        <?php
    }
} else {
    echo '';
}
?>