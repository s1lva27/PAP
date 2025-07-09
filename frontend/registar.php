<?php
session_start();

// Captura as mensagens de erro, se existirem
$nameError = isset($_SESSION['erro']) && $_SESSION['erro'] == 'Nome de utilizador já registado.' ? 'Nome de utilizador já registado.' : '';
$emailError = isset($_SESSION['erro']) && $_SESSION['erro'] == 'Email já registado.' ? 'Email já registado.' : '';

// Limpar as mensagens de erro após capturá-las
unset($_SESSION['erro']);
?>


<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar</title>
    <link rel="stylesheet" href="css/style_registar.css">
    <link rel="icon" type="image/x-icon" href="images/favicon_orange.png">
</head>

<body>
    <header>
        <nav class="user"></nav>
    </header>

    <div class="signup-container">
        <img src="images/favicon/favicon_orange.png" alt="Orange Logo" class="logo">
        <h2>Criar Conta</h2>
        <form id="signupForm" action="../backend/registar.php" method="POST">

            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="name" id="name" placeholder="Nome" maxlength="50" required>
                    <p class="error-text" id="nameError">Nome deve ter pelo menos 3 caracteres.</p>
                </div>

                <div class="form-group">
                    <input type="email" id="email" name="email" required placeholder="E-mail">
                    <p class="error-text" id="emailError">Email já está em uso.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <input type="date" id="date" name="date" required min="1924-01-01" max="2011-12-31">
                    <p class="error-text" id="dateError"></p>
                </div>

                <div class="form-group">
                    <input type="text" name="nick" id="nick" placeholder="Nome de utilizador" maxlength="16" required>
                    <span class="info-icon"
                        data-tooltip="O seu nome de exibição deve ter entre 3 e 16 caracteres, e pode conter letras e números, além de traços, pontos, sublinhados e espaços não consecutivos.">
                        <svg xmlns="http://www.w3.org/2000/svg" height="35" width="35" fill="#757575"
                            viewBox="0 0 16 16">
                            <path
                                d="M8 1.5A6.5 6.5 0 1 0 8 14.5A6.5 6.5 0 0 0 8 1.5zm0 12A5.5 5.5 0 1 1 8 2.5A5.5 5.5 0 0 1 8 13.5zM7.75 5.5h.5a.25.25 0 0 1 .25.25v.5a.25.25 0 0 1-.25.25h-.5A.25.25 0 0 1 7.5 6.25v-.5a.25.25 0 0 1 .25-.25zM7.5 7h1v5h-1z" />
                        </svg></span>
                    <p class="error-text" id="nickError">Nome de utilizador já está em uso.</p>
                </div>

            </div>

            <div class="form-row">
                <div class="form-group">
                    <input type="password" id="password" name="password" required placeholder="Palavra-passe">
                </div>

                <div class="form-group">
                    <input type="password" id="confirm_password" name="confirm_password" required
                        placeholder="Confirmar palavra-passe">
                </div>
                
                <p id="passwordMismatch" style="font-size: 0.9em; margin-left: 323px;">As
                        palavras-passe não coincidem.</p>
            </div>

            <div class="form-group">
                <label for="termos">
                    <input type="checkbox" id="termos" name="condicoes" required>
                    Li e aceito a <a href="privacidade.html" target="_blank">Política de Privacidade</a> e os <a
                        href="termos.html" target="_blank">Termos e Condições</a>
                </label>
                <button type="submit" class="btn" id="submitBtn" name="submitBtn" disabled>Criar Conta</button>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2024 ORANGE. Todos os direitos reservados.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('signupForm');
        const submitBtn = document.getElementById('submitBtn');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const nameInput = document.getElementById('name');
        const nickInput = document.getElementById('nick');
        const emailInput = document.getElementById('email');
        const termsCheckbox = document.getElementById('termos');
        const requiredInputs = form.querySelectorAll('input[required]');
        const dateInput = document.getElementById('date');
        const dateError = document.getElementById('dateError');

        // Evento para impedir números na entrada do campo 'name'
        nameInput.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        });

        // Eventos para validações em tempo real
        passwordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
        nameInput.addEventListener('blur', validateName);
        nickInput.addEventListener('blur', validateNick);
        emailInput.addEventListener('blur', validateEmail);
        termsCheckbox.addEventListener('change', checkFormValidity);
        dateInput.addEventListener('blur', validateDate);

        requiredInputs.forEach(input => {
            input.addEventListener('input', checkFormValidity);
        });

        function capitalizeName(name) {
            return name
                .toLowerCase()
                .replace(/\s+/g, ' ') // Substitui múltiplos espaços por um único espaço
                .trim() // Remove espaços no início e fim
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        nameInput.addEventListener('blur', function () {
            nameInput.value = capitalizeName(nameInput.value.trim());
        });

        async function validateNick() {
            const nickError = document.getElementById('nickError');
            const response = await fetch(`../backend/check_nick.php?nick=${nickInput.value}`);
            const result = await response.text();
            if (result === 'exist') {
                nickError.style.color = 'red';
                nickInput.classList.add('incorreto');
            } else {
                nickError.style.color = 'transparent';
                nickInput.classList.remove('incorreto');
            }
        }

        async function validateEmail() {
            const emailError = document.getElementById('emailError');
            const response = await fetch(`../backend/check_email.php?email=${emailInput.value}`);
            const result = await response.text();
            if (result === 'exist') {
                emailError.style.color = 'red';
                emailInput.classList.add('incorreto');
            } else {
                emailError.style.color = 'transparent';
                emailInput.classList.remove('incorreto');
            }
        }

        function validatePasswords() {
            const mismatchError = document.getElementById('passwordMismatch');
            if (passwordInput.value !== confirmPasswordInput.value) {
                mismatchError.style.color = 'red';
                confirmPasswordInput.classList.add('passred');
            } else {
                mismatchError.style.color = 'transparent';
                confirmPasswordInput.classList.remove('passred');
            }
        }

        function validateName() {
            const nameError = document.getElementById('nameError');
            const trimmedValue = nameInput.value.trim();

            if (trimmedValue.length < 3 || /\d/.test(trimmedValue)) {
                nameError.style.color = 'red';
            } else {
                nameError.style.color = 'transparent';
            }

            if(trimmedValue == 0)
            {
                nameError.style.color = 'transparent';
            }

            // Atualiza a validade do formulário
            checkFormValidity();
        }

        function checkFormValidity() {
            let isValid = true;
            requiredInputs.forEach(input => {
                if (!input.value.trim() || input.classList.contains('incorreto')) {
                    isValid = false;
                }
            });

            if (passwordInput.value !== confirmPasswordInput.value || !termsCheckbox.checked) {
                isValid = false;
            }

            submitBtn.disabled = !isValid;
        }

        function validateDate() {
            const today = new Date();
            const selectedDate = new Date(dateInput.value);
            let age = today.getFullYear() - selectedDate.getFullYear();
            const monthDiff = today.getMonth() - selectedDate.getMonth();
            const dayDiff = today.getDate() - selectedDate.getDate();

            if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                age--; // Ajusta se o aniversário ainda não passou neste ano.
            }

            if (age < 13) {
                dateError.textContent = "Tens de ser maior de 13 anos.";
                dateError.style.color = 'red';
                dateInput.classList.add('incorreto');
            } else if (age > 115) {
                dateError.textContent = "Introduza uma data de nascimento real.";
                dateError.style.color = 'red';
                dateInput.classList.add('incorreto');
            } else {
                dateError.style.color = 'transparent';
                dateInput.classList.remove('incorreto');
            }

            // Usa checkFormValidity no lugar de toggleSubmitButton
            checkFormValidity();
        }
    });
</script>
</body>

</html>