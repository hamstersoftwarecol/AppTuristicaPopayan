# Product Backlog General — Plataforma Web de Turismo

(Versión 1.0) (Laravel + MVC) (Marketplace turístico)

Referencia funcional: [Turismocity Colombia](https://www.turismocity.com.co/) · Mercado inicial: Colombia, con posibilidad de expansión internacional

## 1. Objetivo del producto

Desarrollar una plataforma web integral de turismo donde los usuarios puedan buscar, comparar, cotizar, reservar y adquirir servicios turísticos desde una interfaz moderna, responsive e intuitiva.

La plataforma combinará un **metabuscador de viajes, un marketplace turístico y un sistema de administración**, conectado a proveedores externos mediante APIs.

Contemplará nueve módulos comerciales:

- Vuelos
- Hoteles
- Paquetes
- Asistencias
- Carros
- Actividades
- Buses
- eSIM
- Blog

## 2. Arquitectura tecnológica

| Componente        | Tecnología                                                           |
| ----------------- | -------------------------------------------------------------------- |
| Backend           | Laravel 13, PHP 8.3+                                                 |
| Arquitectura      | MVC + monolito modular                                               |
| Autenticación     | Breeze personalizado como referencia visual y funcional, con Fortify |
| Frontend          | Vue 3 + Inertia.js + TypeScript                                      |
| UI                | Tailwind CSS                                                         |
| Base de datos     | PostgreSQL                                                           |
| Roles y permisos  | Spatie Laravel Permission                                            |
| Autenticación API | Laravel Sanctum                                                      |
| Caché y colas     | Redis + Horizon                                                      |
| Integraciones     | REST APIs con adaptadores por proveedor                              |
| Notificaciones    | Email, WhatsApp y notificaciones internas                            |
| Almacenamiento    | S3 compatible                                                        |

**Nota:** Breeze fue reemplazado por los starter kits más recientes de Laravel. Se conservará su experiencia de autenticación personalizada, utilizando componentes y servicios compatibles con el framework actual.

## 3. Roles del sistema

| Rol                | Funciones principales                                    |
| ------------------ | -------------------------------------------------------- |
| Visitante          | Buscar, comparar, consultar promociones y blog           |
| Cliente            | Reservas, pagos, historial, favoritos y documentos       |
| Agente             | Cotizaciones, ventas asistidas y seguimiento             |
| Proveedor          | Administrar servicios, precios y disponibilidad propios  |
| Soporte            | Consultas, incidencias, cancelaciones y solicitudes      |
| Editor             | Blog, contenido, banners y SEO                           |
| Finanzas           | Pagos, reembolsos, comisiones y conciliación             |
| Administrador      | Gestión operativa de la plataforma                       |
| Superadministrador | Configuración general, integraciones y permisos globales |

## 4. Priorización del Product Backlog

P0 — Esencial (Primera versión)

Funcionalidades indispensables para lanzar el MVP.

---

P1 — Importante (Versión 1)

Ampliación comercial y operativa.

---

P2 — Evolutivo (Versión 2)

Optimización, inteligencia artificial y escalabilidad.

## 5. Product Backlog por épicas

Las historias están identificadas para facilitar su posterior creación como tareas en Jira, Trello, GitHub Projects o herramientas de desarrollo asistido por IA.

### Épica 01 — Configuración y arquitectura

| ID     | Historia / funcionalidad                                                  | Prioridad |
| ------ | ------------------------------------------------------------------------- | --------- |
| PB-001 | Crear proyecto Laravel y configurar arquitectura MVC modular              | P0        |
| PB-002 | Configurar PostgreSQL, migraciones, seeders y modelos Eloquent            | P0        |
| PB-003 | Configurar Redis, colas, eventos y sistema de caché                       | P0        |
| PB-004 | Configurar servicios, repositorios y controladores por módulos turísticos | P0        |
| PB-005 | Implementar entornos de desarrollo, pruebas y producción                  | P0        |

### Épica 02 — Interfaz pública y experiencia de usuario

| ID     | Historia / funcionalidad                                                             | Prioridad |
| ------ | ------------------------------------------------------------------------------------ | --------- |
| PB-006 | Crear página de inicio con buscador turístico por pestañas                           | P0        |
| PB-007 | Diseñar header, navegación, footer y menú móvil                                      | P0        |
| PB-008 | Implementar componentes interactivos: calendarios, filtros, selects y autocompletado | P0        |
| PB-009 | Implementar diseño responsive, skeleton loaders y estados de error/vacío             | P0        |
| PB-010 | Incorporar modo oscuro, microinteracciones y animaciones opcionales                  | P1        |

### Épica 03 — Autenticación y autorización

| ID     | Historia / funcionalidad                                                  | Prioridad |
| ------ | ------------------------------------------------------------------------- | --------- |
| PB-011 | Personalizar registro, inicio de sesión y cierre de sesión                | P0        |
| PB-012 | Verificar correo electrónico y recuperar contraseña                       | P0        |
| PB-013 | Implementar autenticación mediante Google y otros proveedores autorizados | P1        |
| PB-014 | Crear sistema de roles, permisos y políticas de acceso                    | P0        |
| PB-015 | Redirigir usuarios al dashboard correspondiente a su rol                  | P0        |
| PB-016 | Implementar doble factor de autenticación para perfiles privilegiados     | P0        |
| PB-017 | Gestionar sesiones, dispositivos y cierre de sesiones activas             | P1        |
| PB-018 | Implementar auditoría de accesos y operaciones sensibles                  | P0        |

### Épica 04 — Gestión de APIs y proveedores

| ID     | Historia / funcionalidad                                                        | Prioridad |
| ------ | ------------------------------------------------------------------------------- | --------- |
| PB-019 | Crear catálogo de proveedores y configuración de integraciones                  | P0        |
| PB-020 | Implementar conectores REST para APIs externas                                  | P0        |
| PB-021 | Normalizar resultados, monedas, disponibilidad y estructuras de precios         | P0        |
| PB-022 | Configurar límites, timeouts, reintentos controlados y estado de proveedores    | P0        |
| PB-023 | Habilitar afiliación/redirección o reserva directa según permisos del proveedor | P0        |
| PB-024 | Permitir múltiples proveedores para un mismo servicio y comparación avanzada    | P1        |

### Épica 05 — Tiquetes aéreos

| ID     | Historia / funcionalidad                                                         | Prioridad |
| ------ | -------------------------------------------------------------------------------- | --------- |
| PB-025 | Buscar vuelos por origen, destino, fechas, pasajeros y clase                     | P0        |
| PB-026 | Consultar y mostrar ofertas de una API de vuelos autorizada                      | P0        |
| PB-027 | Filtrar por aerolínea, escalas, duración, equipaje y precio                      | P0        |
| PB-028 | Mostrar detalle de vuelo, tarifa final y condiciones                             | P0        |
| PB-029 | Validar precios y gestionar redirección o emisión/reserva cuando esté habilitada | P0        |
| PB-030 | Incorporar multidestino, calendario flexible y alertas de precios                | P1        |

### Épica 06 — Hoteles y alojamientos

| ID     | Historia / funcionalidad                                                     | Prioridad |
| ------ | ---------------------------------------------------------------------------- | --------- |
| PB-031 | Buscar alojamiento por destino, fechas, habitaciones y huéspedes             | P0        |
| PB-032 | Consultar proveedores de inventario hotelero                                 | P0        |
| PB-033 | Filtrar por precio, ubicación, estrellas y servicios                         | P0        |
| PB-034 | Mostrar ficha de hotel con galería, mapa, habitaciones y políticas           | P0        |
| PB-035 | Comparar tarifas, verificar disponibilidad y habilitar reserva o redirección | P0        |

### Épica 07 — Paquetes turísticos

| ID     | Historia / funcionalidad                                              | Prioridad |
| ------ | --------------------------------------------------------------------- | --------- |
| PB-036 | Crear catálogo de paquetes turísticos prediseñados                    | P1        |
| PB-037 | Buscar paquetes por destino, duración, presupuesto y fechas           | P1        |
| PB-038 | Mostrar itinerarios, servicios incluidos, exclusiones y condiciones   | P1        |
| PB-039 | Construir paquetes dinámicos combinando vuelos, hoteles y adicionales | P2        |
| PB-040 | Administrar reservas compuestas y cambios parciales                   | P2        |

### Épica 08 — Asistencia al viajero

| ID     | Historia / funcionalidad                                                 | Prioridad |
| ------ | ------------------------------------------------------------------------ | --------- |
| PB-041 | Consultar planes de asistencia según destino, viajeros y duración        | P0        |
| PB-042 | Comparar coberturas, exclusiones y condiciones                           | P0        |
| PB-043 | Permitir contratación cuando exista integración y acuerdo comercial      | P0        |
| PB-044 | Consultar y entregar certificados o documentos emitidos por el proveedor | P1        |

### Épica 09 — Alquiler de carros

| ID     | Historia / funcionalidad                                         | Prioridad |
| ------ | ---------------------------------------------------------------- | --------- |
| PB-045 | Buscar vehículos por ciudad, recogida, devolución y fechas       | P1        |
| PB-046 | Comparar vehículos, categorías, seguros, depósitos y condiciones | P1        |
| PB-047 | Integrar proveedores y gestionar reserva o redirección           | P1        |
| PB-048 | Mostrar comprobante e instrucciones de recogida                  | P1        |

### Épica 10 — Actividades y experiencias

| ID     | Historia / funcionalidad                                  | Prioridad |
| ------ | --------------------------------------------------------- | --------- |
| PB-049 | Consultar actividades por destino, categoría y fecha      | P1        |
| PB-050 | Mostrar experiencias, fotos, itinerarios y disponibilidad | P1        |
| PB-051 | Gestionar selección de horarios, participantes y reservas | P1        |
| PB-052 | Generar o recuperar vouchers y códigos QR del proveedor   | P1        |

### Épica 11 — Pasajes de buses

| ID     | Historia / funcionalidad                                | Prioridad |
| ------ | ------------------------------------------------------- | --------- |
| PB-053 | Buscar rutas terrestres por origen, destino y fecha     | P1        |
| PB-054 | Consultar empresas, horarios, disponibilidad y tarifas  | P1        |
| PB-055 | Seleccionar servicio y asiento cuando la API lo permita | P1        |
| PB-056 | Gestionar reservas, comprobantes y políticas de cambios | P1        |

### Épica 12 — eSIM internacionales

| ID     | Historia / funcionalidad                                                   | Prioridad |
| ------ | -------------------------------------------------------------------------- | --------- |
| PB-057 | Consultar planes eSIM por país, región y cobertura                         | P0        |
| PB-058 | Comparar gigabytes, días, redes y precios                                  | P0        |
| PB-059 | Procesar compra o redirección a proveedor autorizado                       | P0        |
| PB-060 | Entregar QR o instrucciones de activación y gestionar recargas compatibles | P1        |

### Épica 13 — Carrito, checkout, pagos y reservas

| ID     | Historia / funcionalidad                                                                         | Prioridad |
| ------ | ------------------------------------------------------------------------------------------------ | --------- |
| PB-061 | Crear carrito persistente para ofertas elegibles para compra directa                             | P0        |
| PB-062 | Solicitar información de viajeros, huéspedes o compradores según producto                        | P0        |
| PB-063 | Revalidar precios, disponibilidad y políticas antes del pago                                     | P0        |
| PB-064 | Implementar pasarela de pagos compatible con Colombia: tarjetas, PSE y otros métodos habilitados | P0        |
| PB-065 | Gestionar estados de pago y reservas con confirmaciones seguras                                  | P0        |
| PB-066 | Evitar cobros y reservas duplicadas mediante idempotencia                                        | P0        |
| PB-067 | Implementar cancelaciones, reembolsos y conciliación                                             | P0        |
| PB-068 | Generar comprobantes, vouchers y notificaciones                                                  | P0        |

**Regla fundamental:** los productos de afiliación que redirigen al comprador no deben simular un pago o una reserva realizada dentro de la plataforma. El checkout propio únicamente estará disponible cuando los acuerdos comerciales y las APIs permitan efectuar la transacción.

### Épica 14 — Dashboards y operaciones

| ID     | Historia / funcionalidad                                                   | Prioridad |
| ------ | -------------------------------------------------------------------------- | --------- |
| PB-069 | Dashboard del cliente: perfil, viajeros, reservas y documentos             | P0        |
| PB-070 | Dashboard del administrador: usuarios, operaciones, reservas y proveedores | P0        |
| PB-071 | Dashboard del superadministrador: permisos y configuración global          | P0        |
| PB-072 | Dashboard de agentes: cotizaciones, clientes y ventas asistidas            | P1        |
| PB-073 | Dashboard de proveedores: inventario propio, tarifas y reservas            | P1        |
| PB-074 | Dashboard de soporte: incidencias, solicitudes y postventa                 | P1        |
| PB-075 | Dashboard financiero: pagos, liquidaciones, comisiones y reembolsos        | P1        |

### Épica 15 — Blog, marketing y comunicaciones

| ID     | Historia / funcionalidad                                                | Prioridad |
| ------ | ----------------------------------------------------------------------- | --------- |
| PB-076 | Crear blog público con destinos, consejos y promociones                 | P0        |
| PB-077 | Crear CMS para artículos, categorías, etiquetas e imágenes              | P0        |
| PB-078 | Administrar banners, ofertas y contenido destacado en la página inicial | P1        |
| PB-079 | Implementar favoritos, alertas de precios y promociones por correo      | P1        |
| PB-080 | Integrar mensajería de servicio mediante correo y WhatsApp autorizado   | P1        |
| PB-081 | Implementar asistente virtual de viajes y recomendaciones con IA        | P2        |

### Épica 16 — Seguridad, calidad y despliegue

| ID     | Historia / funcionalidad                                                          | Prioridad |
| ------ | --------------------------------------------------------------------------------- | --------- |
| PB-082 | Implementar políticas de privacidad, cookies y tratamiento de datos               | P0        |
| PB-083 | Validar requisitos regulatorios colombianos y habilitación comercial por producto | P0        |
| PB-084 | Implementar seguridad, cifrado, autorización y protección de APIs                 | P0        |
| PB-085 | Crear pruebas unitarias, integración y end-to-end                                 | P0        |
| PB-086 | Configurar backups, logs, métricas y monitoreo de errores                         | P0        |
| PB-087 | Configurar SEO técnico, analítica de tráfico y rendimiento                        | P0        |
| PB-088 | Implementar CI/CD y despliegue controlado de nuevas versiones                     | P0        |

## 6. Roadmap de implementación

**Fase 1 — MVP**

Fundación, autenticación, buscador público, vuelos, hoteles, asistencia, eSIM, blog básico, dashboard cliente/admin y transacciones autorizadas. El objetivo es validar búsquedas, conversiones y primeras ventas o derivaciones de afiliados.

---

**Fase 2 — Versión comercial completa**

Paquetes prediseñados, carros, actividades, buses, dashboards adicionales, soporte, promociones, alertas, más integraciones y reportes financieros.

---

**Fase 3 — Evolución y escalabilidad**

Paquetes dinámicos, asistente de IA, recomendaciones personalizadas, comparación multiproveedor avanzada, fidelización y servicios para agencias B2B.

Las fases son una secuencia propuesta, no fechas comprometidas. Las integraciones dependerán de la aprobación y contratación de proveedores externos.

## 7. Criterios generales de aceptación

Una historia de usuario podrá considerarse terminada cuando cumpla los siguientes criterios:

- **Funcionalidad:** completa el flujo solicitado, con validación de formularios y manejo de errores.
- **Seguridad:** valida permisos desde Laravel, no solo desde la interfaz.
- **Interacciones:** muestra estados de carga, éxito, error y ausencia de resultados.
- **Integración:** dispone de pruebas con respuestas exitosas, fallidas y tiempos de espera de proveedores.
- **Responsive:** funciona correctamente en escritorio, tablet y móvil.
- **Calidad:** cuenta con pruebas automatizadas relevantes y no presenta errores críticos conocidos.
- **Trazabilidad:** las acciones sensibles, cambios de reserva y operaciones de pago quedan registradas.

## 8. Flujo principal del producto

**Buscar servicio turístico**

Vuelos, hoteles, eSIM, actividades y demás categorías

**Consultar APIs y normalizar ofertas**

Disponibilidad, proveedores, condiciones y precios

**Comparar y seleccionar oferta**

Filtros interactivos y detalles de la tarifa

**Afiliación**

Redirigir al proveedor para finalizar la compra

**Reserva directa**

Validar precio, pagar y confirmar mediante API

**Seguimiento y postventa**

Historial, documentos, soporte y notificaciones según el tipo de compra

## 9. Resultado esperado

| Elemento                           | Total |
| ---------------------------------- | ----- |
| Épicas funcionales y técnicas      | 16    |
| Historias iniciales del backlog    | 88    |
| Módulos comerciales y de contenido | 9     |
| Roles de usuarios                  | 9     |

**Recomendación de desarrollo:** transformar estas 88 historias en tareas pequeñas, cada una con su controlador, modelo, vista/componente, migraciones cuando correspondan, permisos, pruebas y criterios de aceptación. El primer sprint debería construir la arquitectura MVC, las interfaces base, la autenticación personalizada y los roles, antes de integrar los servicios turísticos.

Este backlog constituye la base general del producto; todavía deberá refinarse con estimaciones, dependencias, historias de usuario formales y criterios de aceptación específicos antes de comenzar los sprints.
