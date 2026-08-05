const express = require('express');
const cors = require('cors');
const mysql = require('mysql2');

const app = express();
const PORT = 3000;

app.use(cors());
app.use(express.json());

// Ruta de prueba
app.get('/api/test', (req, res) => {
    res.json({ message: 'Servidor AULAMOS funcionando correctamente' });
});

// ========================================== */
// LOGIN - Ruta que espera tu frontend       */
// ========================================== */
app.post('/api/auth/login', (req, res) => {
    const { correo, password } = req.body;
    
    console.log('📩 Intento de login:', { correo, password });
    
    // Aquí va la lógica real de login
    // Por ahora, respuesta de prueba exitosa
    res.json({
        success: true,
        message: 'Login exitoso',
        usuario: {
            id: 1,
            nombre: 'Usuario Prueba',
            correo: correo,
            rol: 'Alumno'
        }
    });
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`🚀 Servidor corriendo en http://localhost:${PORT}`);
    console.log(`📡 API en http://localhost:${PORT}/api`);
});