document.addEventListener('DOMContentLoaded', function() {
    const formConfig = document.getElementById('formConfiguracion');
    if (!formConfig) return;

    document.querySelectorAll('#formConfiguracion input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            formConfig.submit();
        });
    });

    const btnDark = document.getElementById('btn-darkmode');
    if (btnDark) {
        btnDark.addEventListener('click', function() {
            const radios = document.querySelectorAll('input[name="tema"]');
            let selected = document.querySelector('input[name="tema"]:checked');
            if (!selected) return;
            const nuevo = selected.value === 'oscuro' ? 'claro' : 'oscuro';
            document.querySelector('input[name="tema"][value="' + nuevo + '"]').checked = true;
            formConfig.submit();
        });
    }

    const btnContrast = document.getElementById('btn-contrast');
    if (btnContrast) {
        btnContrast.addEventListener('click', function() {
            const radios = document.querySelectorAll('input[name="alto_contraste"]');
            let selected = document.querySelector('input[name="alto_contraste"]:checked');
            if (!selected) return;
            const nuevo = selected.value === '1' ? '0' : '1';
            document.querySelector('input[name="alto_contraste"][value="' + nuevo + '"]').checked = true;
            formConfig.submit();
        });
    }

    const btnTextSize = document.getElementById('btn-text-size');
    if (btnTextSize) {
        btnTextSize.addEventListener('click', function() {
            const opciones = ['pequeño', 'normal', 'grande'];
            let selected = document.querySelector('input[name="tamano_texto"]:checked');
            if (!selected) return;
            let idx = opciones.indexOf(selected.value);
            let nuevo = opciones[(idx + 1) % opciones.length];
            document.querySelector('input[name="tamano_texto"][value="' + nuevo + '"]').checked = true;
            formConfig.submit();
        });
    }

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