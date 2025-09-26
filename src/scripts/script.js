
function nextStep() {
  const current = document.querySelector('.step.active');
  const next = current.nextElementSibling;
  if (next && next.classList.contains('step')) {
    current.classList.remove('active');
    next.classList.add('active');
  }
}

function prevStep() {
  const current = document.querySelector('.step.active');
  const prev = current.previousElementSibling;
  if (prev && prev.classList.contains('step')) {
    current.classList.remove('active');
    prev.classList.add('active');
  }
}

// Al enviar el pre-registro, avanzar al paso 2
document.getElementById('pre-register-form').addEventListener('submit', function(e) {
  e.preventDefault();
  // Aquí podrías validar campos si quieres
  nextStep();
});

// Al enviar la reserva, avanzar al paso 3
document.getElementById('reservation-form').addEventListener('submit', function(e) {
  e.preventDefault();
  // Aquí podrías validar campos de reserva
  nextStep();
  // Mostrar detalles de la reserva
  mostrarReserva();
});

function mostrarReserva() {
  const latest = document.getElementById('latest-reservation');
  const name = document.getElementById('name').value;
  const date = document.getElementById('date').value;
  const time = document.getElementById('time').value;
  const guests = document.getElementById('guests').value;
  const table = document.getElementById('table-type').value;

  latest.innerHTML = `
    <p><strong>Nombre:</strong> ${name}</p>
    <p><strong>Fecha:</strong> ${date}</p>
    <p><strong>Hora:</strong> ${time}</p>
    <p><strong>Personas:</strong> ${guests}</p>
    <p><strong>Tipo de mesa:</strong> ${table}</p>
  `;
}


function goHome() {
  Toastify({
    text: "✅ Tu reserva fue confirmada. Redirigiendo al inicio...",
    duration: 3000,
    close: true,
    gravity: "top",
    position: "center",
    backgroundColor: "#28a745",
  }).showToast();

  setTimeout(() => {
    window.location.href = "pantalla principal.html"; // ⬅️ Aquí va tu archivo principal
  }, 3200);
}


