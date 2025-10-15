# Migración Vue -> Symfony/Twig (Entrega)

Fecha: 2025-09-21T09:45:29.888204Z

## Estructura Entregada

- `src/Controller/` — HomeController, AuthController
- `src/Form/` — LoginType, RegisterType
- `templates/`
  - `base.html.twig`
  - `home/` — páginas migradas
  - `auth/` — login/register
  - `components/` — partials Twig generados desde componentes Vue
- `public/`
  - `css/` — **copiados** desde el proyecto Vue (estilos intactos)
  - `js/` — módulos vanilla JS generados por componente + `app.js`
  - `img/` — imágenes copiadas

## Rutas

Definidas en `config/routes.yaml`:

```yaml
home:
  path: /
  controller: App\Controller\HomeController::index

auth_login:
  path: /login
  controller: App\Controller\AuthController::login

auth_register:
  path: /register
  controller: App\Controller\AuthController::register

dashboard:
  path: /dashboard
  controller: App\Controller\HomeController::dashboard
```

## Instalación

```bash
composer install
php bin/console cache:clear
php bin/console assets:install public
symfony server:start
```

*(Usa tu servidor habitual si ya tienes uno configurado.)*

## Cómo se hizo el mapeo

Se analizaron los `.vue` y se generaron:
- **Twig** con la misma estructura HTML y clases CSS.  
- Se conservaron estilos originales copiando todos los `.css` a `public/css`.
- Atributos de Vue (`v-if`, `v-for`, `v-model`, `@click`, `:class`, etc.) se transformaron en **atributos `data-*`** para ser manejados por JS vanilla.

Tabla de mapeo (archivo -> plantilla/JS):

```json
[
  {
    "vue_file": "src/App.vue",
    "twig_path": "templates/components/app.html.twig",
    "component_name": "App",
    "js_module": "public/js/app.js",
    "classification": "components"
  },
  {
    "vue_file": "src/components/AppFooter.vue",
    "twig_path": "templates/components/appfooter.html.twig",
    "component_name": "AppFooter",
    "js_module": "public/js/appfooter.js",
    "classification": "components"
  },
  {
    "vue_file": "src/components/HelloWorld.vue",
    "twig_path": "templates/components/helloworld.html.twig",
    "component_name": "HelloWorld",
    "js_module": "public/js/helloworld.js",
    "classification": "components"
  },
  {
    "vue_file": "src/components/Home.vue",
    "twig_path": "templates/home/home.html.twig",
    "component_name": "Home",
    "js_module": "public/js/home.js",
    "classification": "home"
  },
  {
    "vue_file": "src/components/Homev2.vue",
    "twig_path": "templates/home/homev2.html.twig",
    "component_name": "Homev2",
    "js_module": "public/js/homev2.js",
    "classification": "home"
  },
  {
    "vue_file": "src/pages/index.vue",
    "twig_path": "templates/home/index.html.twig",
    "component_name": "index",
    "js_module": "public/js/index.js",
    "classification": "home"
  },
  {
    "vue_file": "src/pages/login.vue",
    "twig_path": "templates/auth/login.html.twig",
    "component_name": "login",
    "js_module": "public/js/login.js",
    "classification": "auth"
  }
]
```

## JavaScript Vanilla

Por cada componente se creó un módulo en `public/js/<componente>.js` con:
- Clase `State` para el estado.
- Delegación de eventos basada en `data-on-click="metodo"`.
- Lugar para portar lógica de `mounted/created`.

`public/js/app.js` carga automáticamente dichos módulos.

### Timers, efectos, animaciones

Si tu proyecto Vue contenía temporizadores o animaciones, añade su lógica dentro del módulo del componente correspondiente usando `requestAnimationFrame` o `setInterval`. Los atributos `data-*` colocados en el HTML sirven como *hooks*.

## Formularios

- Formularios complejos se representan con **Form Types** y validaciones server-side.  
- Mantén la misma UX con JS vanilla si tenías validaciones instantáneas en Vue.

## Sesiones

- Se usa `SessionInterface` en controladores (sustituye `sessionStorage`).  
- Ejemplo: se guarda `user` tras el login de prueba.

## Validaciones finales

- Twig lint: `php bin/console lint:twig templates/`
- Rutas: `php bin/console debug:router`
- PHPStan/Psalm (opcional) para tipos.

## Notas

- Donde había componentes Vue anidados se insertó la inclusión Twig: {{% include "components/<partial>.html.twig" %}}.
- Si algún `.vue` usaba directivas avanzadas o slots, revisa el módulo JS generado y ajusta los `data-*`.
