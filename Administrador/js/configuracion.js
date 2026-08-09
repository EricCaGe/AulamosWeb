document.addEventListener('DOMContentLoaded', function() {
    const formConfig = document.getElementById('formConfiguracion');
    if (!formConfig) return;

    // ========================================== */
    // 1. RADIO BUTTONS - ENVÍO AUTOMÁTICO       */
    // ========================================== */
    document.querySelectorAll('#formConfiguracion input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            formConfig.submit();
        });
    });

    // ========================================== */
    // 2. BOTÓN IDIOMA (HEADER)                  */
    // ========================================== */
    const btnIdioma = document.getElementById('btnIdioma');
    if (btnIdioma) {
        btnIdioma.addEventListener('click', function() {
            let selected = document.querySelector('input[name="idioma"]:checked');
            if (!selected) return;
            const nuevo = selected.value === 'es' ? 'en' : 'es';
            document.querySelector('input[name="idioma"][value="' + nuevo + '"]').checked = true;
            document.getElementById('idiomaTexto').textContent = nuevo.toUpperCase();
            formConfig.submit();
        });
    }
});