# 📌 ProyectoFinalWeb – Sistema HelpDesk (Mesa de Ayuda)

Integrantes
Estudiantes: Enrique Fong 4-829-300 

Justinie Hernández 8-1014-1485 

Sebastián González 8-1009-418 

Alejandro Silva 20-70-7736 

## 📝 Descripción General

El **Sistema HelpDesk** es una aplicación web desarrollada en **PHP + MySQL** cuyo objetivo es centralizar, organizar y dar seguimiento a solicitudes de soporte técnico y requerimientos académicos dentro de una institución.  
El sistema permite a colaboradores o estudiantes registrar tickets de forma estructurada y al personal administrativo (administradores y agentes) gestionarlos durante todo su ciclo de vida.

El proyecto implementa una **arquitectura por roles**, separación de capas y buenas prácticas de desarrollo web, garantizando seguridad 🔒, escalabilidad 📈 y facilidad de mantenimiento.

---

## 🧱 Arquitectura del Sistema

El sistema está organizado de forma modular:

- **Capa de Presentación**
  - HTML5 + CSS
  - Formularios validados desde el cliente y servidor
- **Capa de Lógica**
  - PHP orientado a procesos
  - Manejo de sesiones
  - Control de roles y permisos
- **Capa de Datos**
  - Base de datos relacional MySQL
  - Uso de claves foráneas para integridad referencial

📂 Estructura principal del proyecto:
    /index.php → Login Admin / Agente
  /publico/ → Portal Colaboradores
  /admin/ → Panel Administrador
  /agente/ → Panel Agente
  /includes/ → Configuración, clases y utilidades
  /css/ → Estilos

  
---

## 🔐 Flujo de Acceso y Control de Roles

El sistema cuenta con **dos accesos completamente independientes**:

### 👨‍💼 Acceso Administrativo (Admin / Agente)
- Ruta: `/index.php`
- Autenticación basada en:
  - Usuario
  - Contraseña encriptada (`password_hash`)
- Redirección automática según rol:
  - `admin/`
  - `agente/`
- Funciones:
  - 📋 Gestión completa de tickets
  - 👤 Asignación de agentes
  - 🔄 Cambio de estados
  - ✅ Cierre con solución documentada

### 🎓 Acceso Público (Colaborador / Estudiante)
- Ruta: `/publico/index.php`
- Login exclusivo para colaboradores (sin selector de rol)
- Funciones:
  - 📝 Registro de usuario
  - 📨 Creación de tickets
  - 🔍 Consulta y seguimiento de tickets propios

Este diseño previene accesos indebidos y refuerza la seguridad del sistema.

---

## 👥 Gestión de Usuarios y Seguridad

- Contraseñas almacenadas usando **hash seguro** (`password_hash` / `password_verify`)
- Uso de **sesiones PHP** para autenticación
- Validaciones:
  - Lado cliente (HTML5)
  - Lado servidor (PHP)
- Sanitización de datos para prevenir:
  - Inyección SQL
  - XSS

---

## 📨 Gestión de Tickets

### 📌 Creación de Tickets

Cada ticket registrado incluye:
- 🏷️ Título
- 📝 Descripción
- 📂 Categoría (obligatoria)
- 📁 Subcategoría (obligatoria)
- 📅 Fecha de creación
- 🔄 Estado inicial: *En espera*

### 📂 Categorías Implementadas

El sistema maneja **únicamente dos categorías**, evitando ambigüedades:

#### 🛠️ Soporte
- 📧 Correo
- 🌐 Solicitudes de acceso a internet

#### 🎓 Académico
- 📄 Solicitudes de Créditos Oficiales
- 📝 Reclamo de nota

Las subcategorías se cargan dinámicamente según la categoría seleccionada, garantizando consistencia de datos.

---

## 🗃️ Modelo de Base de Datos (Resumen)

Principales tablas:
- `usuarios` → Admin / Agentes
- `colaboradores` → Estudiantes
- `tickets` → Información principal del ticket
- `categorias_ticket`
- `subcategorias_ticket`

Relaciones:
- Un ticket pertenece a **una categoría y una subcategoría**
- Un ticket puede ser asignado a **un agente**
- Integridad referencial mediante claves foráneas

---

## 🔍 Detalle y Seguimiento del Ticket

Cada ticket cuenta con una vista de detalle que muestra:
- ℹ️ Información general
- 📂 Categoría y subcategoría
- 🔄 Estado actual
- 📅 Fechas relevantes
- 👨‍💼 Agente asignado
- 📝 Comentario de cierre

Esto permite una **trazabilidad completa** del ticket desde su creación hasta su cierre.

---

## 🛠️ Panel Administrativo

El panel de administración y agentes permite:
- 📋 Visualización completa de tickets
- 🔍 Consulta detallada
- 👤 Asignación y autoasignación
- 🔄 Gestión de estados
- ✅ Cierre documentado

Este módulo optimiza la gestión del soporte y reduce tiempos de respuesta.

---

## 🎯 Objetivo del Proyecto

El Sistema HelpDesk busca:
- 📌 Centralizar solicitudes
- ⏱️ Reducir tiempos de atención
- 📊 Mejorar trazabilidad
- 🤝 Facilitar comunicación usuario–soporte
- 🧠 Aplicar conceptos de Ingeniería Web en un entorno real

---

## 🏁 Conclusión

El **Sistema HelpDesk** es una solución web robusta y bien estructurada que integra control de roles, seguridad, organización de datos y una interfaz intuitiva.  
El proyecto demuestra la aplicación práctica de tecnologías web, bases de datos relacionales y buenas prácticas de desarrollo, cumpliendo con los objetivos de un **proyecto final de Ingeniería Web** 🎓💻.

