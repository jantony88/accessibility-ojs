# Changelog – Zytek - Accesibilidad (OJS 3.4)

## 2.0.0.0 – 2026-10-25

Versión para **OJS 3.4**, con las mismas herramientas, ajustes y declaración que la 1.1.0 para OMP 3.3
([accessibility-omp](https://github.com/jantony88/accessibility-omp)).

- Clases con espacio de nombres (`APP\plugins\generic\zytekAccessibility`) y `Hook::add`.
- Textos en `locale/es` y `locale/en`, como pide OJS 3.4.
- La declaración se sirve con un handler propio en `LoadHandler`, sin constantes globales.
- Los textos que hablaban de «la editorial» ahora hablan de «la revista».
- Las preferencias guardadas por los lectores con la versión 1.x se conservan.
- Correcciones encontradas al probar sobre las revistas reales:
  - la lectura en voz alta ya no corta frases en números y direcciones con punto
    («WCAG 2.1», «OJS 3.4.0», «www.upec.edu.ec»); antes se leían como dos frases;
  - el enlace «Declaración de accesibilidad» se añade dentro del contenido del pie y, si el color
    del tema no contrasta con el fondo del pie (por ejemplo, una franja oscura), usa blanco o casi
    negro, el que cumpla 4,5:1;
  - con texto al 175 % y al 200 %, la barra lateral del tema predeterminado se coloca debajo del
    contenido (antes dejaba sitio para menos de una palabra);
  - los botones de descarga (PDF, EPUB, HTML) pasan a otra línea en lugar de salirse de la pantalla.
- Sin cambios en el núcleo de OJS.
