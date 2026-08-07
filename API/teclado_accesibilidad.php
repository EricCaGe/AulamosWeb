<!-- 1. CSS del teclado virtual -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-keyboard@3.7.79/build/css/index.css">

<!-- 2. El contenedor del teclado virtual (Ahora existe antes de que el JS lo busque) -->
<div id="virtual-keyboard-container" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #f8fafc; z-index: 9999; padding: 10px 0 20px 0; box-shadow: 0 -4px 12px rgba(0,0,0,0.15); border-top: 2px solid #3b82f6;">
    <div id="simpleKeyboard" class="simple-keyboard"></div>
</div>

<!-- 3. JS de la librería del teclado -->
 <script>
// Inyectamos el CSS directamente para que no dependa de internet
var style = document.createElement('style');
style.innerHTML = `
    #virtual-keyboard-container {
        display: none; position: fixed; bottom: 0; left: 0; width: 100%;
        background-color: #e9ecef; z-index: 10000; padding: 10px 0 20px 0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.1); border-top: 2px solid #3b82f6;
    }
    .hg-row { display: flex; justify-content: center; gap: 4px; margin-bottom: 4px; }
    .hg-button {
        background-color: #ffffff; border: 1px solid #ced4da; border-radius: 6px;
        font-size: 1rem; color: #212529; height: 44px; min-width: 36px;
        padding: 0 8px; cursor: pointer; display: flex; align-items: center;
        justify-content: center; transition: background 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .hg-button:hover { background-color: #f8f9fa; border-color: #adb5bd; }
    .hg-button-special { min-width: 60px; background-color: #f1f3f5; }
    body.modo-oscuro #virtual-keyboard-container { background-color: #1e1e32; border-top: 2px solid #4f46e5; }
    body.modo-oscuro .hg-button { background-color: #2d2d44; border-color: #4a4a6a; color: #e2e8f0; }
    body.modo-oscuro .hg-button:hover { background-color: #3d3d5a; }
`;
document.head.appendChild(style);

// RESPALDO: Dibuja el teclado si la librería externa falló
if (typeof SimpleKeyboard === 'undefined') {
    window.SimpleKeyboard = function(config) {
        var container = document.querySelector('.simple-keyboard');
        if (!container) return;

        var layout = ['1 2 3 4 5 6 7 8 9 0', 'q w e r t y u i o p', 'a s d f g h j k l ñ', 'z x c v b n m', '{bksp} {space} {enter}'];
        
        function renderKeyboard() {
            var html = '';
            layout.forEach(function(row) {
                html += '<div class="hg-row">';
                var keys = row.split(' ');
                keys.forEach(function(key) {
                    var special = (key === '{bksp}' || key === '{space}' || key === '{enter}') ? 'hg-button-special' : '';
                    var displayKey = key;
                    if(key === '{bksp}') displayKey = '⌫';
                    if(key === '{space}') displayKey = 'Espacio';
                    if(key === '{enter}') displayKey = '↵ Enter';
                    html += `<button class="hg-button ${special}" data-key="${key}">${displayKey}</button>`;
                });
                html += '</div>';
            });
            container.innerHTML = html;

            container.querySelectorAll('.hg-button').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var key = this.dataset.key;
                    var input = document.activeElement;
                    
                    if (key === '{bksp}') {
                        if(input) { input.value = input.value.slice(0, -1); input.dispatchEvent(new Event('input', { bubbles: true })); }
                    } else if (key === '{space}') {
                        if(input) { input.value += ' '; input.dispatchEvent(new Event('input', { bubbles: true })); }
                    } else if (key === '{enter}') {
                        console.log("Tecla Enter presionada");
                    } else {
                        if(input && (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA')) {
                            input.value += key;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                });
            });
        }
        renderKeyboard();
    };
}
</script>

<!-- El contenedor donde se dibujará el teclado -->
<div id="virtual-keyboard-container">
    <div class="simple-keyboard"></div>
</div>
