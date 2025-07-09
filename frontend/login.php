<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orange - Iniciar Sessão</title>
    <link rel="stylesheet" href="css/style_login.css">
    <link rel="icon" type="image/x-icon" href="images/favicon_orange.png">
</head>

<body>
    <div class="container">
        <header>
            <h1>Iniciar Sessão</h1>
        </header>
        <form class="form-login" method="POST" action="../backend/login.php">
            <div class="form-group">
                <img src="images/favicon/favicon_orange.png" alt="Orange Logo" class="logo">
                <input type="text" id="username-email" name="primeiro_campo"
                    placeholder="Insira o seu nome de utilizador ou email">
                <p class="error-text" id="usernameEmailError" style="display: none;">Por favor, insira um nome de
                    utilizador ou email válido.</p>
            </div>

            <div class="form-group">
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Insira a sua palavra-passe">
                </div>
                <p class="error-text" id="passwordError" style="display: none;">Por favor, insira a sua palavra-passe.
                </p>
            </div>

            <?php if (isset($_SESSION["erro"])): ?>
                <p class="error-text" style="color: red;">
                    <?php echo $_SESSION["erro"]; ?>
                </p>
                <?php unset($_SESSION["erro"]); ?>
            <?php endif; ?>

            <button name="botaoLogin" type="submit" class="btn-primary">Iniciar Sessão</button>

            <div class="divider">
                <span>ou</span>
            </div>

            <button type="button" class="btn-google">
                <img src="https://www.zmax.work/wp-content/uploads/2021/05/x31__stroke.png" alt="Google"
                    class="google-icon"> Iniciar Sessão com Google
            </button>

            <p>Não tem conta? <a href="registar.php">Crie uma agora</a></p>
        </form>
    </div>

    <footer>
        &copy; 2024 ORANGE. Todos os direitos reservados.
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.form-login');
            const usernameEmailInput = document.getElementById('username-email');
            const passwordInput = document.getElementById('password');
            const usernameEmailError = document.getElementById('usernameEmailError');
            const passwordError = document.getElementById('passwordError');

            // Inicialmente, ocultar os erros
            usernameEmailError.style.display = 'none';
            passwordError.style.display = 'none';

            // Evento para validar os campos antes de submeter
            form.addEventListener('submit', function (event) {
                let hasError = false;

                // Verificar se o campo de utilizador/email está preenchido
                if (!usernameEmailInput.value.trim()) {
                    usernameEmailError.style.display = 'block';
                    hasError = true;
                } else {
                    usernameEmailError.style.display = 'none';
                }

                // Verificar se o campo de palavra-passe está preenchido
                if (!passwordInput.value.trim()) {
                    passwordError.style.display = 'block';
                    hasError = true;
                } else {
                    passwordError.style.display = 'none';
                }

                if (hasError) {
                    event.preventDefault(); // Impedir envio do formulário
                }
            });

            // Limpar mensagens de erro ao digitar
            usernameEmailInput.addEventListener('input', () => usernameEmailError.style.display = 'none');
            passwordInput.addEventListener('input', () => passwordError.style.display = 'none');
        });
    </script>
</body>

</html>