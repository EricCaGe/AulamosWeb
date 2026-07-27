document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos los elementos
    const tabs = document.querySelectorAll('.tab-btn');
    const students = document.querySelectorAll('.student-card');
    const searchInput = document.querySelector('.student-search-input'); // Seleccionamos el buscador

    // Creamos una función maestra que filtra por PESTAÑA y por BÚSQUEDA al mismo tiempo
    function filterStudents() {
        // 1. Ver qué pestaña está activa actualmente
        const activeTab = document.querySelector('.tab-btn.active');
        const groupFilter = activeTab ? activeTab.getAttribute('data-filter') : 'todos';
        
        // 2. Ver qué texto escribió el usuario (lo pasamos a minúsculas para que no importe si usa mayúsculas)
        const searchTerm = searchInput.value.toLowerCase().trim();

        // 3. Revisar cada estudiante
        students.forEach(student => {
            const studentGroup = student.getAttribute('data-group');
            // Buscamos el nombre del estudiante (está dentro de la etiqueta <h4>)
            const studentName = student.querySelector('h4').textContent.toLowerCase();

            // Verificamos si cumple la condición del grupo
            const matchesGroup = (groupFilter === 'todos' || groupFilter === studentGroup);
            // Verificamos si el nombre incluye lo que se escribió en el buscador
            const matchesSearch = studentName.includes(searchTerm);

            // Si cumple AMBAS condiciones, lo mostramos. Si no, lo ocultamos.
            if (matchesGroup && matchesSearch) {
                student.style.display = 'flex';
            } else {
                student.style.display = 'none';
            }
        });
    }

    // EVENTO 1: Cuando hacemos clic en los botones de grupos
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Llamamos a la función maestra
            filterStudents(); 
        });
    });

    // EVENTO 2: Cuando escribimos en la barra de búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            // Cada vez que se teclea una letra, llamamos a la función maestra
            filterStudents();
        });
    }
});