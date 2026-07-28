document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const activityRows = document.querySelectorAll('.activity-row');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Quitar activo de todos y poner al clickeado
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const filterValue = this.getAttribute('data-filter');

                    // Filtrar filas
                    activityRows.forEach(row => {
                        if (filterValue === 'todos' || row.getAttribute('data-status') === filterValue) {
                            row.style.display = 'flex';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });