(() => {
  'use strict';

  const menuButton = document.querySelector('.menu-toggle');
  const mainNav = document.querySelector('#main-nav');

  const closePublicMenu = (returnFocus = false) => {
    if (!menuButton || !mainNav) return;
    mainNav.classList.remove('open');
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.setAttribute('aria-label', 'Abrir menú');
    document.body.classList.remove('menu-open');
    if (returnFocus) menuButton.focus();
  };

  if (menuButton && mainNav) {
    menuButton.addEventListener('click', () => {
      const open = mainNav.classList.toggle('open');
      menuButton.setAttribute('aria-expanded', String(open));
      menuButton.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
      document.body.classList.toggle('menu-open', open);
      if (open) mainNav.querySelector('a')?.focus();
    });
    mainNav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closePublicMenu()));
  }

  const dashboardButton = document.querySelector('.dashboard-menu');
  const sidebar = document.querySelector('.sidebar');
  const closeDashboardMenu = (returnFocus = false) => {
    if (!dashboardButton || !sidebar) return;
    sidebar.classList.remove('open');
    dashboardButton.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('menu-open');
    if (returnFocus) dashboardButton.focus();
  };
  if (dashboardButton && sidebar) {
    dashboardButton.addEventListener('click', () => {
      const open = sidebar.classList.toggle('open');
      dashboardButton.setAttribute('aria-expanded', String(open));
      document.body.classList.toggle('menu-open', open);
      if (open) sidebar.querySelector('a, button')?.focus();
    });
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeDashboardMenu()));
  }

  document.querySelectorAll('[data-dismiss]').forEach((button) => {
    button.addEventListener('click', () => button.closest('[data-dismissible]')?.remove());
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.dataset.confirm || '¿Confirmas esta acción?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  const localDate = (date) => {
    const offset = date.getTimezoneOffset();
    return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10);
  };
  document.querySelectorAll('input[type="date"][data-min-today]').forEach((input) => {
    input.min = localDate(new Date());
    if (!input.value && input.dataset.defaultTomorrow === 'true') {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      input.value = localDate(tomorrow);
    }
  });

  document.querySelectorAll('[data-count]').forEach((field) => {
    const target = document.querySelector(field.dataset.count);
    if (!target) return;
    const update = () => {
      target.textContent = `${field.value.length}/${field.maxLength || '∞'}`;
    };
    field.addEventListener('input', update);
    update();
  });

  document.querySelectorAll('[data-category-id]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const select = document.querySelector('#category_id');
      if (!select) return;
      select.value = trigger.dataset.categoryId;
    });
  });

  document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.querySelector(button.dataset.togglePassword);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.textContent = show ? 'Ocultar' : 'Mostrar';
      button.setAttribute('aria-pressed', String(show));
    });
  });

  document.querySelectorAll('.field-error').forEach((error, index) => {
    error.setAttribute('role', 'alert');
    if (!error.id) error.id = `field-error-${index + 1}`;
    const container = error.closest('.field, fieldset, div');
    const control = container?.querySelector('input:not([type="hidden"]), select, textarea');
    if (!control) return;
    control.setAttribute('aria-invalid', 'true');
    const describedBy = new Set((control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
    describedBy.add(error.id);
    control.setAttribute('aria-describedby', [...describedBy].join(' '));
  });

  document.querySelectorAll('.table-scroll').forEach((region, index) => {
    region.tabIndex = 0;
    region.setAttribute('role', 'region');
    const heading = region.closest('.panel')?.querySelector('.panel-header h2, .panel-header h3');
    region.setAttribute('aria-label', heading?.textContent?.trim() || `Tabla de datos ${index + 1}`);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (mainNav?.classList.contains('open')) closePublicMenu(true);
    if (sidebar?.classList.contains('open')) closeDashboardMenu(true);
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) {
      closePublicMenu();
      if (sidebar && dashboardButton) {
        closeDashboardMenu();
      }
    }
  });
})();
