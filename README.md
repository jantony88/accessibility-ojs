# Zytek - Accesibilidad

Plugin genérico para **OJS 3.4** que añade al sitio público un panel de opciones de accesibilidad en
mosaicos y una **declaración de accesibilidad** con canal para reportar barreras.

**Compatibilidad:**
- Probado en OJS 3.4.0-10 con PHP 8.2, sobre una copia real con cinco revistas.
- Por usar la misma API, debería funcionar en OMP y OPS 3.4, pero no se ha probado.
- **No es compatible con OJS/OMP 3.3**: para 3.3 use [accessibility-omp](https://github.com/jantony88/accessibility-omp)
  (versión 1.x, misma funcionalidad).
- Las preferencias de los lectores son las mismas que en la versión 1.x (`localStorage`, clave `zytekA11y`).

**Descarga:** el paquete listo para subir a OJS está en
[Releases](../../releases): `zytekAccessibility-2.0.0.tar.gz`. El ZIP «Source code» que genera GitHub
no sirve para instalar desde el panel, porque su carpeta no se llama `zytekAccessibility`.

- Sin servicios de terceros ni cookies: las preferencias se guardan solo en el navegador del lector (`localStorage`).
- Sin cambios en el núcleo de OJS. Funciona con cualquier tema basado en la estructura de PKP
  (probado con el tema predeterminado de OJS 3.4).
- Textos en español e inglés.
- Objetivo de conformidad: WCAG 2.1 nivel AA, que es la referencia de la norma ecuatoriana NTE INEN-ISO/IEC 40500.

## El panel

El botón flotante (esquina inferior) o el atajo **Alt + Mayús + A** abre el panel. **Esc** lo cierra
y devuelve el foco al botón.

- **Cabecera:**
  - idioma del sitio, cuando la revista tiene más de uno;
  - ocultar el botón durante la visita (vuelve con Alt + Mayús + A);
  - cerrar.
- **Mosaicos en tres grupos** (Contenido, Colores, Navegación). Cada pulsación activa la herramienta;
  las que tienen varios pasos avanzan al siguiente y, tras el último, se apagan. El mosaico muestra el
  paso actual (p. ej. «150 %», «Oscuro») y unas marcas de progreso.
- **Restablecer todo.**
- **Pie:**
  - Declaración de accesibilidad;
  - Reportar un problema: correo con la dirección de la página ya escrita;
  - la marca Zytek, que se puede ocultar.

## Herramientas

| Grupo | Herramienta | Pasos | Para quién |
|---|---|---|---|
| Contenido | Texto más grande | 125 / 150 / 175 / 200 % | Baja visión, adultos mayores |
| | Espaciado de texto | — (valores de WCAG 1.4.12) | Dislexia, baja visión |
| | Fuente legible (Atkinson Hyperlegible) | — | Dislexia, baja visión |
| | Cursor grande | — | Baja visión, discapacidad motriz |
| | Información emergente | — | Discapacidad cognitiva, baja visión |
| | Ocultar imágenes | — | TDAH, discapacidad cognitiva |
| | Lectura en voz alta | Velocidad normal / lenta / rápida | Ceguera parcial, dislexia, discapacidad de aprendizaje |
| Colores | Contraste | Oscuro / Claro | Baja visión, sensibilidad a la luz |
| | Invertir colores | — | Baja visión, fotofobia |
| | Brillo | Menos / Más | Fotofobia, baja visión |
| | Escala de grises | — | Daltonismo, distracciones |
| | Saturación | Baja / Alta | Daltonismo, sensibilidad sensorial |
| | Resaltar enlaces | — | Daltonismo, discapacidad cognitiva |
| Navegación | Guía de lectura | — | TDAH, dislexia |
| | Modo lectura | — | Discapacidad cognitiva, TDAH |
| | Foco reforzado | — | Usuarios de teclado o pulsadores |
| | Detener animaciones | — | Epilepsia fotosensible, trastornos vestibulares |
| | Mapa de la página | (abre la lista) | Usuarios de teclado o lector de pantalla |

Detalles:

- **Texto más grande:**
  - reacomoda la página sin desplazamiento horizontal;
  - los títulos grandes crecen la mitad para no partir palabras;
  - funciona también con temas que fijan los tamaños en px.
- **Lectura en voz alta:** con la herramienta activa,
  - un clic en un párrafo, título o elemento de lista lo lee y lo marca;
  - el texto seleccionado se lee al soltar;
  - un reproductor junto al botón lee la página entera, con pausa y parada;
  - **Esc** detiene la lectura.
- **Información emergente:**
  - muestra en grande el texto alternativo de imágenes, iconos y botones al pasar el puntero o el foco;
  - se puede recorrer con el puntero y se cierra con **Esc** (WCAG 1.4.13).
- **Filtros de color** (invertir, brillo, saturación, escala de grises):
  - se combinan entre sí;
  - nunca afectan al panel;
  - al invertir, las fotos se mantienen como son.
- **Ocultar imágenes:** las imágenes conservan su texto alternativo para lectores de pantalla.

Las preferencias se aplican antes de dibujar la página (sin parpadeo) y las de la versión 1.0 se conservan.

El panel en sí:
- es un diálogo con nombre accesible;
- los mosaicos son botones con `aria-pressed` y cada cambio se anuncia;
- los objetivos táctiles miden al menos 44 px;
- en el móvil es una hoja inferior;
- sigue el tamaño de texto elegido hasta un 125 %.

## Declaración de accesibilidad

Se publica en `…/index.php/<revista>/accesibilidad`, una dirección que se puede configurar. Incluye:
- el compromiso (texto propio o el texto base);
- la lista de herramientas activas;
- la nota de privacidad;
- el correo para reportar barreras;
- la fecha de revisión.

## Ajustes (Ajustes > Sitio web > Plugins > Zytek - Accesibilidad > Configuración)

- **Herramientas:** activar o desactivar cada una (todas activas por defecto).
- **Botón:**
  - posición;
  - color: hexadecimal, y se valida que el texto blanco tenga contraste 4,5:1;
  - mostrar u ocultar la marca Zytek.
- **Declaración:**
  - publicar o no la declaración;
  - enlace en el pie de página;
  - dirección de la página;
  - correo de contacto (por defecto, el de la revista);
  - fecha de revisión;
  - texto propio, multilingüe.

## Instalación y actualización

**Desde el panel:**
- Instalación nueva: *Ajustes > Sitio web > Plugins > Cargar un plugin nuevo* con `zytekAccessibility-2.0.0.tar.gz`, y marque **Activo**.
- Actualización: en la fila del plugin, abra la flecha y pulse **Actualizar** con el paquete nuevo.

**Por consola**, si el panel no puede escribir en `plugins/`:

```
rm -rf plugins/generic/zytekAccessibility
tar -xzf zytekAccessibility-2.0.0.tar.gz -C plugins/generic/
chown -R www-data: plugins/generic/zytekAccessibility
php lib/pkp/tools/installPluginVersion.php plugins/generic/zytekAccessibility/version.xml
```

## Límites

Las herramientas complementan, pero no sustituyen, un sitio accesible ni las tecnologías de apoyo del
usuario (lectores de pantalla, ampliadores). Lo que el plugin no puede hacer por sí mismo:
- Los documentos (PDF, EPUB) y los videos deben producirse accesibles: PDF etiquetado, subtítulos y transcripciones.
- La lectura en voz alta depende de las voces instaladas en el dispositivo.
- El contenido incrustado de terceros queda fuera del alcance del plugin.

## Licencia

GNU GPL v3 (ver `LICENSE`). La fuente Atkinson Hyperlegible se distribuye con licencia SIL OFL 1.1
(`fonts/OFL-atkinson-hyperlegible.txt`). Los iconos son originales del plugin.
