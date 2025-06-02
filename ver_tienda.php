<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>PerFime</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #dfe9f3, #ffffff);
      font-family: 'Segoe UI', sans-serif;
    }
    header {
      background: #1565c0;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .producto-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 1.5rem;
    }
    .producto {
      background: white;
      border-radius: 10px;
      padding: 1rem;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .producto:hover {
      transform: scale(1.02);
    }
    .producto img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 8px;
    }
    .carrito, .pago-box {
      background: #f5f5f5;
      padding: 1rem;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .pagination .page-item.active .page-link {
      background-color: #0d47a1;
      border-color: #0d47a1;
    }
  </style>
</head>
<body>

<header>
  <h1><i class="fas fa-store"></i> PerFime</h1>
  <a href="logout.php" class="btn btn-light">Cerrar sesión</a>
</header>

<div class="container mt-4">
  <form id="filtros" class="row g-2 mb-4">
    <div class="col-md-2">
      <select id="categoria" class="form-select">
        <option value="">Categoría</option>
        <option value="dama">Dama</option>
        <option value="caballero">Caballero</option>
        <option value="mixto">Mixto</option>
      </select>
    </div>
    <div class="col-md-2">
      <select id="presentacion" class="form-select">
        <option value="">Presentación</option>
        <option value="completo">Completo</option>
        <option value="5ml">5 ml</option>
        <option value="10ml">10 ml</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="number" id="min" class="form-control" placeholder="Precio mínimo">
    </div>
    <div class="col-md-2">
      <input type="number" id="max" class="form-control" placeholder="Precio máximo">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Filtrar</button>
    </div>
  </form>

  <div class="row">
    <div class="col-md-8">
      <div id="productos" class="producto-grid mb-4"></div>
      <nav>
        <ul id="paginacion" class="pagination justify-content-center"></ul>
      </nav>
    </div>
    <div class="col-md-4">
      <div class="carrito mb-4">
        <h4><i class="fas fa-cart-shopping"></i> Carrito</h4>
        <ul id="carrito-items" class="list-unstyled"></ul>
        <p class="fw-bold">Total: $<span id="carrito-total">0.00</span></p>

        <select id="metodo_pago" class="form-select mb-2">
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

function renderProductos(data) {
  const contenedor = document.getElementById('productos');
  contenedor.innerHTML = '';
  data.forEach(p => {
    contenedor.innerHTML += `
      <div class="producto">
        <img src="imagenes/${p.imagen}" alt="${p.Nombre}">
        <h5>${p.Nombre}</h5>
        <p>${p.descripcion}</p>
        <p class="fw-bold">$${parseFloat(p.Precio).toFixed(2)}</p>
        <button class="btn btn-primary w-100" onclick="agregarCarrito(${p.Id}, '${p.Nombre}', ${p.Precio})">Agregar al carrito</button>
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
      <li class="mb-2">
        <strong>${item.nombre}</strong><br>
        ${item.cantidad} x $${item.precio.toFixed(2)} = $${subtotal.toFixed(2)}
        <button class="btn btn-sm btn-danger ms-2" onclick="eliminarItem(${id})">❌</button>
      </li>`;
  }
  total.textContent = suma.toFixed(2);
}

function cargarProductos(pagina = 1) {
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
        renderProductos(data.data);
        renderPaginacion(data.total_paginas, pagina);
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
      cargarProductos(i);
    });
    pag.appendChild(li);
  }
}

document.getElementById('filtros').addEventListener('submit', e => {
  e.preventDefault();
  cargarProductos();
});

document.getElementById('vaciar').addEventListener('click', () => {
  localStorage.removeItem('carrito');
  location.reload();
});

document.getElementById('finalizar').addEventListener('click', () => {
  const productos = Object.values(carrito);
  const metodo_pago = document.getElementById('metodo_pago').value;

  if (productos.length === 0) return alert('El carrito está vacío.');

  fetch('api/finalizar_compra.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ productos, metodo_pago })
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

cargarProductos();
renderCarrito();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
