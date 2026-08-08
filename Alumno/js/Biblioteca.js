(function () {
    'use strict';

    // =============================================
    // ASISTENTE VIRTUAL
    // =============================================
    const btnAsistente =
        document.getElementById('btnAsistente');

    if (btnAsistente) {
        btnAsistente.addEventListener(
            'click',
            function () {
                alert(
                    'Asistente Virtual: ¿Qué recurso buscas?'
                );
            }
        );
    }

    // =============================================
    // CONSTRUIR URL DEL RECURSO
    //
    // Los archivos son servidos por Node
    // en el puerto 3000.
    //
    // Ejemplo:
    // /uploads/recursos/archivo.pdf
    //
    // se transforma en:
    // http://IP_DEL_SERVIDOR:3000/uploads/recursos/archivo.pdf
    // =============================================
    function construirUrlRecurso(ruta) {

        if (!ruta) {
            return null;
        }

        let rutaLimpia =
            String(ruta)
                .trim()
                .replace(/\\/g, '/');

        // Si ya es una URL completa,
        // se utiliza directamente.
        if (/^https?:\/\//i.test(rutaLimpia)) {
            return rutaLimpia;
        }

        // Compatibilidad temporal con
        // algunas rutas antiguas.
        rutaLimpia =
            rutaLimpia.replace(
                /^(\.\.\/)+/,
                '/'
            );

        if (!rutaLimpia.startsWith('/')) {
            rutaLimpia =
                '/' + rutaLimpia;
        }

        const protocolo =
            window.location.protocol === 'https:'
                ? 'https:'
                : 'http:';

        const servidor =
            window.location.hostname;

        return (
            protocolo +
            '//' +
            servidor +
            ':3000' +
            rutaLimpia
        );
    }

    // =============================================
    // ABRIR RECURSOS
    // =============================================
    const tarjetas =
        document.querySelectorAll(
            '.js-abrir-recurso'
        );

    tarjetas.forEach(function (tarjeta) {

        tarjeta.addEventListener(
            'click',
            function () {

                const ruta =
                    tarjeta.dataset.url;

                const titulo =
                    tarjeta.dataset.titulo ||
                    'recurso';

                const url =
                    construirUrlRecurso(ruta);

                if (!url) {
                    alert(
                        'Este recurso no tiene un archivo disponible.'
                    );

                    return;
                }

                console.log(
                    'Abriendo recurso:',
                    titulo,
                    url
                );

                window.open(
                    url,
                    '_blank',
                    'noopener,noreferrer'
                );
            }
        );
    });

})();