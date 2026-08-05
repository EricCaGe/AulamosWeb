document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos los elementos
    const tabs = document.querySelectorAll('.tab-btn');
    const students = document.querySelectorAll('.student-card');
    const searchInput = document.querySelector('.student-search-input');

    // Función para filtrar estudiantes por pestaña y búsqueda
    function filterStudents() {
        const activeTab = document.querySelector('.tab-btn.active');
        const groupFilter = activeTab ? activeTab.getAttribute('data-filter') : 'todos';
        const searchTerm = searchInput.value.toLowerCase().trim();

        let visibleStudents = 0;

        students.forEach(student => {
            const studentGroup = student.getAttribute('data-group');
            const studentName = student.querySelector('h4').textContent.toLowerCase();

            const matchesGroup = (groupFilter === 'todos' || groupFilter === studentGroup);
            const matchesSearch = studentName.includes(searchTerm);

            if (matchesGroup && matchesSearch) {
                student.style.display = 'flex';
                visibleStudents++;
            } else {
                student.style.display = 'none';
            }
        });

        // Mostrar mensaje si no hay estudiantes visibles
        const noStudentsMessage = document.querySelector('.no-students-message');
        if (noStudentsMessage) {
            noStudentsMessage.style.display = (visibleStudents === 0) ? 'flex' : 'none';
        }
    }

    // Evento para los botones de grupos
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            filterStudents();
        });
    });

    // Evento para la barra de búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', filterStudents);
    }

    // Filtrar al cargar la página
    filterStudents();
});