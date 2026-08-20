(() => {
  const shell = document.querySelector('.prototype-shell');
  const themeToggle = document.querySelector('.theme-toggle');

  if (shell && themeToggle) {
    const savedTheme = window.localStorage.getItem('regionalPrototypeTheme') || 'light';

    const setTheme = theme => {
      const isDark = theme === 'dark';
      shell.dataset.prototypeTheme = theme;
      themeToggle.setAttribute('aria-pressed', String(isDark));
      themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      themeToggle.innerHTML = `<i class="ti ${isDark ? 'ti-sun' : 'ti-moon'}"></i>`;
      window.localStorage.setItem('regionalPrototypeTheme', theme);
    };

    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
      setTheme(shell.dataset.prototypeTheme === 'dark' ? 'light' : 'dark');
    });
  }

  document.querySelectorAll('[data-back-button]').forEach(button => {
    button.addEventListener('click', event => {
      if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      event.preventDefault();

      if (window.history.length > 1) {
        window.history.back();
        return;
      }

      window.location.assign(button.getAttribute('href') || '/');
    });
  });

  document.querySelectorAll('[data-branch-map]').forEach(mapEl => {
    const lat = Number(mapEl.dataset.lat);
    const lng = Number(mapEl.dataset.lng);
    const zoom = Number(mapEl.dataset.zoom || 13);
    const mapUrl = mapEl.dataset.mapUrl;
    let pointerStart = null;

    if (!window.L || !Number.isFinite(lat) || !Number.isFinite(lng)) {
      if (mapUrl) {
        mapEl.addEventListener('click', () => window.open(mapUrl, '_blank', 'noopener'));
      }
      return;
    }

    mapEl.querySelector('.branch-map-fallback')?.remove();

    let mapCanvas = mapEl.querySelector('[data-branch-map-canvas]');

    if (!mapCanvas) {
      mapCanvas = document.createElement('div');
      mapCanvas.className = 'branch-map-canvas';
      mapCanvas.dataset.branchMapCanvas = '';
      mapEl.prepend(mapCanvas);
    }

    const map = window.L.map(mapCanvas, {
      zoomControl: false,
      attributionControl: true,
      dragging: true,
      scrollWheelZoom: false,
      doubleClickZoom: false,
      tap: true
    }).setView([lat, lng], Number.isFinite(zoom) ? zoom : 13);

    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 20,
      attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);

    const branchIcon = window.L.divIcon({
      className: 'branch-map-marker',
      html: '<span></span>',
      iconSize: [42, 42],
      iconAnchor: [21, 38],
      popupAnchor: [0, -36]
    });

    window.L.marker([lat, lng], { icon: branchIcon })
      .addTo(map)
      .bindPopup(mapEl.dataset.title || 'Regional Finance branch');

    if (mapUrl) {
      mapEl.addEventListener('pointerdown', event => {
        pointerStart = { x: event.clientX, y: event.clientY };
      });

      mapEl.addEventListener('click', event => {
        const target = event.target;
        const isLeafletControl = target instanceof Element && target.closest('.leaflet-control-container');
        const moved = pointerStart && Math.hypot(event.clientX - pointerStart.x, event.clientY - pointerStart.y) > 8;

        if (!isLeafletControl && !moved) {
          window.open(mapUrl, '_blank', 'noopener');
        }
      });

      mapEl.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          window.open(mapUrl, '_blank', 'noopener');
        }
      });
    }

    window.setTimeout(() => map.invalidateSize(), 100);
  });

  const menuToggle = document.querySelector('[data-menu-toggle]');
  const sideMenu = document.querySelector('.side-menu');
  const sideMenuBackdrop = document.querySelector('.side-menu-backdrop');
  const menuCloseControls = document.querySelectorAll('[data-menu-close]');

  if (menuToggle && sideMenu && sideMenuBackdrop) {
    const setMenuOpen = isOpen => {
      sideMenu.classList.toggle('is-open', isOpen);
      sideMenu.setAttribute('aria-hidden', String(!isOpen));
      menuToggle.setAttribute('aria-expanded', String(isOpen));
      sideMenuBackdrop.hidden = !isOpen;

      if (isOpen) {
        sideMenu.querySelector('a, button')?.focus();
      } else if (document.activeElement && sideMenu.contains(document.activeElement)) {
        menuToggle.focus();
      }
    };

    setMenuOpen(false);

    menuToggle.addEventListener('click', () => {
      setMenuOpen(!sideMenu.classList.contains('is-open'));
    });

    menuCloseControls.forEach(control => {
      control.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && sideMenu.classList.contains('is-open')) {
        setMenuOpen(false);
      }
    });

    window.addEventListener('pageshow', () => setMenuOpen(false));
  }

  const mockChat = document.querySelector('[data-mock-chat]');

  if (mockChat) {
    const chatThread = mockChat.querySelector('[data-chat-thread]');
    const chatForm = mockChat.querySelector('[data-chat-form]');
    const chatInput = mockChat.querySelector('[data-chat-input]');
    const responseText = 'This will be AI-powered in the app. For this prototype, I can only show how the chat experience could feel. A future version could answer account questions, guide payment next steps, explain documents, and connect customers with Regional Finance support.';

    const addMessage = (role, label, text) => {
      if (!chatThread) {
        return;
      }

      const message = document.createElement('article');
      message.className = `chat-bubble ${role}`;
      message.innerHTML = `<span>${label}</span><p>${text}</p>`;
      chatThread.appendChild(message);
      chatThread.scrollTop = chatThread.scrollHeight;
    };

    const submitMockMessage = text => {
      const cleanText = text.trim();

      if (!cleanText) {
        return;
      }

      addMessage('customer', 'You', cleanText);
      window.setTimeout(() => addMessage('assistant', 'Regional Assistant', responseText), 300);
    };

    chatForm?.addEventListener('submit', event => {
      event.preventDefault();
      submitMockMessage(chatInput?.value || '');

      if (chatInput) {
        chatInput.value = '';
        chatInput.focus();
      }
    });

    mockChat.querySelectorAll('[data-chat-prompt]').forEach(button => {
      button.addEventListener('click', () => submitMockMessage(button.dataset.chatPrompt || button.textContent || ''));
    });
  }

  const showPrototypeAlert = ({ title, text, icon = 'ti-circle-check', confirmText = 'OK', showCancel = false, cancelText = 'Not now' }) => {
    if (window.Swal) {
      return window.Swal.fire({
        title,
        text,
        icon: icon.includes('x') ? 'warning' : 'success',
        confirmButtonText: confirmText,
        showCancelButton: showCancel,
        cancelButtonText: cancelText,
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-light'
        },
        buttonsStyling: false
      });
    }

    return new Promise(resolve => {
      const backdrop = document.createElement('div');
      backdrop.className = 'prototype-swal-backdrop';
      backdrop.innerHTML = `
        <section class="prototype-swal" role="alertdialog" aria-modal="true">
          <i class="ti ${icon}"></i>
          <h2>${title}</h2>
          <p>${text}</p>
          <div class="button-row">
            ${showCancel ? `<button class="btn btn-light flex-fill" type="button" data-swal-cancel>${cancelText}</button>` : ''}
            <button class="btn btn-primary flex-fill" type="button" data-swal-confirm>${confirmText}</button>
          </div>
        </section>
      `;
      document.body.appendChild(backdrop);
      backdrop.querySelector('[data-swal-confirm]').focus();
      backdrop.querySelector('[data-swal-confirm]').addEventListener('click', () => {
        backdrop.remove();
        resolve({ isConfirmed: true });
      });
      backdrop.querySelector('[data-swal-cancel]')?.addEventListener('click', () => {
        backdrop.remove();
        resolve({ isConfirmed: false });
      });
    });
  };

  const paymentFlow = document.querySelector('[data-payment-flow]');

  if (paymentFlow) {
    const minimumDue = Number(paymentFlow.dataset.minimumDue || 0);
    const amountInput = paymentFlow.querySelector('#payment-amount');
    const dateInput = paymentFlow.querySelector('#payment-date');
    const warning = paymentFlow.querySelector('[data-minimum-warning]');
    const reviewButton = paymentFlow.querySelector('[data-review-payment]');
    const submitButton = paymentFlow.querySelector('[data-submit-payment]');
    const reviewPanel = paymentFlow.querySelector('[data-payment-review]');
    const reviewAmount = paymentFlow.querySelector('[data-review-amount]');
    const reviewDate = paymentFlow.querySelector('[data-review-date]');
    const reviewAccount = paymentFlow.querySelector('[data-review-account]');
    const reviewWarning = paymentFlow.querySelector('[data-review-warning]');
    const accountModeField = paymentFlow.querySelector('[data-account-mode]');
    const addBankToggle = paymentFlow.querySelector('[data-add-bank-toggle]');
    const addBankPanel = paymentFlow.querySelector('[data-add-bank-panel]');
    const bankDropdownToggle = paymentFlow.querySelector('[data-bank-dropdown-toggle]');
    const bankDropdownMenu = paymentFlow.querySelector('[data-bank-dropdown-menu]');
    const selectedBankName = paymentFlow.querySelector('[data-selected-bank-name]');
    const selectedBankMeta = paymentFlow.querySelector('[data-selected-bank-meta]');
    const savedAccountField = paymentFlow.querySelector('[data-saved-account]');
    const cancelPaymentForm = paymentFlow.querySelector('[data-cancel-payment]');

    const money = value => Number(value || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    const formatDate = value => {
      if (!value) {
        return '';
      }

      const [year, month, day] = value.split('-').map(Number);
      return new Date(year, month - 1, day).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
      });
    };
    const selectedAccountLabel = () => {
      if (accountModeField?.value === 'new') {
        const name = paymentFlow.querySelector('[name="new_account_name"]')?.value.trim();
        const accountNumber = paymentFlow.querySelector('[name="new_account_number"]')?.value.trim();
        const suffix = accountNumber ? accountNumber.slice(-4) : 'new';
        return `${name || 'New checking account'} - ${suffix}`;
      }

      return savedAccountField?.value || 'Primary Checking - 4203';
    };
    const syncWarning = () => {
      if (!amountInput || !warning) {
        return false;
      }

      const belowMinimum = Number(amountInput.value || 0) < minimumDue;
      warning.hidden = !belowMinimum;

      return belowMinimum;
    };

    amountInput?.addEventListener('input', syncWarning);
    amountInput?.addEventListener('focus', () => {
      amountInput.value = '';
      syncWarning();
    });
    amountInput?.addEventListener('blur', () => {
      if (amountInput.value.trim() === '') {
        amountInput.value = minimumDue.toFixed(2);
        syncWarning();
      }
    });

    bankDropdownToggle?.addEventListener('click', () => {
      if (!bankDropdownMenu) {
        return;
      }

      bankDropdownMenu.hidden = !bankDropdownMenu.hidden;
      bankDropdownToggle.setAttribute('aria-expanded', String(!bankDropdownMenu.hidden));
    });

    paymentFlow.querySelectorAll('[data-account-option]').forEach(option => {
      option.addEventListener('click', () => {
        if (savedAccountField) {
          savedAccountField.value = option.dataset.accountLabel || 'Primary Checking - 4203';
        }

        if (selectedBankName) {
          selectedBankName.innerHTML = option.dataset.accountName || 'Primary Checking &bull; 4203';
        }

        if (selectedBankMeta) {
          selectedBankMeta.textContent = option.dataset.accountMeta || 'Checking account';
        }

        if (accountModeField) {
          accountModeField.value = 'saved';
        }

        if (bankDropdownMenu) {
          bankDropdownMenu.hidden = true;
        }

        bankDropdownToggle?.setAttribute('aria-expanded', 'false');
        if (addBankPanel) {
          addBankPanel.hidden = true;
        }
      });
    });

    addBankToggle?.addEventListener('click', () => {
      if (!addBankPanel) {
        return;
      }

      addBankPanel.hidden = !addBankPanel.hidden;
      if (!addBankPanel.hidden) {
        if (accountModeField) {
          accountModeField.value = 'new';
        }

        if (bankDropdownMenu) {
          bankDropdownMenu.hidden = true;
        }

        bankDropdownToggle?.setAttribute('aria-expanded', 'false');
        addBankPanel.querySelector('input')?.focus();
      } else if (accountModeField) {
        accountModeField.value = 'saved';
      }
    });

    reviewButton?.addEventListener('click', () => {
      const belowMinimum = syncWarning();

      if (!amountInput?.reportValidity() || !dateInput?.reportValidity()) {
        return;
      }

      if (reviewAmount) {
        reviewAmount.textContent = money(amountInput.value);
      }

      if (reviewDate) {
        reviewDate.textContent = formatDate(dateInput.value);
      }

      if (reviewAccount) {
        reviewAccount.textContent = selectedAccountLabel();
      }

      if (reviewWarning) {
        reviewWarning.hidden = !belowMinimum;
      }

      if (reviewPanel && submitButton) {
        reviewPanel.hidden = false;
        reviewButton.hidden = true;
        submitButton.hidden = false;
        reviewPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });

    if (paymentFlow.dataset.paymentStatus === 'scheduled') {
      showPrototypeAlert({
        title: 'Payment scheduled',
        text: `${paymentFlow.dataset.scheduledAmount} is pending for ${paymentFlow.dataset.scheduledDate}.`,
        icon: 'ti-clock-check',
        confirmText: 'View payment',
        showCancel: true,
        cancelText: 'Go home'
      }).then(result => {
        if (result.isDismissed || result.isConfirmed === false) {
          window.location.assign(paymentFlow.dataset.homeUrl || '/');
        }
      });
    }

    if (paymentFlow.dataset.paymentStatus === 'cancelled') {
      showPrototypeAlert({
        title: 'Payment cancelled',
        text: 'The pending payment was removed before it posted.',
        icon: 'ti-circle-x',
        confirmText: 'Done'
      });
    }

    cancelPaymentForm?.addEventListener('submit', event => {
      event.preventDefault();
      showPrototypeAlert({
        title: 'Cancel pending payment?',
        text: 'You can cancel this payment before it posts.',
        icon: 'ti-alert-circle',
        confirmText: 'Cancel payment',
        showCancel: true
      }).then(result => {
        if (result.isConfirmed) {
          cancelPaymentForm.submit();
        }
      });
    });

    syncWarning();
  }

  const modal = document.querySelector('.prototype-modal');

  if (!modal) {
    return;
  }

  const focusable = modal.querySelector('a, button');
  focusable?.focus();

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      modal.querySelector('.modal-close')?.click();
    }
  });
})();
