'use strict';

// Shared client-side helpers will be added as the service forms are implemented.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      const message = element.getAttribute('data-confirm') || '¿Confirmas esta acción?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });
});
