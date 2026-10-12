<?php
ob_start();
register_shutdown_function(function () {
    $salida = ob_get_clean();
    header('Content-Length: ' . strlen($salida));
    echo $salida;
});
ini_set("display_errors", "0");
error_reporting(E_ALL);
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'usuario_save') {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $data = [
                postText('nombre', 100),
                filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL),
                trim((string) ($_POST['telefono'] ?? '')),
                isset($_POST['activo']) ? 1 : 0,
            ];
            if (!$data[1] || mb_strlen($data[2]) > 20) {
                throw new InvalidArgumentException('El correo o el telefono no son validos.');
            }
            if ($id > 0) {
                $statement = $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, activo = ? WHERE id_usuario = ?');
                $statement->execute(array_merge($data, [$id]));
                flash('ok', 'Usuario actualizado.');
            } else {
                $statement = $pdo->prepare('INSERT INTO usuarios (nombre, email, telefono, activo) VALUES (?, ?, ?, ?)');
                $statement->execute($data);
                flash('ok', 'Usuario creado.');
            }
            redirect('usuarios');
        }

        if ($action === 'producto_save') {
            $id = (int) ($_POST['id_producto'] ?? 0);
            $data = [
                postText('nombre', 150),
                trim((string) ($_POST['descripcion'] ?? '')),
                postDecimal('precio'),
                postInt('stock', 0),
                trim((string) ($_POST['imagen'] ?? '')),
                isset($_POST['activo']) ? 1 : 0,
            ];
            if (mb_strlen($data[1]) > 5000 || mb_strlen($data[4]) > 255) {
                throw new InvalidArgumentException('La descripcion o imagen supera su limite.');
            }
            if ($id > 0) {
                $statement = $pdo->prepare('UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, activo = ? WHERE id_producto = ?');
                $statement->execute(array_merge($data, [$id]));
                flash('ok', 'Producto actualizado.');
            } else {
                $statement = $pdo->prepare('INSERT INTO productos (nombre, descripcion, precio, stock, imagen, activo) VALUES (?, ?, ?, ?, ?, ?)');
                $statement->execute($data);
                flash('ok', 'Producto creado.');
            }
            redirect('productos');
        }

        if ($action === 'venta_save') {
            $id = (int) ($_POST['id_venta'] ?? 0);
            $data = [
                postInt('id_usuario'),
                postInt('id_producto'),
                postInt('cantidad'),
                postDecimal('precio_unit'),
                postDecimal('tasa_impuesto'),
            ];
            if ($data[4] > 100) {
                throw new InvalidArgumentException('La tasa de impuesto debe estar entre 0 y 100.');
            }
            if ($id > 0) {
                $statement = $pdo->prepare('UPDATE ventas SET id_usuario = ?, id_producto = ?, cantidad = ?, precio_unit = ?, tasa_impuesto = ? WHERE id_venta = ?');
                $statement->execute(array_merge($data, [$id]));
                flash('ok', 'Venta actualizada.');
            } else {
                $statement = $pdo->prepare('INSERT INTO ventas (id_usuario, id_producto, cantidad, precio_unit, tasa_impuesto) VALUES (?, ?, ?, ?, ?)');
                $statement->execute($data);
                flash('ok', 'Venta creada. Los totales fueron calculados por MySQL.');
            }
            redirect('ventas');
        }

        if (in_array($action, ['usuario_delete', 'producto_delete', 'venta_delete'], true)) {
            $id = postInt('id');
            $tables = [
                'usuario_delete' => ['usuarios', 'id_usuario'],
                'producto_delete' => ['productos', 'id_producto'],
                'venta_delete' => ['ventas', 'id_venta'],
            ];
            [$table, $column] = $tables[$action];
            $statement = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = ?");
            $statement->execute([$id]);
            flash('ok', 'Registro eliminado.');
            redirect($action === 'usuario_delete' ? 'usuarios' : ($action === 'producto_delete' ? 'productos' : 'ventas'));
        }
    } catch (InvalidArgumentException | PDOException $exception) {
        flash('error', $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
            ? 'Ese correo ya esta registrado.'
            : $exception->getMessage());
        redirect($_POST['return_page'] ?? 'inicio');
    }
}

$flash = getFlash();
$orsKey = (string) ($config['ors_api_key'] ?? '');
$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY id_usuario DESC')->fetchAll();
$productos = $pdo->query('SELECT * FROM productos ORDER BY id_producto DESC')->fetchAll();
$ventas = $pdo->query('SELECT v.*, u.nombre AS usuario, p.nombre AS producto FROM ventas v INNER JOIN usuarios u ON u.id_usuario = v.id_usuario INNER JOIN productos p ON p.id_producto = v.id_producto ORDER BY v.id_venta DESC')->fetchAll();

$editUsuario = null;
if (!empty($_GET['edit_user'])) {
    $statement = $pdo->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
    $statement->execute([(int) $_GET['edit_user']]);
    $editUsuario = $statement->fetch() ?: null;
}
$editProducto = null;
if (!empty($_GET['edit_product'])) {
    $statement = $pdo->prepare('SELECT * FROM productos WHERE id_producto = ?');
    $statement->execute([(int) $_GET['edit_product']]);
    $editProducto = $statement->fetch() ?: null;
}
$editVenta = null;
if (!empty($_GET['edit_sale'])) {
    $statement = $pdo->prepare('SELECT * FROM ventas WHERE id_venta = ?');
    $statement->execute([(int) $_GET['edit_sale']]);
    $editVenta = $statement->fetch() ?: null;
}

function checked(bool $value): string { return $value ? ' checked' : ''; }
function selected(bool $value): string { return $value ? ' selected' : ''; }
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de ventas | CRUD</title>
    <link rel="stylesheet" href="https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>
    <script>
    // Boton "Nuevo": abre y lleva a la vista el panel con el formulario
    function abrirFormulario(id) {
        var panel = $('#' + id);
        panel.collapsible('expand');
        $('html, body').animate({ scrollTop: panel.offset().top - 60 }, 250);
        panel.find('input:visible, select:visible').first().focus();
    }
    // Video de YouTube: se carga al entrar a la seccion y se detiene al salir
    $(document).on('pagebeforeshow', '#video', function () {
        var f = $('#video-frame'); f.attr('src', f.data('src'));
    });
    $(document).on('pagehide', '#video', function () {
        $('#video-frame').attr('src', 'about:blank');
    });
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <style>
        .location-panel .map-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px; }
        .location-panel .map-col { min-width: 0; }
        .location-panel .map-col h3 { margin: 0 0 8px; font-size: 1rem; }
        .location-panel .map-grid iframe { display: block; width: 100%; height: 450px; border: 0; }
        #mapa-leaflet { position: relative; z-index: 0; width: 100%; height: 450px; border-radius: 4px; }
        .mapa-controles { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
        .mapa-controles input, .mapa-controles select { box-sizing: border-box; padding: 8px; border: 1px solid #9aa5a1; border-radius: 6px; font: inherit; background: #fff; color: #222; }
        .mapa-controles input { flex: 1 1 160px; min-width: 0; }
        .mapa-btn { padding: 8px 12px; border: 1px solid #14463f; border-radius: 6px; background: #14463f; color: #fff; font: inherit; cursor: pointer; }
        .mapa-btn.sec { background: #fff; color: #14463f; }
        .mapa-btn:focus-visible, .mapa-controles input:focus-visible, .mapa-controles select:focus-visible { outline: 3px solid #e0a100; outline-offset: 2px; }
        .mapa-info { margin: 0 0 6px; min-height: 1.3em; font-size: .9rem; }
        @media (max-width: 800px) { .location-panel .map-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div data-role="page" id="inicio">
    <div data-role="header"><a href="inicio.html" data-ajax="false" data-icon="home">Portada</a><h1 class="brand">Sistema de ventas</h1></div>
    <div class="hero"><div class="content hero-inner"><div><p class="eyebrow">Hola Mundo</p><h1>Panel de control</h1><p>Administra usuarios, productos y ventas desde una interfaz ligera compatible con hosting compartido.</p></div><img class="profile-photo" src="mi_foto.jpeg" alt="Foto personal"></div></div>
    <main class="content">
        <?php if ($flash): ?><div class="notice<?= $flash['type'] === 'error' ? ' error' : '' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <section class="panel location-panel">
            <h2>Ubicación</h2>
            <p class="section-label">Cómo llegar</p>
            <p>Estoy ubicado en una zona central con fácil acceso por transporte público y rutas principales, ideal para llegar sin complicaciones.</p>
            <div class="map-grid">
                <div class="map-col">
                    <h3>Mapa de Google</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2858.2233490563667!2d-99.12777556532178!3d19.64078403283855!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85d1f43e59c00a9f%3A0xe3c992b6aa422e2c!2sUniversidad%20Polit%C3%A9cnica%20del%20Valle%20de%20M%C3%A9xico!5e0!3m2!1ses-419!2smx!4v1790863623769!5m2!1ses-419!2smx" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
                <div class="map-col">
                    <h3>Mapa interactivo (Leaflet)</h3>
                    <div class="mapa-controles">
                        <input id="mapa-busqueda" type="text" data-role="none" placeholder="Busca un lugar o dirección" aria-label="Buscar lugar o dirección">
                        <button type="button" id="mapa-buscar" class="mapa-btn" data-role="none">Buscar</button>
                        <select id="mapa-modo" data-role="none" aria-label="Modo de transporte">
                            <option value="driving-car">En auto</option>
                            <option value="foot-walking">A pie</option>
                            <option value="cycling-regular">En bici</option>
                        </select>
                        <button type="button" id="mapa-ruta" class="mapa-btn sec" data-role="none">Ruta entre los 2 últimos puntos</button>
                        <button type="button" id="mapa-limpiar" class="mapa-btn sec" data-role="none">Limpiar</button>
                    </div>
                    <p id="mapa-info" class="mapa-info" aria-live="polite">Haz clic en el mapa para agregar puntos o busca una dirección.</p>
                    <div id="mapa-leaflet" role="application" aria-label="Mapa interactivo"></div>
                </div>
            </div>
        </section>
        <div class="stats"><div class="stat"><strong><?= count($usuarios) ?></strong><span>Usuarios registrados</span></div><div class="stat"><strong><?= count($productos) ?></strong><span>Productos registrados</span></div><div class="stat"><strong><?= count($ventas) ?></strong><span>Ventas registradas</span></div></div>
        <div data-role="navbar"><ul><li><a href="#usuarios" class="ui-btn primary">Usuarios</a></li><li><a href="#productos" class="ui-btn primary">Productos</a></li><li><a href="#ventas" class="ui-btn primary">Ventas</a></li><li><a href="#video" class="ui-btn primary">Video</a></li></ul></div>
        <p style="text-align:center"><a href="encuesta.html" data-ajax="false" class="ui-btn ui-btn-inline">📝 Contestar la encuesta UPVM</a></p>
    </main>
</div>

<div data-role="page" id="usuarios">
    <div data-role="header"><a href="#inicio" data-icon="home">Inicio</a><h1>Usuarios</h1><a href="#" onclick="abrirFormulario('usuario-form');return false;" data-icon="plus">Nuevo</a></div>
    <main class="content">
        <?php if ($flash): ?><div class="notice<?= $flash['type'] === 'error' ? ' error' : '' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <section class="panel"><h2>Usuarios registrados</h2><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Telefono</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($usuarios as $usuario): ?><tr><td><?= e($usuario['nombre']) ?></td><td><?= e($usuario['email']) ?></td><td><?= e($usuario['telefono']) ?></td><td><?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?></td><td class="actions"><a class="ui-btn" data-ajax="false" href="?edit_user=<?= $usuario['id_usuario'] ?>#usuarios">Editar</a><form method="post" data-ajax="false" onsubmit="return confirm('Eliminar este usuario?');"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="usuario_delete"><input type="hidden" name="id" value="<?= $usuario['id_usuario'] ?>"><button class="ui-btn danger" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
        <section data-role="collapsible" class="panel" id="usuario-form" data-collapsed="<?= $editUsuario ? 'false' : 'true' ?>"><h2><?= $editUsuario ? 'Editar usuario' : 'Nuevo usuario' ?></h2><form method="post" data-ajax="false"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="usuario_save"><input type="hidden" name="return_page" value="usuarios"><input type="hidden" name="id_usuario" value="<?= e($editUsuario['id_usuario'] ?? '') ?>"><div class="form-grid"><label>Nombre<input name="nombre" maxlength="100" required value="<?= e($editUsuario['nombre'] ?? '') ?>"></label><label>Correo<input type="email" name="email" maxlength="150" required value="<?= e($editUsuario['email'] ?? '') ?>"></label><label>Telefono<input name="telefono" maxlength="20" value="<?= e($editUsuario['telefono'] ?? '') ?>"></label><label><input type="checkbox" name="activo" value="1"<?= checked((bool) ($editUsuario['activo'] ?? true)) ?>> Activo</label></div><button class="ui-btn primary" type="submit">Guardar usuario</button></form></section>
    </main>
</div>

<div data-role="page" id="productos">
    <div data-role="header"><a href="#inicio" data-icon="home">Inicio</a><h1>Productos</h1><a href="#" onclick="abrirFormulario('producto-form');return false;" data-icon="plus">Nuevo</a></div>
    <main class="content">
        <?php if ($flash): ?><div class="notice<?= $flash['type'] === 'error' ? ' error' : '' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <section class="panel"><h2>Catalogo</h2><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($productos as $producto): ?><tr><td><strong><?= e($producto['nombre']) ?></strong><br><span class="muted"><?= e($producto['descripcion']) ?></span></td><td>$<?= number_format((float) $producto['precio'], 2) ?></td><td><?= e($producto['stock']) ?></td><td><?= $producto['activo'] ? 'Activo' : 'Inactivo' ?></td><td class="actions"><a class="ui-btn" data-ajax="false" href="?edit_product=<?= $producto['id_producto'] ?>#productos">Editar</a><form method="post" data-ajax="false" onsubmit="return confirm('Eliminar este producto?');"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="producto_delete"><input type="hidden" name="id" value="<?= $producto['id_producto'] ?>"><button class="ui-btn danger" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
        <section data-role="collapsible" class="panel" id="producto-form" data-collapsed="<?= $editProducto ? 'false' : 'true' ?>"><h2><?= $editProducto ? 'Editar producto' : 'Nuevo producto' ?></h2><form method="post" data-ajax="false"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="producto_save"><input type="hidden" name="return_page" value="productos"><input type="hidden" name="id_producto" value="<?= e($editProducto['id_producto'] ?? '') ?>"><div class="form-grid"><label>Nombre<input name="nombre" maxlength="150" required value="<?= e($editProducto['nombre'] ?? '') ?>"></label><label>Precio<input type="number" name="precio" min="0" step="0.01" required value="<?= e($editProducto['precio'] ?? '0.00') ?>"></label><label>Stock<input type="number" name="stock" min="0" step="1" required value="<?= e($editProducto['stock'] ?? '0') ?>"></label><label>Imagen<input name="imagen" maxlength="255" value="<?= e($editProducto['imagen'] ?? '') ?>" placeholder="imagenes_productos/foto.jpg"></label><label class="wide">Descripcion<textarea name="descripcion" maxlength="5000"><?= e($editProducto['descripcion'] ?? '') ?></textarea></label><label><input type="checkbox" name="activo" value="1"<?= checked((bool) ($editProducto['activo'] ?? true)) ?>> Activo</label></div><button class="ui-btn primary" type="submit">Guardar producto</button></form></section>
    </main>
</div>

<div data-role="page" id="ventas">
    <div data-role="header"><a href="#inicio" data-icon="home">Inicio</a><h1>Ventas</h1><a href="#" onclick="abrirFormulario('venta-form');return false;" data-icon="plus">Nueva</a></div>
    <main class="content">
        <?php if ($flash): ?><div class="notice<?= $flash['type'] === 'error' ? ' error' : '' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <section class="panel"><h2>Historial de ventas</h2><div class="table-wrap"><table><thead><tr><th>Cliente</th><th>Producto</th><th>Cantidad</th><th>Total final</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($ventas as $venta): ?><tr><td><?= e($venta['usuario']) ?></td><td><?= e($venta['producto']) ?></td><td><?= e($venta['cantidad']) ?></td><td>$<?= number_format((float) $venta['total_final'], 2) ?></td><td><?= e($venta['created_at']) ?></td><td class="actions"><a class="ui-btn" data-ajax="false" href="?edit_sale=<?= $venta['id_venta'] ?>#ventas">Editar</a><form method="post" data-ajax="false" onsubmit="return confirm('Eliminar esta venta?');"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="venta_delete"><input type="hidden" name="id" value="<?= $venta['id_venta'] ?>"><button class="ui-btn danger" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
        <section data-role="collapsible" class="panel" id="venta-form" data-collapsed="<?= $editVenta ? 'false' : 'true' ?>"><h2><?= $editVenta ? 'Editar venta' : 'Nueva venta' ?></h2><form method="post" data-ajax="false"><input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="venta_save"><input type="hidden" name="return_page" value="ventas"><input type="hidden" name="id_venta" value="<?= e($editVenta['id_venta'] ?? '') ?>"><div class="form-grid"><label>Usuario<select name="id_usuario" required><option value="">Selecciona un usuario</option><?php foreach ($usuarios as $usuario): ?><option value="<?= $usuario['id_usuario'] ?>"<?= selected((int) ($editVenta['id_usuario'] ?? 0) === (int) $usuario['id_usuario']) ?>><?= e($usuario['nombre']) ?></option><?php endforeach; ?></select></label><label>Producto<select name="id_producto" required><option value="">Selecciona un producto</option><?php foreach ($productos as $producto): ?><option value="<?= $producto['id_producto'] ?>"<?= selected((int) ($editVenta['id_producto'] ?? 0) === (int) $producto['id_producto']) ?>><?= e($producto['nombre']) ?></option><?php endforeach; ?></select></label><label>Cantidad<input type="number" name="cantidad" min="1" step="1" required value="<?= e($editVenta['cantidad'] ?? '1') ?>"></label><label>Precio unitario<input type="number" name="precio_unit" min="0" step="0.01" required value="<?= e($editVenta['precio_unit'] ?? '0.00') ?>"></label><label>Tasa de impuesto (%)<input type="number" name="tasa_impuesto" min="0" max="100" step="0.01" required value="<?= e($editVenta['tasa_impuesto'] ?? '16.00') ?>"></label></div><p class="muted">El total bruto, impuesto y total final se recalculan mediante los triggers de Sistema.sql.</p><button class="ui-btn primary" type="submit">Guardar venta</button></form></section>
    </main>
</div>

<div data-role="page" id="video">
    <div data-role="header"><a href="#inicio" data-icon="home">Inicio</a><h1>Video</h1></div>
    <main class="content">
        <section class="panel">
            <h2>Mira el video</h2>
            <div class="video-wrap">
                <iframe id="video-frame" data-src="https://www.youtube-nocookie.com/embed/3FWZfY-sYLU?rel=0" title="Video de YouTube"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
            <p class="muted">¿No se reproduce? <a href="https://youtu.be/3FWZfY-sYLU" target="_blank" rel="external noopener">Míralo directamente en YouTube</a>.</p>
        </section>
    </main>
</div>
<script>
(function () {
    var ORS_API_KEY = <?= json_encode($orsKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var CENTRO = [19.64078, -99.12778]; // Universidad Politécnica del Valle de México
    var mapa = null, marcadores = [], capaRuta = null;
    var info = document.getElementById('mapa-info');

    function agregarMarcador(lat, lng, texto, abrir) {
        var m = L.marker([lat, lng]).addTo(mapa).bindPopup(texto);
        if (abrir !== false) { m.openPopup(); }
        marcadores.push(m);
        return m;
    }

    function iniciarMapa() {
        if (typeof L === 'undefined') { info.textContent = 'No se pudo cargar Leaflet. Revisa tu conexión.'; return; }
        if (mapa) { mapa.invalidateSize(); return; }   // al volver a la página de inicio
        mapa = L.map('mapa-leaflet').setView(CENTRO, 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; Colaboradores de OpenStreetMap | Rutas: openrouteservice.org'
        }).addTo(mapa);
        agregarMarcador(CENTRO[0], CENTRO[1], 'Universidad Politécnica del Valle de México', false);
        mapa.on('click', function (e) {
            agregarMarcador(e.latlng.lat, e.latlng.lng,
                'Punto ' + (marcadores.length + 1) + '<br>' + e.latlng.lat.toFixed(5) + ', ' + e.latlng.lng.toFixed(5));
            info.textContent = 'Puntos en el mapa: ' + marcadores.length;
        });
    }

    // Búsqueda: primero OpenRouteService (con API key); si falla, Nominatim (sin clave)
    async function geocodificar(q) {
        if (ORS_API_KEY) {
            try {
                var r = await fetch('https://api.heigit.org/pelias/v1/search?api_key=' + encodeURIComponent(ORS_API_KEY) + '&size=1&text=' + encodeURIComponent(q));
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                var d = await r.json();
                if (d.features && d.features.length) {
                    return { lat: d.features[0].geometry.coordinates[1], lng: d.features[0].geometry.coordinates[0], nombre: d.features[0].properties.label };
                }
                return null;
            } catch (err) { /* se intenta el servicio alterno */ }
        }
        var r2 = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q));
        var d2 = await r2.json();
        return d2.length ? { lat: parseFloat(d2[0].lat), lng: parseFloat(d2[0].lon), nombre: d2[0].display_name } : null;
    }

    async function buscar() {
        var q = document.getElementById('mapa-busqueda').value.trim();
        if (!q) { info.textContent = 'Escribe una dirección o lugar para buscar.'; return; }
        info.textContent = 'Buscando...';
        try {
            var res = await geocodificar(q);
            if (!res) { info.textContent = 'No se encontró ese lugar. Prueba con otro nombre.'; return; }
            mapa.setView([res.lat, res.lng], 16);
            agregarMarcador(res.lat, res.lng, res.nombre);
            info.textContent = 'Encontrado: ' + res.nombre;
        } catch (err) {
            info.textContent = 'No se pudo buscar. Revisa tu conexión.';
        }
    }

    function dibujarRuta(geojson, km, min, nota) {
        if (capaRuta) { mapa.removeLayer(capaRuta); }
        capaRuta = L.geoJSON(geojson, { style: { color: '#c2410c', weight: 5 } }).addTo(mapa);
        mapa.fitBounds(capaRuta.getBounds(), { padding: [30, 30] });
        info.textContent = 'Ruta: ' + km.toFixed(1) + ' km, ' + Math.round(min) + ' min aprox.' + (nota || '');
    }

    // Ruta: primero OpenRouteService (con API key); si falla, OSRM (sin clave, solo auto)
    async function trazarRuta() {
        if (marcadores.length < 2) { info.textContent = 'Necesitas al menos 2 puntos para trazar una ruta.'; return; }
        var a = marcadores[marcadores.length - 2].getLatLng();
        var b = marcadores[marcadores.length - 1].getLatLng();
        var modo = document.getElementById('mapa-modo').value;
        info.textContent = 'Calculando ruta...';
        if (ORS_API_KEY) {
            try {
                var r = await fetch('https://api.heigit.org/openrouteservice/v2/directions/' + modo + '/geojson', {
                    method: 'POST',
                    headers: { 'Authorization': ORS_API_KEY, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ coordinates: [[a.lng, a.lat], [b.lng, b.lat]] })
                });
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                var d = await r.json();
                var f = d.features[0];
                dibujarRuta(f, f.properties.summary.distance / 1000, f.properties.summary.duration / 60);
                return;
            } catch (err) { /* se intenta el servicio alterno */ }
        }
        try {
            var url = 'https://router.project-osrm.org/route/v1/driving/' + a.lng + ',' + a.lat + ';' + b.lng + ',' + b.lat + '?overview=full&geometries=geojson';
            var d2 = await (await fetch(url)).json();
            if (d2.code !== 'Ok') { info.textContent = 'No se encontró una ruta entre esos puntos.'; return; }
            var ruta = d2.routes[0];
            dibujarRuta(ruta.geometry, ruta.distance / 1000, ruta.duration / 60, ' (servicio alterno, en auto)');
        } catch (err) {
            info.textContent = 'No se pudo calcular la ruta. Intenta de nuevo.';
        }
    }

    function limpiar() {
        marcadores.forEach(function (m) { mapa.removeLayer(m); });
        marcadores = [];
        if (capaRuta) { mapa.removeLayer(capaRuta); capaRuta = null; }
        agregarMarcador(CENTRO[0], CENTRO[1], 'Universidad Politécnica del Valle de México', false);
        mapa.setView(CENTRO, 15);
        info.textContent = 'Mapa limpio. Haz clic para agregar puntos.';
    }

    document.getElementById('mapa-buscar').addEventListener('click', buscar);
    document.getElementById('mapa-busqueda').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
    document.getElementById('mapa-ruta').addEventListener('click', trazarRuta);
    document.getElementById('mapa-limpiar').addEventListener('click', limpiar);

    // jQuery Mobile muestra/oculta "páginas": el mapa se crea (o se redimensiona) al mostrar el inicio
    $(document).on('pageshow', '#inicio', iniciarMapa);
})();
</script>
</body>
</html>
