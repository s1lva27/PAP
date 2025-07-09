const popup = document.getElementById('popup');
const closeButton = document.getElementById('closePopup');
const saveButton = document.getElementById('saveBtn');
const Warning = document.getElementById('warning-text');

const popupTitulo = document.getElementById('popup-titulo');
const popupDisplay = document.getElementById('popup-display');
const popupDisplayLabel = document.getElementById('popup-displayLabel');
const popupErro = document.getElementById('popup-erro');

function popupContent(tituloText, displayText, displayLabelText, placeholder){
    // Titulo
    popupTitulo.textContent = tituloText;
    
    // Display, placeholder
    popupDisplay.textContent = displayText;
    popupDisplay.placeholder = placeholder;

    // DisplayLabel
    popupDisplayLabel.textContent = displayLabelText;
}

function openPopup() {
    popup.classList.add('active');
    popupDisplay.focus();
    // Prevent background scrolling
    document.body.style.overflow = 'hidden';
    Warning.style.display = "none";
}

function closePopup() {
    popup.classList.remove('active');
    // Restore background scrolling
    document.body.style.overflow = '';
}

function handleSave() {
    const newName = popupDisplay.value.trim();
    if (newName) {
        // Aqui você pode adicionar a lógica para salvar o nome
        console.log('Nome salvo:', newName);
        closePopup();
        // Exemplo de feedback ao usuário
        // alert('Nome alterado com sucesso!');
    } else {
        // alert('Por favor, insira um nome válido.');
    }
}

// Event Listeners
closeButton.addEventListener('click', closePopup);
saveButton.addEventListener('click', handleSave);

// Fechar ao clicar fora do popup
popup.addEventListener('click', (e) => {
    if (e.target === popup) {
        closePopup();
    }
});

// Fechar com tecla ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && popup.classList.contains('active')) {
        closePopup();
    }
});

// Prevenir envio do formulário com Enter
popupDisplay.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleSave();
    }
});