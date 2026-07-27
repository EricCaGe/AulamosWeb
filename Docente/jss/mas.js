document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const filterPills = document.querySelectorAll('.pill');
    const studentRows = document.querySelectorAll('.student-row');

    let currentFilter = 'Todos'; // Filtro por defecto

    // Función principal para filtrar estudiantes
    function filterStudents() {
        const searchTerm = searchInput.value.toLowerCase();

        studentRows.forEach(row => {
            // Obtenemos el nombre del estudiante y su grupo
            const studentName = row.querySelector('h4').textContent.toLowerCase();
            const studentGroup = row.getAttribute('data-group');
            
            // Verificamos si coincide con la búsqueda Y con el grupo seleccionado
            const matchesSearch = studentName.includes(searchTerm);
            const matchesGroup = currentFilter === 'Todos' || studentGroup === currentFilter;

            // Si cumple ambas condiciones, lo mostramos. Si no, lo ocultamos.
            if (matchesSearch && matchesGroup) {
                row.style.display = 'flex'; // Usamos flex para que no se rompa tu diseño
            } else {
                row.style.display = 'none';
            }
        });
    }

    // 1. Escuchar cuando escribes en la barra de búsqueda
    searchInput.addEventListener('input', filterStudents);

    // 2. Escuchar cuando haces clic en los botones (pills)
    filterPills.forEach(pill => {
        pill.addEventListener('click', function() {
            // Quitar la clase 'active' de todos los botones y ponérsela al que clickeaste
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            // Actualizar el filtro actual y ejecutar la función
            currentFilter = this.getAttribute('data-filter');
            filterStudents();
        });
    });
});