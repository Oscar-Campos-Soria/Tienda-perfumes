<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'cliente') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>PerFime - Tienda de Perfumes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 70px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    header {
      position: fixed;
      top: 0; left: 0; right: 0;
      background-color: #343a40;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 1030;
    }
    header h1 {
      font-weight: 700;
      font-size: 1.8rem;
    }
    header a.btn-logout {
      color: white;
      border: 1px solid white;
      border-radius: 5px;
      padding: 6px 14px;
      text-decoration: none;
      transition: background-color 0.2s ease;
    }
    header a.btn-logout:hover {
      background-color: white;
      color: #343a40;
    }
    .producto-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill,minmax(250px,1fr));
      gap: 20px;
    }
    .producto {
      background: white;
      border-radius: 10px;
      padding: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
    }
    .producto img {
      max-height: 180px;
      object-fit: contain;
      margin-bottom: 10px;
    }
    .producto h5 {
      font-size: 1.2rem;
      margin-bottom: 5px;
      min-height: 3em;
    }
    .producto p {
      flex-grow: 1;
      font-size: 0.9rem;
      color: #555;
    }
    .carrito {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
      max-height: 450px;
      overflow-y: auto;
      position: sticky;
      top: 80px;
    }
    .carrito h4 {
      font-weight: 700;
      margin-bottom: 15px;
      border-bottom: 2px solid #28a745;
      padding-bottom: 5px;
    }
    #carrito-items li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
      border-bottom: 1px solid #eee;
      padding-bottom: 6px;
    }
    #carrito-items li strong {
      flex-grow: 1;
    }
    #carrito-items button {
      border: none;
      background: transparent;
      color: #dc3545;
      font-size: 1.1rem;
      cursor: pointer;
    }
    .pago-box {
      background: #e9f7ef;
      border-left: 5px solid #28a745;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      font-size: 0.9rem;
      color: #155724;
    }
    /* Pagination */
    .pagination .page-item.active .page-link {
      background-color: #28a745;
      border-color: #28a745;
      color: white;
    }
    .pagination .page-link {
      cursor: pointer;
    }
  </style>
</head>
<body>

<header>
  <h1><i class="fas fa-store"></i> PerFime</h1>
  <a href="logout.php" class="btn-logout">Cerrar sesión</a>
</header>

<div class="container">
  <form id="filtros" class="row g-3 mb-4">
    <div class="col-md-3">
      <select id="categoria" class="form-select" aria-label="Categoría">
        <option value="">Todas las categorías</option>
        <option value="caballero">Caballero</option>
        <option value="dama">Dama</option>
        <option value="mixto">Mixto</option>
      </select>
    </div>
    <div class="col-md-3">
      <select id="presentacion" class="form-select" aria-label="Presentación">
        <option value="">Todas las presentaciones</option>
        <option value="1">Completo</option>
        <option value="2">5 ml</option>
        <option value="3">10 ml</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="number" class="form-control" id="min" placeholder="Precio mínimo" min="0" step="0.01" />
    </div>
    <div class="col-md-2">
      <input type="number" class="form-control" id="max" placeholder="Precio máximo" min="0" step="0.01" />
    </div>
    <div class="col-md-2 d-grid">
      <button type="submit" class="btn btn-success">Filtrar</button>
    </div>
  </form>

  <div class="row">
    <div class="col-md-8">
      <div id="producto" class="producto-grid"></div>
      <nav>
        <ul id="paginacion" class="pagination justify-content-center mt-4"></ul>
      </nav>
    </div>
    <div class="col-md-4">
      <div class="carrito">
        <h4><i class="fas fa-cart-shopping"></i> Carrito</h4>
        <ul id="carrito-items" class="list-unstyled"></ul>
        <p class="fw-bold">Total: $<span id="carrito-total">0.00</span></p>

        <select id="metodo_pago" class="form-select mb-3" aria-label="Método de pago">
          <option value="efectivo">Efectivo</option>
          <option value="transferencia">Transferencia</option>
        </select>

        <button id="finalizar" class="btn btn-success w-100 mb-2">Finalizar compra</button>
        <button id="vaciar" class="btn btn-secondary w-100">Vaciar carrito</button>
      </div>

      <div class="pago-box" id="instrucciones-pago" style="display: none;">
        <h5><i class="fas fa-info-circle"></i> Instrucciones de pago</h5>
        <div id="pago-info" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

<script>
  const carrito = JSON.parse(localStorage.getItem('carrito')) || {};

  function renderproducto(data) {
    const contenedor = document.getElementById('producto');
    contenedor.innerHTML = '';
    if (data.length === 0) {
      contenedor.innerHTML = '<p>No hay productos que mostrar.</p>';
      return;
    }
    data.forEach(p => {
      contenedor.innerHTML += `
        <div class="producto">
          <img src="imagenes/${p.Imagen}" alt="${p.Nombre}">
          <h5>${p.Nombre}</h5>
          <p>${p.Descripcion}</p>
          <p class="fw-bold">$${parseFloat(p.Precio).toFixed(2)}</p>
          <button class="btn btn-primary w-100" onclick="agregarCarrito(${p.IdProducto}, '${p.Nombre}', ${p.Precio})">Agregar al carrito</button>
        </div>`;
    });
  }

  function agregarCarrito(id, nombre, precio) {
    if (!carrito[id]) carrito[id] = { id, nombre, precio, cantidad: 1 };
    else carrito[id].cantidad++;
    localStorage.setItem('carrito', JSON.stringify(carrito));
    renderCarrito();
  }

  function eliminarItem(id) {
    delete carrito[id];
    localStorage.setItem('carrito', JSON.stringify(carrito));
    renderCarrito();
  }

  function renderCarrito() {
    const items = document.getElementById('carrito-items');
    const total = document.getElementById('carrito-total');
    items.innerHTML = '';
    let suma = 0;
    for (const id in carrito) {
      const item = carrito[id];
      const subtotal = item.precio * item.cantidad;
      suma += subtotal;
      items.innerHTML += `
        <li class="mb-2 d-flex justify-content-between align-items-center">
          <div>
            <strong>${item.nombre}</strong><br>
            ${item.cantidad} x $${item.precio.toFixed(2)} = $${subtotal.toFixed(2)}
          </div>
          <button class="btn btn-sm btn-danger ms-2" onclick="eliminarItem(${id})" aria-label="Eliminar ${item.nombre}">❌</button>
        </li>`;
    }
    total.textContent = suma.toFixed(2);
  }

  function cargarproducto(pagina = 1) {
    const params = new URLSearchParams({
      categoria: document.getElementById('categoria').value,
      presentacion: document.getElementById('presentacion').value,
      min: document.getElementById('min').value,
      max: document.getElementById('max').value,
      pagina
    });

    fetch('api/obtener_productos.php?' + params.toString())
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          renderproducto(data.data);
          renderPaginacion(data.total_paginas, pagina);
        } else {
          document.getElementById('producto').innerHTML = '<p>No hay productos que mostrar.</p>';
          document.getElementById('paginacion').innerHTML = '';
        }
      });
  }

  function renderPaginacion(total, actual) {
    const pag = document.getElementById('paginacion');
    pag.innerHTML = '';
    for (let i = 1; i <= total; i++) {
      const li = document.createElement('li');
      li.className = 'page-item' + (i === actual ? ' active' : '');
      li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
      li.addEventListener('click', e => {
        e.preventDefault();
        cargarproducto(i);
      });
      pag.appendChild(li);
    }
  }

  document.getElementById('filtros').addEventListener('submit', e => {
    e.preventDefault();
    cargarproducto();
  });

  document.getElementById('vaciar').addEventListener('click', () => {
    localStorage.removeItem('carrito');
    renderCarrito();
  });

  document.getElementById('finalizar').addEventListener('click', () => {
    const producto = Object.values(carrito);
    const metodo_pago = document.getElementById('metodo_pago').value;

    if (producto.length === 0) {
      alert('El carrito está vacío.');
      return;
    }

    fetch('api/finalizar_compra.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ producto, metodo_pago })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('instrucciones-pago').style.display = 'block';
          const pagoDiv = document.getElementById('pago-info');
          pagoDiv.innerHTML = '';
          for (const clave in data.instrucciones_pago) {
            pagoDiv.innerHTML += `<p><strong>${clave}:</strong> ${data.instrucciones_pago[clave]}</p>`;
          }
          alert("Compra realizada con éxito.");
          localStorage.removeItem('carrito');
          renderCarrito();
        } else {
          alert(data.message || "Error al procesar la compra.");
        }
      });
  });

  // Carga inicial
  cargarproducto();
  renderCarrito();
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
